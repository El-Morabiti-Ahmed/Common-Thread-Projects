<?php
/**
 * Everything about the player table: register, login check,
 * points/rank updates and leaderboard.
 */
class Player extends BaseModel
{
    // never select password_hash unless we really need it
    private const PUBLIC_COLUMNS = 'id, username, email, role, points, player_rank, created_at';

    public function findById(int $id, bool $lock = false): ?array
    {
        $sql = 'SELECT ' . self::PUBLIC_COLUMNS . ' FROM player WHERE id = :id';
        if ($lock) {
            $sql .= ' FOR UPDATE';            // used inside a transaction
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Checks the rules for a new account and returns a list of errors. */
    public function validateRegistration(string $username, string $email, string $password, string $confirm): array
    {
        $errors = [];

        if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
            $errors[] = 'Username must be 3-20 characters: letters, numbers or underscore.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }

        if (!$errors) {
            $stmt = $this->db->prepare(
                'SELECT username, email FROM player WHERE username = :u OR email = :e'
            );
            $stmt->execute(['u' => $username, 'e' => $email]);
            foreach ($stmt->fetchAll() as $row) {
                if (strcasecmp($row['username'], $username) === 0) {
                    $errors[] = 'That username is already taken.';
                }
                if (strcasecmp($row['email'], $email) === 0) {
                    $errors[] = 'That email is already registered.';
                }
            }
            $errors = array_unique($errors);
        }

        return $errors;
    }

    /** Creates the account and returns the new player id. */
    public function register(string $username, string $email, string $password): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO player (username, email, password_hash)
             VALUES (:username, :email, :hash)'
        );

        try {
            $stmt->execute([
                'username' => $username,
                'email'    => $email,
                'hash'     => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $e) {
            if ($this->isDuplicate($e)) {
                throw new DomainException('Username or email is already used.');
            }
            throw $e;
        }

        return (int) $this->db->lastInsertId();
    }

    /** Login with username OR email. Returns the player or null. */
    public function authenticate(string $identifier, string $password): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, password_hash FROM player WHERE username = :u OR email = :e LIMIT 1'
        );
        $stmt->execute(['u' => $identifier, 'e' => $identifier]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return null;
        }

        return $this->findById((int) $row['id']);
    }

    /** Called by GameSession after each finished game. */
    public function setPoints(int $id, int $points, string $rank): void
    {
        $stmt = $this->db->prepare(
            'UPDATE player SET points = :points, player_rank = :rank WHERE id = :id'
        );
        $stmt->execute(['points' => $points, 'rank' => $rank, 'id' => $id]);
    }

    /** Best players first. */
    public function leaderboard(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.username, p.points, p.player_rank,
                    COUNT(s.id) AS games,
                    COALESCE(SUM(s.result = 'win'), 0) AS wins
             FROM player p
             LEFT JOIN game_session s ON s.player_id = p.id
             GROUP BY p.id
             ORDER BY p.points DESC, wins DESC, p.username ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Admin list: every player with the number of games played. */
    public function allWithStats(): array
    {
        return $this->db->query(
            'SELECT p.id, p.username, p.email, p.role, p.points, p.player_rank, p.created_at,
                    COUNT(s.id) AS games
             FROM player p
             LEFT JOIN game_session s ON s.player_id = p.id
             GROUP BY p.id
             ORDER BY p.created_at DESC'
        )->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM player')->fetchColumn();
    }
}
