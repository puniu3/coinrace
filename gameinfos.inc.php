<?php

/**
 * ゲームのメタ情報設定ファイル
 *
 * このファイルを変更した後は、コントロールパネルから「ゲーム情報をリロード」をクリックしてください。
 *
 * @see http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
 */

$gameinfos = [
    // ゲーム基本情報
    'game_name' => "My Great Game",
    'publisher' => 'My Publishing Company',
    'publisher_website' => 'http://www.mypublishingcompany.com/',
    'publisher_bgg_id' => 1234,
    'bgg_id' => 0,

    // プレイ人数設定（例: 2～4人プレイ）
    'players' => [2, 3, 4],
    'suggest_player_number' => null,
    'not_recommend_player_number' => null,

    // ゲーム時間設定（分）
    'estimated_duration' => 30,

    // 追加時間設定（秒）- giveExtraTime() 呼び出し時に追加される時間
    'fast_additional_time' => 30,
    'medium_additional_time' => 40,
    'slow_additional_time' => 50,

    // タイブレーカーの説明（使用しない場合は空文字列）
    // 例: 'tie_breaker_description' => totranslate("手札の残り枚数")
    'tie_breaker_description' => "",

    // ゲームモード設定
    'losers_not_ranked' => false,       // 敗者を同順位にするか
    'solo_mode_ranked' => false,        // ソロモードをランキング対象にするか
    'is_coop' => 0,                     // 協力ゲームか（0=非協力, 1=協力）

    // 言語依存性（false=依存なし, true=全員同言語必須）
    'language_dependency' => false,

    // プレイヤーカラー（12色定義）
    'player_colors' => [
        'ff0000', // 赤
        '008000', // 緑
        '0000ff', // 青
        'ffa500', // オレンジ
        'e94190', // ピンク
        '982fff', // 紫
        '72c3b1', // ターコイズ
        'f07f16', // オレンジ2
        'bdd002', // 黄緑
        '7b7b7b', // グレー
        '000000', // 黒
        'ffffff', // 白
    ],

    // お気に入りカラー機能を有効化
    'favorite_colors_support' => true,

    // 再戦時のプレイヤー順ローテーションを無効化するか
    'disable_player_order_swap_on_rematch' => false,

    // ゲーム画面の最小幅（ピクセル）
    // 320～740の範囲で設定可能。小さいほどモバイル対応が良い
    'game_interface_width' => [
        'min' => 740,
    ],

    // 3Dモードを有効化（ゲームが3Dで正しく動作する場合のみtrue）
    'enable_3d' => false,
];
