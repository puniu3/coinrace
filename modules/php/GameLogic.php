<?php
declare(strict_types=1);

namespace Bga\Games\CoinRace\Core;

/* * ====== Types / DTOs (Pythonのdataclass相当) ====== 
 * 本来は別ファイルに分けるのもありですが、
 * 視認性を良くするためここにまとめています。
 */

class DrawAction {
    // マーカークラス（中身なし）
}

class CoinAcquired {
    public function __construct(
        public int $player_id,
        public int $amount
    ) {}
}

class State {
    /**
     * @param int[] $players Index based scores [0 => score, 1 => score]
     * @param int $active PlayerIndex
     * @param int[] $deck List of coins
     * @param object[] $msg List of Messages
     */
    public function __construct(
        public array $players,
        public int $active,
        public array $deck,
        public array $msg
    ) {}
}

/* * ====== Logic (Pure Functions) ====== 
 */

class GameLogic
{
    /**
     * Python: create_initial_state()
     */
    public static function create_initial_state(): State
    {
        $CARDS = [1, 1, 1, 1, 2, 2, 2, 3, 3, 3];
        
        // PHPにはsampleがないので、shuffleして使う
        $deck = $CARDS;
        shuffle($deck);

        return new State(
            players: [0, 0], // player_id 0 and 1
            active: 0,
            deck: $deck,
            msg: []
        );
    }

    /**
     * Python: advance(state, action)
     */
    public static function advance(State $state, object $action): State
    {
        // Pythonの replace のように、元のStateを変更せず新しいStateを作るため clone する
        $nextState = clone $state;
        
        // メッセージはターンごとにリセットされる設計のようなので空にする
        // (Pythonコードの Game.execute で replace(s, msg=[]) している部分の代用)
        $nextState->msg = [];

        if ($action instanceof DrawAction) {
            if (empty($nextState->deck)) {
                return $state; // デッキ切れなら何もしない（あるいは例外）
            }

            // coin = state.deck[0]
            $coin = $nextState->deck[0];
            
            // deck = state.deck[1:]
            // array_sliceで先頭を削る
            $nextState->deck = array_slice($nextState->deck, 1);

            // Update scores
            // players = [p + coin if i == state.active else p ...]
            $nextState->players[$nextState->active] += $coin;

            // Add Message
            $nextState->msg[] = new CoinAcquired(
                player_id: $nextState->active,
                amount: $coin
            );

            // Switch Active Player
            // active = (state.active + 1) % 2
            $nextState->active = ($nextState->active + 1) % 2;

            return $nextState;
        }

        return $state;
    }

    /**
     * Python: is_over(state)
     */
    public static function is_over(State $state): bool
    {
        return count($state->deck) === 0;
    }
}
