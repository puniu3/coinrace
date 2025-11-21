<?php
/**
 * CoinRace - Action Entry Point
 *
 * クライアント(JS)からのアクションリクエストを受け取るエントリーポイント
 */

$action = 'coinrace'; // ゲーム名

require_once("modules/php/Game.php");

class action_coinrace extends \Bga\GameFramework\Actions\Action
{
    public function __construct() {
        parent::__construct();
        // ここでゲームインスタンスを生成する場合もあるが、
        // BGAフレームワークが自動的に呼び出すメソッドで処理される
    }

    // 必要に応じてアクションのルーティングや前処理を記述
    // 現在のBGAの仕様では、States/ 以下にアクションメソッドがあれば自動的にマッピングされるため、
    // ここは空でも動作する場合が多い、あるいは定型的な記述が必要
}
