<?php
declare(strict_types=1);

namespace Bga\Games\CoinRace\Core;

/**
 * Game State (immutable)
 *
 * Represents the complete game state at a point in time.
 */
class State {
    /**
     * @param int[] $players Index based scores [0 => score, 1 => score]
     * @param int $active PlayerIndex (0 or 1)
     * @param int[] $deck List of coins
     * @param object[] $msg List of Messages
     */
    public function __construct(
        public array $players,
        public int $active,
        public array $deck,
        public array $msg
    ) {}
}
