<?php

/**
 * CoinRace - メインゲームロジック
 *
 * @author <Your name here> <Your email address here>
 * @copyright Board Game Arena
 * @see http://en.boardgamearena.com/#!doc/Studio
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace;

// Import GameLogic manually since BGA autoloader might not pick it up for Core namespace
require_once(__DIR__ . '/GameLogic.php');

use Bga\Games\CoinRace\States\PlayerTurn;
use Bga\Games\CoinRace\Core\GameLogic;
use Bga\Games\CoinRace\Core\State;

class Game extends \Bga\GameFramework\Table
{
    private const GAME_END_STATE = 99;
    private const DEFAULT_DEBUG_STATE = 10;
    private const DEFAULT_AUTO_PLAY_MOVES = 50;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct();

        // ゲーム状態ラベルの初期化
        $this->initGameStateLabels([]);
    }

    /**
     * ゲーム進行度を計算（0～100%）
     */
    public function getGameProgression(): int
    {
        $state = $this->loadState();
        $total = 10; // 初期デッキサイズ
        $current = count($state->deck);
        return (int) (($total - $current) / $total * 100);
    }

    /**
     * データベーススキーマのマイグレーション
     */
    public function upgradeTableDb($from_version): void
    {
    }

    /**
     * 現在のゲーム状況を取得
     */
    protected function getAllDatas(): array
    {
        $result = [];

        // BGA標準のプレイヤー情報
        $result['players'] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

        // Functional Coreの状態を取得
        $fcState = $this->loadState();

        // View情報の構築
        $result['deck_size'] = count($fcState->deck);
        $result['active_player_index'] = $fcState->active;
        // active_player_idはBGA側で持っているが、FC側と整合しているか確認用などに

        return $result;
    }

    /**
     * 新規ゲームのセットアップ
     */
    protected function setupNewGame($players, $options = []): string
    {
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        $query_values = [];
        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player['player_canal'],
                addslashes($player['player_name']),
                addslashes($player['player_avatar']),
            ]);
        }

        static::DbQuery(sprintf(
            "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
            implode(',', $query_values)
        ));

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        $this->reloadPlayersBasicInfos();

        // Functional Coreの初期化
        $initialState = GameLogic::create_initial_state();
        $this->saveState($initialState);

        // 最初のプレイヤーをアクティブ化
        // FCのactiveは0なので、BGA側も0番目のプレイヤーをアクティブにする
        $sortedIds = $this->getSortedPlayerIds();
        $this->gamestate->changeActivePlayer($sortedIds[0]);

        return PlayerTurn::class;
    }

    // ========================================
    // Action Logic
    // ========================================

    public function actDraw(): void
    {
        $this->gamestate->checkPossibleAction('actDraw');

        $playerId = (int) $this->getCurrentPlayerId();

        // State Class インスタンスを作成してアクションを実行
        // ステートID 10 は PlayerTurn クラス
        // ここでは単純化のため直接インスタンス化するが、
        // 将来的に他のStateのアクションが増えるなら分岐が必要

        $playerTurnState = new PlayerTurn($this);
        $nextStateClass = $playerTurnState->actDraw($playerId);

        // 状態遷移
        $this->gamestate->nextState($nextStateClass);
    }

    // ========================================
    // Functional Core Integration / Helpers
    // ========================================

    public function loadState(): State
    {
        $json = $this->getUniqueValueFromDB("SELECT value FROM global_state WHERE `key` = 'main_state'");
        if (!$json) {
            // エラーハンドリングまたは初期状態（通常はsetupNewGameで作られるのでここは来ないはず）
            return GameLogic::create_initial_state();
        }

        $data = json_decode($json, true);

        // 配列からオブジェクトに復元（簡易実装）
        // GameLogic.phpの定義に合わせて復元
        return new State(
            players: $data['players'],
            active: $data['active'],
            deck: $data['deck'],
            msg: [] // msgは永続化する必要がない（ターン毎にクリアされる）
        );
    }

    public function saveState(State $state): void
    {
        $json = json_encode([
            'players' => $state->players,
            'active' => $state->active,
            'deck' => $state->deck,
        ]);

        // UPSERT
        $this->DbQuery("INSERT INTO global_state (`key`, `value`) VALUES ('main_state', '$json') ON DUPLICATE KEY UPDATE `value` = '$json'");
    }

    /**
     * player_id をソートしてインデックス（0, 1...）にマッピング
     */
    public function getSortedPlayerIds(): array
    {
        $players = $this->getCollectionFromDb("SELECT player_id FROM player ORDER BY player_id ASC");
        return array_keys($players);
    }

    public function mapPlayerIdToIndex(int $playerId): int
    {
        $ids = $this->getSortedPlayerIds();
        $index = array_search($playerId, $ids);
        if ($index === false) {
            throw new \Bga\GameFramework\UserException("Player ID $playerId not found");
        }
        return (int) $index;
    }

    public function mapIndexToPlayerId(int $index): int
    {
        $ids = $this->getSortedPlayerIds();
        if (!isset($ids[$index])) {
            throw new \Bga\GameFramework\UserException("Player index $index out of bounds");
        }
        return (int) $ids[$index];
    }

    // ========================================
    // Helper Methods
    // ========================================

    public function dbIncScore(int $playerId, int $amount): void
    {
        $this->DbQuery("UPDATE player SET player_score = player_score + $amount WHERE player_id = $playerId");
    }

    // ========================================
    // デバッグ機能
    // ========================================

    public function debug_goToState(int $state = self::DEFAULT_DEBUG_STATE): void
    {
        $this->gamestate->jumpToState($state);
    }

    public function debug_playAutomatically(int $moves = self::DEFAULT_AUTO_PLAY_MOVES): void
    {
        // 簡易実装: 現在のプレイヤーで actDraw を呼び続ける
        $count = 0;
        $current_state_id = (int) $this->gamestate->getCurrentMainStateId();

        while ($current_state_id < self::GAME_END_STATE && $count < $moves) {
            $count++;

            // ゾンビモードのロジックを使って進める
            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int) $playerId;
                $current_state = $this->gamestate->getCurrentState($playerId);
                $this->gamestate->runStateClassZombie($current_state, $playerId);
            }

            $current_state_id = (int) $this->gamestate->getCurrentMainStateId();
        }
    }
}
