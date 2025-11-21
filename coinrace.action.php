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
    }

    public function actDraw()
    {
        self::setAjaxMode();

        // アクションの実行
        $this->game->actDraw();

        self::ajaxResponse();
    }
}
