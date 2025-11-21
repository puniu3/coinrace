<?php
/**
 * CoinRace - Entry Point
 *
 * BGAフレームワークが読み込むメインのエントリーポイント
 */

// BGAフレームワークの定数を読み込む（必要な場合）
// define('APP_GAMEMODULE_PATH', ...);

require_once("modules/php/Game.php");

class coinrace extends \Bga\Games\CoinRace\Game {
    // このクラスは modules/php/Game.php で定義されたロジックを継承するだけのプロキシ
}
