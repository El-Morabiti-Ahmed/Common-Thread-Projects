<?php
// Generic JSON file storage
class JsonStore {
    private string $file;
    public function __construct(string $name) {
        $this->file = __DIR__ . "/data/$name.json";
        if (!file_exists($this->file)) file_put_contents($this->file, '[]');
    }
    public function all(): array { return json_decode(file_get_contents($this->file), true) ?: []; }
    public function save(array $rows): void {
        file_put_contents($this->file, json_encode(array_values($rows), JSON_PRETTY_PRINT), LOCK_EX);
    }
    public function add(array $row): array {
        $rows = $this->all();
        $row = ['id' => $rows ? max(array_column($rows, 'id')) + 1 : 1] + $row;
        $rows[] = $row;
        $this->save($rows);
        return $row;
    }
}

class Rank {
    private const TIERS = [60 => 'immortal', 50 => 'beast', 35 => 'diamond', 20 => 'gold', 10 => 'bronze', 5 => 'copper', 0 => 'iron'];
    public static function fromPoints(int $p): string {
        foreach (self::TIERS as $min => $name) if ($p >= $min) return $name;
        return 'iron';
    }
    public static function delta(string $result): int { return $result === 'win' ? 2 : -1; }
}

class PlayerService {
    private JsonStore $store;
    public function __construct() { $this->store = new JsonStore('players'); }
    public static function clean(array $p): array { unset($p['password']); return $p; }
    public function find(int $id): ?array {
        foreach ($this->store->all() as $p) if ($p['id'] === $id) return $p;
        return null;
    }
    public function register(string $user, string $pass): array {
        $user = trim($user);
        if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $user)) throw new Exception('Username: 3-20 letters, numbers or _');
        if (strlen($pass) < 6) throw new Exception('Password must be at least 6 characters');
        foreach ($this->store->all() as $p)
            if (strtolower($p['username']) === strtolower($user)) throw new Exception('Username already taken');
        return $this->store->add(['username' => $user, 'password' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'player', 'points' => 0, 'rank' => 'iron', 'created_at' => date('c')]);
    }
    public function login(string $user, string $pass): array {
        foreach ($this->store->all() as $p)
            if (strtolower($p['username']) === strtolower(trim($user)) && password_verify($pass, $p['password'])) return $p;
        throw new Exception('Wrong username or password');
    }
    public function applyResult(int $id, string $result): array {
        $rows = $this->store->all();
        foreach ($rows as &$p) if ($p['id'] === $id) {
            $p['points'] = max(0, $p['points'] + Rank::delta($result));
            $p['rank'] = Rank::fromPoints($p['points']);
            $this->store->save($rows);
            return $p;
        }
        throw new Exception('Player not found');
    }
    public function allPublic(): array { return array_map([self::class, 'clean'], $this->store->all()); }
}

class Api {
    private PlayerService $players;
    private JsonStore $games, $cats, $sessions;
    public function __construct() {
        $this->players = new PlayerService();
        $this->games = new JsonStore('games');
        $this->cats = new JsonStore('categories');
        $this->sessions = new JsonStore('sessions');
    }
    private function out($data, int $code = 200): void { http_response_code($code); echo json_encode($data); exit; }
    private function me(): ?array { return isset($_SESSION['uid']) ? $this->players->find($_SESSION['uid']) : null; }
    private function require(bool $admin = false): array {
        $me = $this->me();
        if (!$me) $this->out(['error' => 'Please log in'], 401);
        if ($admin && $me['role'] !== 'admin') $this->out(['error' => 'Admins only'], 403);
        return $me;
    }
    public function handle(): void {
        try {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $this->post() : $this->get();
        } catch (Exception $e) { $this->out(['error' => $e->getMessage()], 400); }
    }
    private function get(): void {
        switch ($_GET['resource'] ?? '') {
            case 'games': $this->out($this->games->all());
            case 'categories': $this->out($this->cats->all());
            case 'players': $this->out($this->players->allPublic());
            case 'me': $me = $this->me(); $this->out($me ? PlayerService::clean($me) : null);
            case 'sessions':
                $me = $this->require();
                $all = $this->sessions->all();
                $this->out($me['role'] === 'admin' ? $all : array_values(array_filter($all, fn($s) => $s['player_id'] === $me['id'])));
        }
        $this->out(['error' => 'Unknown resource'], 404);
    }
    private function post(): void {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        switch ($d['action'] ?? '') {
            case 'register':
                $p = $this->players->register($d['username'] ?? '', $d['password'] ?? '');
                $_SESSION['uid'] = $p['id']; $this->out(PlayerService::clean($p));
            case 'login':
                $p = $this->players->login($d['username'] ?? '', $d['password'] ?? '');
                $_SESSION['uid'] = $p['id']; $this->out(PlayerService::clean($p));
            case 'logout': session_destroy(); $this->out(['ok' => true]);
            case 'session':
                $me = $this->require();
                $game = array_values(array_filter($this->games->all(), fn($g) => $g['id'] === (int)($d['game_id'] ?? 0)));
                if (!$game || !in_array($d['result'] ?? '', ['win', 'loss'], true)) throw new Exception('Invalid session');
                $this->sessions->add(['player_id' => $me['id'], 'game_id' => $game[0]['id'], 'result' => $d['result'],
                    'points_change' => Rank::delta($d['result']), 'played_at' => date('c')]);
                $this->out(PlayerService::clean($this->players->applyResult($me['id'], $d['result'])));
            case 'add_category':
                $this->require(true);
                if (trim($d['name'] ?? '') === '') throw new Exception('Name required');
                $this->out($this->cats->add(['name' => trim($d['name'])]));
            case 'add_game':
                $this->require(true);
                if (trim($d['name'] ?? '') === '') throw new Exception('Name required');
                $this->out($this->games->add(['name' => trim($d['name']), 'key' => $d['key'] ?? '',
                    'category_id' => (int)($d['category_id'] ?? 0), 'description' => trim($d['description'] ?? '')]));
        }
        $this->out(['error' => 'Unknown action'], 404);
    }
}
