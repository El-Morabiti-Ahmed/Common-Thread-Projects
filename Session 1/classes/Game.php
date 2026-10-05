<?php
/**
 * The games players can play.
 * "slug" is the name of the JavaScript file that contains the game
 * (assets/js/games/<slug>.js).
 */
class Game extends BaseModel
{
    /** Games with their category name. Optional filter by category. */
    public function all(?int $categoryId = null, bool $onlyActive = true): array
    {
        $sql = 'SELECT g.*, c.name AS category_name
                FROM game g
                LEFT JOIN game_category c ON c.id = g.category_id
                WHERE 1 = 1';
        $params = [];

        if ($onlyActive) {
            $sql .= ' AND g.is_active = 1';
        }
        if ($categoryId !== null) {
            $sql .= ' AND g.category_id = :category';
            $params['category'] = $categoryId;
        }

        $stmt = $this->db->prepare($sql . ' ORDER BY g.name');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT g.*, c.name AS category_name
             FROM game g
             LEFT JOIN game_category c ON c.id = g.category_id
             WHERE g.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Does assets/js/games/<slug>.js exist? */
    public static function scriptExists(string $slug): bool
    {
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return false;                     // blocks things like ../../
        }
        return is_file(dirname(__DIR__) . '/assets/js/games/' . $slug . '.js');
    }

    public function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '' || strlen($data['name']) > 60) {
            $errors[] = 'Game name is required (60 characters max).';
        }
        if (!preg_match('/^[a-z0-9-]{2,60}$/', $data['slug'])) {
            $errors[] = 'Slug must be 2-60 characters: lowercase letters, numbers and dashes.';
        }
        if (strlen($data['description']) > 255) {
            $errors[] = 'Description is too long (255 characters max).';
        }
        if ($data['icon'] === '' || mb_strlen($data['icon']) > 8) {
            $errors[] = 'Icon must be a short emoji (8 characters max).';
        }

        return $errors;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO game (category_id, name, slug, description, icon, is_active)
             VALUES (:category_id, :name, :slug, :description, :icon, :is_active)'
        );
        $this->save($stmt, $this->params($data));
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE game
             SET category_id = :category_id, name = :name, slug = :slug,
                 description = :description, icon = :icon, is_active = :is_active
             WHERE id = :id'
        );
        $this->save($stmt, $this->params($data) + ['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE game SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** A game with recorded sessions cannot be deleted (deactivate it instead). */
    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM game WHERE id = :id');
            $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1451) {   // foreign key restrict
                throw new DomainException(
                    'This game already has recorded sessions. Deactivate it instead of deleting it.'
                );
            }
            throw $e;
        }
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM game')->fetchColumn();
    }

    private function params(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'icon'        => $data['icon'],
            'is_active'   => $data['is_active'],
        ];
    }

    private function save(PDOStatement $stmt, array $params): void
    {
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                throw new DomainException('A game with that slug already exists.');
            }
            throw $e;
        }
    }
}
