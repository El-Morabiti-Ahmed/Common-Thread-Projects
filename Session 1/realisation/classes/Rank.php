<?php
/**
 * Rank system. Nothing is stored here, it is just the rules:
 *   win  = +2 points
 *   loss = -1 point (never below 0)
 */
class Rank
{
    public const WIN_POINTS  = 2;
    public const LOSS_POINTS = 1;

    // rank => minimum points (lowest to highest)
    private const TIERS = [
        'iron'     => 0,
        'copper'   => 5,
        'bronze'   => 10,
        'gold'     => 20,
        'diamond'  => 35,
        'beast'    => 50,
        'immortal' => 60,
    ];

    /** All ranks with their minimum points. */
    public static function all(): array
    {
        return self::TIERS;
    }

    /** Rank name for a number of points. */
    public static function fromPoints(int $points): string
    {
        $current = 'iron';
        foreach (self::TIERS as $name => $min) {
            if ($points >= $min) {
                $current = $name;
            }
        }
        return $current;
    }

    /** Position of a rank (0 = iron ... 6 = immortal). Used to compare ranks. */
    public static function index(string $rank): int
    {
        $position = array_search($rank, array_keys(self::TIERS), true);
        return $position === false ? 0 : (int) $position;
    }

    public static function label(string $rank): string
    {
        return ucfirst($rank);
    }

    /** HTML badge, the colour comes from the CSS class rank-xxx. */
    public static function badge(string $rank): string
    {
        if (!isset(self::TIERS[$rank])) {
            $rank = 'iron';
        }
        return '<span class="badge rank-' . $rank . '">' . self::label($rank) . '</span>';
    }

    /**
     * Progress towards the next rank.
     * Returns: next (rank name or null), remaining points, percent (0-100).
     */
    public static function progress(int $points): array
    {
        $current = self::fromPoints($points);
        $names   = array_keys(self::TIERS);
        $next    = $names[self::index($current) + 1] ?? null;

        if ($next === null) {
            return ['next' => null, 'remaining' => 0, 'percent' => 100];
        }

        $start   = self::TIERS[$current];
        $goal    = self::TIERS[$next];
        $percent = (int) round((($points - $start) / ($goal - $start)) * 100);

        return [
            'next'      => $next,
            'remaining' => $goal - $points,
            'percent'   => max(0, min(100, $percent)),
        ];
    }
}
