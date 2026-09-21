<?php
/**
 * One-time token given to the player when a game page opens.
 * The result can only be saved with the matching token, and the
 * token is replaced after every saved session. This stops a result
 * from being sent twice and stops random POST requests.
 */
class PlayToken
{
    public static function issue(int $gameId): string
    {
        $token = bin2hex(random_bytes(16));
        $_SESSION['play_tokens'][$gameId] = $token;
        return $token;
    }

    public static function verify(int $gameId, string $token): bool
    {
        $expected = $_SESSION['play_tokens'][$gameId] ?? null;
        return is_string($expected) && $token !== '' && hash_equals($expected, $token);
    }
}
