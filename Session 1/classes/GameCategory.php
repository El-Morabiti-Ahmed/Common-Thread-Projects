<?php
/**
 * Game categories (used to sort/filter the games).
 */
class GameCategory extends BaseModel
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM game_category ORDER BY name')->fetchAll();
    }

    /** Same list, plus how many games each category contains (admin page). */
    public function allWithGameCount(): array
    {
        return $this->db->query(
            'SELECT c.*, COUNT(g.id) AS game_count
             FROM game_category c
             LEFT JOIN game g ON g.category_id = c.id
             GROUP BY c.id
             ORDER BY c.name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM game_category WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function validate(string $name, string $description): array
    {
        $errors = [];
        if ($name === '' || strlen($name) > 50) {
            $errors[] = 'Category name is required (50 characters max).';
        }
        if (strlen($description) > 255) {
            $errors[] = 'Description is too long (255 characters max).';
        }
        return $errors;
    }

    public function create(string $name, string $description): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO game_category (name, description) VALUES (:name, :description)'
        );
        $this->save($stmt, ['name' => $name, 'description' => $description ?: null]);
    }

    public function update(int $id, string $name, string $description): void
    {
        $stmt = $this->db->prepare(
            'UPDATE game_category SET name = :name, description = :description WHERE id = :id'
        );
        $this->save($stmt, ['name' => $name, 'description' => $description ?: null, 'id' => $id]);
    }

    /** Games of a deleted category are kept (category becomes empty). */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM game_category WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function save(PDOStatement $stmt, array $params): void
    {
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                throw new DomainException('A category with that name already exists.');
            }
            throw $e;
        }
    }
}
