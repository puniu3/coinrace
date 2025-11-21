<?php

/**
 * NextPlayer - 次プレイヤー移行状態
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\CoinRace\Game;
use Bga\Games\CoinRace\Core\GameLogic;

class NextPlayer extends GameState
{
    private const STATE_ID = 90;

    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: self::STATE_ID,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    public function onEnteringState(int $activePlayerId): string
    {
        // 直前のアクション完了プレイヤーに追加時間を付与（任意）
        //$this->game->giveExtraTime($activePlayerId);

        // 1. Load State to check is_over
        $state = $this->game->loadState();

        if (GameLogic::is_over($state)) {
            return EndScore::class;
        }

        // 2. Switch Active Player in BGA
        // Functional CoreのStateは既に次のプレイヤーIndexになっている (advanceで更新済み)
        // BGAのactive_playerもそれに合わせる

        $nextActiveIndex = $state->active;
        $nextActiveId = $this->game->mapIndexToPlayerId($nextActiveIndex);

        $this->game->gamestate->changeActivePlayer($nextActiveId);

        return PlayerTurn::class;
    }
}
