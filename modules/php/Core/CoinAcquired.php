<?php
declare(strict_types=1);

namespace Bga\Games\CoinRace\Core;

/**
 * Coin Acquired Message
 *
 * Message emitted when a player acquires a coin.
 */
class CoinAcquired {
    public function __construct(
        public int $player_id,
        public int $amount
    ) {}
}
