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

use Bga\Games\CoinRace\States\PlayerTurn;
use Bga\GameFramework\Components\Counters\PlayerCounter;

class Game extends \Bga\GameFramework\Table
{
    // 定数定義
    private const DEFAULT_PLAYER_ENERGY = 2;
    private const GAME_END_STATE = 99;
    private const DEFAULT_DEBUG_STATE = 3;
    private const DEFAULT_AUTO_PLAY_MOVES = 50;

    // カードタイプ定義
    public static array $CARD_TYPES;

    // プレイヤーエネルギーカウンター
    public PlayerCounter $playerEnergy;

    /**
     * コンストラクタ
     * ゲーム状態ラベル、カウンター、カードタイプを初期化
     */
    public function __construct()
    {
        parent::__construct();

        // ゲーム状態ラベルの初期化（空でも必須）
        $this->initGameStateLabels([]);

        // プレイヤーエネルギーカウンターの作成
        $this->playerEnergy = $this->counterFactory->createPlayerCounter('energy');

        // カードタイプの定義
        self::$CARD_TYPES = [
            1 => ['card_name' => clienttranslate('Troll')],
            2 => ['card_name' => clienttranslate('Goblin')],
        ];

        // 通知デコレーター（任意）
        // プレイヤー名やカード名を自動補完する場合に有効化
        /*
        $this->notify->addDecorator(function(string $message, array $args) {
            if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
                $args['player_name'] = $this->getPlayerNameById($args['player_id']);
            }

            if (isset($args['card_id']) && !isset($args['card_name']) && str_contains($message, '${card_name}')) {
                $args['card_name'] = self::$CARD_TYPES[$args['card_id']]['card_name'];
                $args['i18n'][] = 'card_name';
            }

            return $args;
        });
        */
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
        // TODO: ゲーム進行度の計算ロジックを実装
        return 0;
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

        // プレイヤー情報を取得（dbmodel.sqlで追加したフィールドも取得可能）
        $result['players'] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

        // プレイヤーエネルギーを結果に追加
        $this->playerEnergy->fillResult($result);

        // TODO: その他のゲーム状況データを追加
        // 例: カード、ボード状態など（$current_player_idから見える情報のみ）

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
        // プレイヤーエネルギーを初期化
        $this->playerEnergy->initDb(
            array_keys($players),
            initialValue: self::DEFAULT_PLAYER_ENERGY
        );

        // プレイヤーカラーを設定
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

        // プレイヤーをデータベースに登録
        // dbmodel.sqlで追加フィールドがある場合はここで初期化
        static::DbQuery(sprintf(
            "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
            implode(',', $query_values)
        ));

        // お気に入りカラーで再割り当て
        $this->reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        $this->reloadPlayersBasicInfos();

        // 統計情報の初期化
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // TODO: 初期ゲーム状況のセットアップ
        // 例: カードデッキの作成、ボードの初期化など

        // 最初のプレイヤーをアクティブ化
        $this->activeNextPlayer();

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
