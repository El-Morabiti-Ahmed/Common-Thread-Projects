<?php
/**
 * One finished game (win or loss). This class also applies the
 * points and updates the player's rank.
 */
class GameSession extends BaseModel
{
    /**
     * Saves a session and updates the player's points + rank.
     * Everything runs in ONE transaction: either all is saved or nothing.
     */
    public function record(int $playerId, int $gameId, string $result): array
    {
        if (!in_array($result, ['win', 'loss'], true)) {
            throw new InvalidArgumentException('Result must be "win" or "loss".');
        }

        $players = new Player();
        $this->db->beginTransaction();

        try {
            // lock the player row so two games finishing together can't mix points
            $player = $players->findById($playerId, true);
            if ($player === null) {
                throw new RuntimeException('Player not found.');
            }

            $before = (int) $player['points'];
            $after  = $result === 'win'
                ? $before + Rank::WIN_POINTS
                : max(0, $before - Rank::LOSS_POINTS);   // never below 0

            $oldRank = Rank::fromPoints($before);
            $newRank = Rank::fromPoints($after);

            $stmt = $this->db->prepare(
                'INSERT INTO game_session (player_id, game_id, result, points_change)
                 VALUES (:player, :game, :result, :change)'
            );
            $stmt->execute([
                'player' => $playerId,
                'game'   => $gameId,
                'result' => $result,
                'change' => $after - $before,
            ]);

            $players->setPoints($playerId, $after, $newRank);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        // 'up', 'down' or 'same'
        $movement = Rank::index($newRank) <=> Rank::index($oldRank);

        return [
            'result'        => $result,
            'points_change' => $after - $before,
            'points'        => $after,
            'rank'          => $newRank,
            'rank_label'    => Rank::label($newRank),
            'rank_change'   => [-1 => 'down', 0 => 'same', 1 => 'up'][$movement],
        ];
    }

    /** Games played, wins, losses and win rate of one player. */
    public function statsForPlayer(int $playerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS games,
                    COALESCE(SUM(result = 'win'), 0)  AS wins,
                    COALESCE(SUM(result = 'loss'), 0) AS losses
             FROM game_session WHERE player_id = :id"
        );
        $stmt->execute(['id' => $playerId]);
        $row = $stmt->fetch();

        $games = (int) $row['games'];
        $wins  = (int) $row['wins'];

        return [
            'games'    => $games,
            'wins'     => $wins,
            'losses'   => (int) $row['losses'],
            'win_rate' => $games > 0 ? (int) round($wins / $games * 100) : 0,
        ];
    }

    public function recentForPlayer(int $playerId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.result, s.points_change, s.played_at, g.name AS game_name, g.icon
             FROM game_session s
             JOIN game g ON g.id = s.game_id
             WHERE s.player_id = :id
             ORDER BY s.played_at DESC, s.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':id', $playerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Latest sessions of everybody (admin dashboard). */
    public function recentAll(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.result, s.points_change, s.played_at,
                    g.name AS game_name, p.username
             FROM game_session s
             JOIN game g   ON g.id = s.game_id
             JOIN player p ON p.id = s.player_id
             ORDER BY s.played_at DESC, s.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Plays and wins per game (admin dashboard). */
    public function statsByGame(): array
    {
        return $this->db->query(
            "SELECT g.name, g.icon,
                    COUNT(s.id) AS plays,
                    COALESCE(SUM(s.result = 'win'), 0) AS wins
             FROM game g
             LEFT JOIN game_session s ON s.game_id = g.id
             GROUP BY g.id
             ORDER BY plays DESC, g.name"
        )->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM game_session')->fetchColumn();
    }
}
