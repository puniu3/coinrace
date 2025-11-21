<?php

/**
 * PlayerTurn - プレイヤーターン状態
 *
 * プレイヤーがカードをプレイするか、パスする状態
 */

declare(strict_types=1);

namespace Bga\Games\CoinRace\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\CoinRace\Game;

class PlayerTurn extends GameState
{
    // 定数定義
    private const STATE_ID = 10;
    private const SCORE_PER_CARD = 1;
    private const ENERGY_PER_PASS = 1;

    /**
     * コンストラクタ
     *
     * @param Game $game ゲームインスタンス
     */
    public function __construct(protected Game $game)
    {
        parent::__construct(
            $game,
            id: self::STATE_ID,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must play a card or pass'),
            descriptionMyTurn: clienttranslate('${you} must play a card or pass'),
        );
    }

    /**
     * ゲーム状態の引数を取得
     *
     * この状態で必要な情報を返す（プレイ可能なカードIDなど）
     *
     * @return array 状態引数
     */
    public function getArgs(): array
    {
        // TODO: データベースから現在のゲーム状況を取得
        // 例: プレイヤーの手札、プレイ可能なカードなど

        return [
            'playableCardsIds' => [1, 2],
        ];
    }

    /**
     * カードをプレイするアクション
     *
     * フロントエンドの bgaPerformAction("actPlayCard") から呼び出される
     *
     * @param int $card_id プレイするカードID
     * @param int $activePlayerId アクティブプレイヤーID
     * @param array $args 状態引数（getArgs()の戻り値）
     * @return string 次の状態クラス名
     * @throws UserException 無効なカード選択時
     */
    #[PossibleAction]
    public function actPlayCard(int $card_id, int $activePlayerId, array $args): string
    {
        // 入力値の検証
        $playableCardsIds = $args['playableCardsIds'];
        if (!in_array($card_id, $playableCardsIds, strict: true)) {
            throw new UserException('Invalid card choice');
        }

        // カード名を取得
        $card_name = Game::$CARD_TYPES[$card_id]['card_name'];

        // TODO: カードをプレイするゲームロジックを実装
        // 例: カードを手札から場に移動、効果を適用など

        // 全プレイヤーに通知
        $this->notify->all('cardPlayed', clienttranslate('${player_name} plays ${card_name}'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'card_name' => $card_name,
            'card_id' => $card_id,
            'i18n' => ['card_name'],
        ]);

        // スコア加算（この例ではカード1枚につき1点）
        $this->playerScore->inc($activePlayerId, self::SCORE_PER_CARD);

        // 次の状態へ遷移
        return NextPlayer::class;
    }

    /**
     * パスアクション
     *
     * フロントエンドの bgaPerformAction("actPass") から呼び出される
     *
     * @param int $activePlayerId アクティブプレイヤーID
     * @return string 次の状態クラス名
     */
    #[PossibleAction]
    public function actPass(int $activePlayerId): string
    {
        // 全プレイヤーに通知
        $this->notify->all('pass', clienttranslate('${player_name} passes'), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
        ]);

        // エネルギー加算（この例ではパス1回につき1エネルギー）
        $this->game->playerEnergy->inc($activePlayerId, self::ENERGY_PER_PASS);

        // 次の状態へ遷移
        return NextPlayer::class;
    }

    /**
     * ゾンビモード処理
     *
     * プレイヤーが切断した場合の自動処理
     *
     * 重要: getCurrentPlayerId() は使用しない
     * 　　　引数の $playerId を使用すること
     *
     * @param int $playerId ゾンビプレイヤーのID
     * @return string 次の状態クラス名
     * @see https://en.doc.boardgamearena.com/Zombie_Mode
     */
    public function zombie(int $playerId): string
    {
        // ゾンビレベル0: 単純にパスする
        // return $this->actPass($playerId);

        // ゾンビレベル1: ランダムにカードを選択してプレイ
        $args = $this->getArgs();
        $zombieChoice = $this->getRandomZombieChoice($args['playableCardsIds']);
        return $this->actPlayCard($zombieChoice, $playerId, $args);
    }
}
