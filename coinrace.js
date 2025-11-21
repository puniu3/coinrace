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

        constructor: function() {
            console.log('coinrace constructor');
        },

        setup: function(gamedatas) {
            console.log("Starting game setup", gamedatas);

            // デッキ情報の表示
            this.getGameAreaElement().insertAdjacentHTML('beforeend', `
                <div id="game-table">
                    <div id="deck-container">
                        <strong>Deck Size:</strong> <span id="deck-size">${gamedatas.deck_size}</span>
                    </div>
                    <div id="player-tables"></div>
                </div>
            `);

            // プレイヤーボードのセットアップ
            Object.values(gamedatas.players).forEach(player => {
                // 各プレイヤーのテーブルエリア
                document.getElementById('player-tables').insertAdjacentHTML('beforeend', `
                    <div id="player-table-${player.id}" class="player-table whiteblock">
                        <h3 style="color:#${player.color}">${player.name}</h3>
                        <div class="score-display">Score: <span id="score-${player.id}">${player.score}</span></div>
                    </div>
                `);
            });

            this.setupNotifications();
            console.log("Ending game setup");
        },

        onEnteringState: function(stateName, args) {
            console.log('Entering state: ' + stateName, args);
        },

        onLeavingState: function(stateName) {
            console.log('Leaving state: ' + stateName);
        },

        onUpdateActionButtons: function(stateName, args) {
            console.log('onUpdateActionButtons: ' + stateName, args);

            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'PlayerTurn':
                        this.statusBar.addActionButton(
                            'btnDraw',
                            _('Draw Coin'),
                            () => this.onDrawClick(),
                            { color: 'primary' }
                        );
                        break;
                }
            }
        },

        onDrawClick: function() {
            if (!this.checkAction('actDraw')) return;

            this.bgaPerformAction("actDraw", {}).then(() => {
                // 成功時の処理（通知でUI更新されるので基本不要）
            });
        },

        setupNotifications: function() {
            console.log('notifications subscriptions setup');

            dojo.subscribe('coinAcquired', this, "notif_coinAcquired");

            // スコア更新などの標準通知も必要なら購読（BGAが自動でやる部分もあるが）
            this.bgaSetupPromiseNotifications();
        },

        notif_coinAcquired: function(notif) {
            console.log('notif_coinAcquired', notif);

            // デッキサイズ更新
            if (notif.args.deck_size !== undefined) {
                document.getElementById('deck-size').innerHTML = notif.args.deck_size;
            }

            // スコア更新
            if (notif.args.new_score !== undefined && notif.args.player_id) {
                const scoreEl = document.getElementById(`score-${notif.args.player_id}`);
                if (scoreEl) {
                    scoreEl.innerHTML = notif.args.new_score;
                }

                // プレイヤーパネルのスコアも更新
                this.scoreCtrl[notif.args.player_id].toValue(notif.args.new_score);
            }
        }
    });
});
