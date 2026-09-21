<?php
/**
 * Called by the JavaScript games (fetch) when a game ends.
 * Receives JSON: { game_id, result: "win"|"loss", token }
 * Answers with JSON: new points, rank, etc.
 *
 * The points are calculated HERE on the server, the browser only
 * says "win" or "loss".
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'POST request required.'], 405);
}

$user = Auth::user();
if ($user === null) {
    respond(['success' => false, 'message' => 'You are not logged in.'], 401);
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    respond(['success' => false, 'message' => 'Invalid request.'], 400);
}

$gameId = (int) ($input['game_id'] ?? 0);
$result = (string) ($input['result'] ?? '');
$token  = (string) ($input['token'] ?? '');

if (!PlayToken::verify($gameId, $token)) {
    respond(['success' => false, 'message' => 'This game session expired. Reload the page and play again.'], 403);
}

if (!in_array($result, ['win', 'loss'], true)) {
    respond(['success' => false, 'message' => 'Invalid result.'], 400);
}

$game = (new Game())->find($gameId);
if ($game === null || !(int) $game['is_active']) {
    respond(['success' => false, 'message' => 'Game not found.'], 404);
}

try {
    $summary = (new GameSession())->record((int) $user['id'], $gameId, $result);
} catch (Throwable $e) {
    error_log('save_session failed: ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Could not save your session. Try again.'], 500);
}

// new one-time token for the next round
$summary['token'] = PlayToken::issue($gameId);

respond(['success' => true] + $summary);
