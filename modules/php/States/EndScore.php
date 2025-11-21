<?php

/**
 * EndScore - ゲーム終了スコア計算状態
 *
 * ゲーム終了直前にスコアを計算し、統計情報を集計する状態
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\CoinRace\Game;

// ゲーム終了状態のID
const ST_END_GAME = 99;

class EndScore extends GameState
{
    // 定数定義
    private const STATE_ID = 98;

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
        );
    }

    /**
     * 状態突入時の処理
     *
     * ゲーム終了直前に呼び出され、最終スコア計算と統計集計を行う
     *
     * @return int 次の状態ID（常にST_END_GAME = 99）
     */
    public function onEnteringState(): int
    {
        // スコアがリアルタイム更新されていない場合はここで計算
        // $this->computeFinalScores();

        // 統計情報の集計
        // $this->computeStatistics();

        // ゲーム終了状態へ遷移
        return ST_END_GAME;
    }

    /**
     * 最終スコアを計算
     *
     * リアルタイム更新していない場合に使用
     */
    private function computeFinalScores(): void
    {
        // TODO: 最終スコア計算ロジックを実装
        // 例:
        // - 手札のペナルティ計算
        // - ボーナス点の加算
        // - タイブレーカー値の設定
    }

    /**
     * 統計情報を計算
     *
     * ゲーム終了時の統計データを集計
     */
    private function computeStatistics(): void
    {
        // TODO: 統計情報の計算ロジックを実装
        // 例:
        // $this->playerStats->set('total_cards_played', $playerId, $count);
        // $this->tableStats->set('game_duration', $duration);
    }
}
