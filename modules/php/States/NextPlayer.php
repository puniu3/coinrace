<?php

/**
 * NextPlayer - 次プレイヤー移行状態
 *
 * プレイヤー間のターン遷移を管理する中間状態
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\CoinRace\Game;

class NextPlayer extends GameState
{
    // 定数定義
    private const STATE_ID = 90;

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
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * 状態突入時の処理
     *
     * プレイヤーのターンが終了し、次のプレイヤーに移行する際に呼び出される
     *
     * @param int $activePlayerId アクション完了したプレイヤーのID
     * @return string 次の状態クラス名
     */
    public function onEnteringState(int $activePlayerId): string
    {
        // アクション完了したプレイヤーに追加時間を付与
        $this->game->giveExtraTime($activePlayerId);

        // 次のプレイヤーをアクティブ化
        $this->game->activeNextPlayer();

        // ゲーム終了判定
        $gameEnd = $this->isGameEnd();

        if ($gameEnd) {
            return EndScore::class;
        }

        return PlayerTurn::class;
    }

    /**
     * ゲーム終了判定
     *
     * @return bool ゲームが終了している場合true
     */
    private function isGameEnd(): bool
    {
        // TODO: ゲーム終了条件を実装
        // 例:
        // - 全カードがプレイされた
        // - 規定ラウンド数に達した
        // - 勝利条件を満たしたプレイヤーがいる

        return false;
    }
}
