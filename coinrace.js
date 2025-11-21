/**
 * CoinRace - ユーザーインターフェース
 *
 * @author <Your name here> <Your email address here>
 * @copyright Board Game Arena
 * @see http://en.boardgamearena.com/#!doc/Studio
 */

define([
    "dojo",
    "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare, gamegui, counter) {
    return declare("bgagame.coinrace", ebg.core.gamegui, {

        /**
         * コンストラクタ
         * UIのグローバル変数を初期化
         */
        constructor: function() {
            console.log('coinrace constructor');

            // グローバル変数の初期化
            // 例: this.myGlobalValue = 0;
        },

        /**
         * ゲーム画面のセットアップ
         *
         * ゲーム画面が表示される度に呼び出される:
         * - ゲーム開始時
         * - プレイヤーがページをリフレッシュ時(F5)
         *
         * @param {Object} gamedatas - getAllDatas()で取得したゲームデータ
         */
        setup: function(gamedatas) {
            console.log("Starting game setup");

            // ゲームエリアにゲーム情報を追加
            this.getGameAreaElement().insertAdjacentHTML('beforeend', `
                <div id="game-area">
                    <div id="deck-info">
                        <h3>Deck</h3>
                        <div>Cards remaining: <span id="deck-size">${gamedatas.deck_size}</span></div>
                    </div>
                    <div id="player-scores"></div>
                </div>
            `);

            // プレイヤースコアの表示
            Object.values(gamedatas.players).forEach(player => {
                document.getElementById('player-scores').insertAdjacentHTML('beforeend', `
                    <div class="player-score">
                        <strong>${player.name}:</strong> <span id="score-${player.id}">${player.score}</span> points
                    </div>
                `);
            });

            // 通知ハンドラーのセットアップ
            this.setupNotifications();

            console.log("Ending game setup");
        },


        // ========================================
        // ゲーム状態管理
        // ========================================

        /**
         * ゲーム状態突入時の処理
         *
         * 新しいゲーム状態に入る度に呼び出される
         *
         * @param {string} stateName - 状態名
         * @param {Object} args - 状態引数
         */
        onEnteringState: function(stateName, args) {
            console.log('Entering state: ' + stateName, args);

            switch (stateName) {
                // 例:
                // case 'myGameState':
                //     // この状態でHTMLブロックを表示
                //     dojo.style('my_html_block_id', 'display', 'block');
                //     break;

                case 'dummy':
                    break;
            }
        },

        /**
         * ゲーム状態退出時の処理
         *
         * ゲーム状態を抜ける度に呼び出される
         *
         * @param {string} stateName - 状態名
         */
        onLeavingState: function(stateName) {
            console.log('Leaving state: ' + stateName);

            switch (stateName) {
                // 例:
                // case 'myGameState':
                //     // この状態で表示していたHTMLブロックを非表示
                //     dojo.style('my_html_block_id', 'display', 'none');
                //     break;

                case 'dummy':
                    break;
            }
        },

        /**
         * アクションボタンの更新
         *
         * アクションステータスバーに表示するボタンを管理
         *
         * @param {string} stateName - 状態名
         * @param {Object} args - 状態引数
         */
        onUpdateActionButtons: function(stateName, args) {
            console.log('onUpdateActionButtons: ' + stateName, args);

            // 現在のプレイヤーがアクティブな場合のみ
            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'PlayerTurn':
                        // Draw coin button
                        this.statusBar.addActionButton(
                            _('Draw Coin'),
                            () => this.onDrawCoin()
                        );
                        break;
                }
            }
        },


        // ========================================
        // ユーティリティメソッド
        // ========================================

        // TODO: ここに共通で使用するユーティリティメソッドを定義
        // 例: カード移動、アニメーション、計算処理など


        // ========================================
        // プレイヤーアクション
        // ========================================

        /**
         * Draw coin action
         */
        onDrawCoin: function() {
            console.log('onDrawCoin');

            // Send action to server
            this.bgaPerformAction("actDrawCoin").then(() => {
                // Success - handled by notifications
            });
        },


        // ========================================
        // 通知処理（CometD）
        // ========================================

        /**
         * 通知ハンドラーのセットアップ
         *
         * ゲーム通知とローカルメソッドを関連付ける
         * PHPの notifyAllPlayers/notifyPlayer に対応
         */
        setupNotifications: function() {
            console.log('notifications subscriptions setup');

            // notif_xxx という名前のメソッドを自動検出して登録
            this.bgaSetupPromiseNotifications();
        },

        /**
         * Coin acquired notification handler
         *
         * @param {Object} args - Notification arguments
         */
        notif_coinAcquired: async function(args) {
            console.log('notif_coinAcquired', args);

            // Update player score
            const scoreElement = document.getElementById('score-' + args.player_id);
            if (scoreElement) {
                scoreElement.textContent = args.score;
            }

            // Update deck size (decrease by 1)
            const deckSizeElement = document.getElementById('deck-size');
            if (deckSizeElement) {
                const currentSize = parseInt(deckSizeElement.textContent);
                deckSizeElement.textContent = currentSize - 1;
            }
        },
    });
});
