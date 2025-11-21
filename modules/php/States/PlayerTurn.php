<?php

/**
 * PlayerTurn - プレイヤーターン状態
 *
 * プレイヤーがカードをプレイするか、パスする状態
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\CoinRace\Game;
use Bga\Games\CoinRace\Core\GameLogic;
use Bga\Games\CoinRace\Core\DrawAction;

class PlayerTurn extends GameState
{
    // 定数定義
    private const STATE_ID = 10;

    /**
     * コンストラクタ
     *
     * @param Game $game ゲームインスタンス
     */
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

    /**
     * ゲーム状態の引数を取得
     *
     * この状態で必要な情報を返す（プレイ可能なカードIDなど）
     *
     * @return array 状態引数
     */
    public function getArgs(): array
    {
        // For DrawAction, there are no arguments needed
        // The player just draws the top card
        return [];
    }

    /**
     * Draw a coin action
     *
     * Called from frontend: bgaPerformAction("actDrawCoin")
     *
     * @param int $activePlayerId Active player ID
     * @param array $args State arguments (getArgs() return value)
     * @return string Next state class name
     */
    #[PossibleAction]
    public function actDrawCoin(int $activePlayerId, array $args): string
    {
        // ========================================
        // Imperative Shell: Load state from DB
        // ========================================
        $state = $this->game->loadState();

        // ========================================
        // Functional Core: Advance state
        // ========================================
        $action = new DrawAction();
        $newState = GameLogic::advance($state, $action);

        // ========================================
        // Imperative Shell: Extract messages and apply side effects
        // ========================================

        // Process messages from functional core
        foreach ($newState->msg as $msg) {
            if ($msg instanceof \Bga\Games\CoinRace\Core\CoinAcquired) {
                // Notify all players
                $this->notify->all('coinAcquired', clienttranslate('${player_name} draws a coin and gains ${amount} point(s)'), [
                    'player_id' => $activePlayerId,
                    'player_name' => $this->game->getPlayerNameById($activePlayerId),
                    'amount' => $msg->amount,
                    'score' => $newState->players[$msg->player_id],
                ]);
            }
        }

        // ========================================
        // Imperative Shell: Save state to DB
        // ========================================
        $this->game->saveState($newState);

        // Note: Active player will be changed by NextPlayer state
        // We save the next active player index in the state, and NextPlayer will use it

        // Transition to next state
        return NextPlayer::class;
    }

    /**
     * ゾンビモード処理
     *
     * プレイヤーが切断した場合の自動処理
     *
     * 重要: getCurrentPlayerId() は使用しない
     * 　　　引数の $playerId を使用すること
     *
     * @param int $playerId ゾンビプレイヤーのID
     * @return string 次の状態クラス名
     * @see https://en.doc.boardgamearena.com/Zombie_Mode
     */
    public function zombie(int $playerId): string
    {
        // Simply draw a coin automatically
        $args = $this->getArgs();
        return $this->actDrawCoin($playerId, $args);
    }
}
