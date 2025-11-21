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

use Bga\Games\CoinRace\Core\GameLogic;
use Bga\Games\CoinRace\Core\State;
use Bga\Games\CoinRace\Core\DrawAction;
use Bga\Games\CoinRace\States\PlayerTurn;
use Bga\GameFramework\Components\Counters\PlayerCounter;

class Game extends \Bga\GameFramework\Table
{
    // 定数定義
    private const GAME_END_STATE = 99;
    private const DEFAULT_DEBUG_STATE = 10;
    private const DEFAULT_AUTO_PLAY_MOVES = 50;

    /**
     * コンストラクタ
     * ゲーム状態ラベル、カウンター、カードタイプを初期化
     */
    public function __construct()
    {
        parent::__construct();

        // ゲーム状態ラベルの初期化（空でも必須）
        $this->initGameStateLabels([]);
    }

    // ========================================
    // Functional Core Integration
    // ========================================

    /**
     * Load current game state from DB
     *
     * Imperative shell: Load data from DB and construct functional State object
     */
    public function loadState(): State
    {
        // Load player scores (indexed by player_id)
        $playersData = $this->getCollectionFromDb(
            "SELECT `player_id`, `player_score`, `player_no` FROM `player` ORDER BY `player_no`"
        );

        // Convert to array indexed by player_no (0, 1)
        $players = [0, 0];
        $playerIdToIndex = [];
        foreach ($playersData as $player_id => $player) {
            $index = (int)$player['player_no'];
            $players[$index] = (int)$player['player_score'];
            $playerIdToIndex[$player_id] = $index;
        }

        // Get current active player ID and convert to index
        $activePlayerId = (int)$this->getActivePlayerId();
        $active = $playerIdToIndex[$activePlayerId];

        // Load deck (ordered by position)
        $deckData = $this->getCollectionFromDb(
            "SELECT `card_value` FROM `deck` ORDER BY `card_position`"
        );
        $deck = array_map(fn($card) => (int)$card['card_value'], array_values($deckData));

        return new State($players, $active, $deck, []);
    }

    /**
     * Save game state to DB
     *
     * Imperative shell: Save State object data back to DB
     */
    public function saveState(State $state): void
    {
        // Update player scores
        $playersData = $this->getCollectionFromDb(
            "SELECT `player_id`, `player_no` FROM `player` ORDER BY `player_no`"
        );

        foreach ($playersData as $player_id => $player) {
            $index = (int)$player['player_no'];
            $score = $state->players[$index];
            $this->DbQuery("UPDATE `player` SET `player_score` = $score WHERE `player_id` = $player_id");
        }

        // Update deck (remove drawn cards)
        $this->DbQuery("DELETE FROM `deck`");
        foreach ($state->deck as $position => $value) {
            $this->DbQuery("INSERT INTO `deck` (`card_value`, `card_position`) VALUES ($value, $position)");
        }
    }

    /**
     * Get player_id from player index (0 or 1)
     */
    public function getPlayerIdByIndex(int $index): int
    {
        $playersData = $this->getCollectionFromDb(
            "SELECT `player_id`, `player_no` FROM `player` WHERE `player_no` = $index"
        );
        return (int)array_key_first($playersData);
    }

    /**
     * ゲーム進行度を計算（0～100%）
     *
     * updateGameProgression = true の状態で自動的に呼び出される
     *
     * @return int 進行度（0～100）
     */
    public function getGameProgression(): int
    {
        $state = $this->loadState();
        $totalCards = 10;
        $remainingCards = count($state->deck);
        $cardsDrawn = $totalCards - $remainingCards;

        return (int)(($cardsDrawn / $totalCards) * 100);
    }

    /**
     * データベーススキーマのマイグレーション
     *
     * ゲームが公開された後、スキーマ変更時に使用
     *
     * @param int $from_version 移行元のバージョン番号
     */
    public function upgradeTableDb($from_version): void
    {
        // 例: バージョン1404301345以前からのマイグレーション
        // if ($from_version <= 1404301345) {
        //     $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ADD COLUMN ...";
        //     $this->applyDbUpgradeToAllDB($sql);
        // }
    }

    /**
     * 現在のゲーム状況を取得
     *
     * ゲーム画面表示時（開始時、リフレッシュ時）に呼び出される
     * 現在のプレイヤーから見える情報のみを返す
     *
     * @return array ゲームデータ
     */
    protected function getAllDatas(): array
    {
        $result = [];
        $current_player_id = (int) $this->getCurrentPlayerId();

        // プレイヤー情報を取得
        $result['players'] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score`, `player_no` FROM `player`"
        );

        // Load game state
        $state = $this->loadState();

        // Deck size (not showing the cards themselves, just the count)
        $result['deck_size'] = count($state->deck);

        // Active player index
        $result['active_player_index'] = $state->active;

        return $result;
    }

    /**
     * 新規ゲームのセットアップ
     *
     * ゲーム開始時に一度だけ呼び出される
     *
     * @param array $players プレイヤー情報
     * @param array $options ゲームオプション
     * @return string 初期状態のクラス名
     */
    protected function setupNewGame($players, $options = []): string
    {
        // プレイヤーカラーを設定
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        $query_values = [];
        $player_no = 0;
        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player['player_canal'],
                addslashes($player['player_name']),
                addslashes($player['player_avatar']),
                $player_no++,  // Assign player_no sequentially (0, 1, ...)
            ]);
        }

        // プレイヤーをデータベースに登録
        static::DbQuery(sprintf(
            "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_no) VALUES %s",
            implode(',', $query_values)
        ));

        // お気に入りカラーで再割り当て
        $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        $this->reloadPlayersBasicInfos();

        // 統計情報の初期化
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // ========================================
        // Functional Core: Create initial state
        // ========================================
        $initialState = GameLogic::create_initial_state();

        // ========================================
        // Imperative Shell: Save state to DB
        // ========================================

        // Save deck to database
        foreach ($initialState->deck as $position => $value) {
            $this->DbQuery("INSERT INTO `deck` (`card_value`, `card_position`) VALUES ($value, $position)");
        }

        // Set initial scores (already 0 by default, so no need to update)
        // Player scores in $initialState->players are [0, 0]

        // Active player: Set first player as active
        // The initial state has active=0, so we need to activate player_no=0
        $firstPlayerId = $this->getPlayerIdByIndex(0);
        $this->gamestate->changeActivePlayer($firstPlayerId);

        return PlayerTurn::class;
    }

    // ========================================
    // デバッグ機能（Studioの「Debug」ボタンから実行可能）
    // ========================================

    /**
     * 指定した状態にジャンプ（デバッグ用）
     *
     * @param int $state 遷移先の状態ID
     */
    public function debug_goToState(int $state = self::DEFAULT_DEBUG_STATE): void
    {
        $this->gamestate->jumpToState($state);
    }

    /**
     * 自動プレイテスト（ゾンビモードテスト用）
     *
     * @param int $moves 実行する手数
     */
    public function debug_playAutomatically(int $moves = self::DEFAULT_AUTO_PLAY_MOVES): void
    {
        $count = 0;
        $current_state_id = (int) $this->gamestate->getCurrentMainStateId();

        while ($current_state_id < self::GAME_END_STATE && $count < $moves) {
            $count++;

            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int) $playerId;
                $current_state = $this->gamestate->getCurrentState($playerId);
                $this->gamestate->runStateClassZombie($current_state, $playerId);
            }

            $current_state_id = (int) $this->gamestate->getCurrentMainStateId();
        }
    }

    /**
     * テスト用カード配置（デバッグ用）
     *
     * Deckコンポーネント使用時のテストに便利
     *
     * @param int $cardType カードタイプ
     * @param int $playerId プレイヤーID
     */
    /*
    public function debug_setCardInHand(int $cardType, int $playerId): void
    {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }
    */
}
