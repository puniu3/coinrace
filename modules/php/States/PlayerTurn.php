<?php

/**
 * PlayerTurn - プレイヤーターン状態
 *
 * プレイヤーがコインを引く状態
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\CoinRace\Game;
use Bga\Games\CoinRace\Core\DrawAction;
use Bga\Games\CoinRace\Core\GameLogic;
use Bga\Games\CoinRace\Core\CoinAcquired;

class PlayerTurn extends GameState
{
    private const STATE_ID = 10;

    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: self::STATE_ID,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must draw a coin'),
            descriptionMyTurn: clienttranslate('${you} must draw a coin'),
        );
    }

    public function getArgs(): array
    {
        // Viewに必要な情報はgetAllDatasやNotificationで送るため、ここは空でも良いが、
        // 必要に応じて追加情報を返す
        return [];
    }

    /**
     * コインを引くアクション
     */
    #[PossibleAction]
    public function actDraw(int $activePlayerId): string
    {
        // 1. Load State
        $state = $this->game->loadState();

        // Check if active player matches
        $expectedIndex = $state->active;
        $actualIndex = $this->game->mapPlayerIdToIndex($activePlayerId);

        if ($expectedIndex !== $actualIndex) {
             throw new UserException("It is not your turn (Internal State Mismatch)");
        }

        // 2. Execute Action via Functional Core
        $action = new DrawAction();
        $nextState = GameLogic::advance($state, $action);

        // 3. Save State
        $this->game->saveState($nextState);

        // 4. Handle Messages / Notifications
        foreach ($nextState->msg as $msg) {
            if ($msg instanceof CoinAcquired) {
                // Update BGA Score
                // メッセージに含まれる player_id は index なので変換が必要
                $playerId = $this->game->mapIndexToPlayerId($msg->player_id);
                $this->game->dbIncScore($playerId, $msg->amount);

                $this->notify->all('coinAcquired', clienttranslate('${player_name} draws a coin with value ${amount}'), [
                    'player_id' => $playerId,
                    'player_name' => $this->game->getPlayerNameById($playerId),
                    'amount' => $msg->amount,
                    'deck_size' => count($nextState->deck),
                    'new_score' => $nextState->players[$msg->player_id], // FCのStateが最新スコアを持っている
                ]);
            }
        }

        // 5. Transition
        return NextPlayer::class;
    }

    /**
     * ゾンビモード処理
     */
    public function zombie(int $playerId): string
    {
        // 自動的にドローする
        return $this->actDraw($playerId);
    }
}
