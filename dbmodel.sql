
-- ========================================
-- CoinRace - データベーススキーマ定義
-- ========================================
--
-- @author <Your name here> <Your email address here>
-- @copyright Board Game Arena
-- @see http://en.boardgamearena.com/#!doc/Studio
--
-- このファイルについて:
-- - ゲーム専用のデータベーステーブルを定義
-- - PhpMyAdminからエクスポートした構造をそのまま貼り付け可能
-- - ファイル変更後は、ゲームを再起動して反映
--
-- 注意:
-- - データベース本体と標準テーブルは自動作成される（作成不要）
--   標準テーブル: global, stats, gamelog, player
--

-- ========================================
-- カスタムテーブルの例
-- ========================================

-- 例1: Deckコンポーネント用の標準カードテーブル
-- (BGAの"hearts"ゲームを参照)
--
-- CREATE TABLE IF NOT EXISTS `card` (
--     `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'カードID',
--     `card_type` varchar(16) NOT NULL COMMENT 'カードタイプ（スート等）',
--     `card_type_arg` int(11) NOT NULL COMMENT 'カードタイプ引数（数値等）',
--     `card_location` varchar(16) NOT NULL COMMENT 'カードの場所（deck, hand, play等）',
--     `card_location_arg` int(11) NOT NULL COMMENT '場所の引数（プレイヤーID等）',
--     PRIMARY KEY (`card_id`),
--     KEY `card_type` (`card_type`),
--     KEY `card_location` (`card_location`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='ゲームカード管理テーブル';


-- 例2: 標準プレイヤーテーブルにカスタムフィールドを追加
--
-- ALTER TABLE `player`
--     ADD `player_resource_wood` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '木材リソース',
--     ADD `player_resource_stone` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '石材リソース';


-- ========================================
-- TODO: ここにゲーム専用のテーブルを定義
-- ========================================

-- 例: ゲームボード上のタイルテーブル
--
-- CREATE TABLE IF NOT EXISTS `tile` (
--     `tile_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'タイルID',
--     `tile_type` varchar(16) NOT NULL COMMENT 'タイルタイプ',
--     `tile_position_x` int(11) NOT NULL COMMENT 'X座標',
--     `tile_position_y` int(11) NOT NULL COMMENT 'Y座標',
--     `tile_owner` int(10) unsigned DEFAULT NULL COMMENT '所有プレイヤーID',
--     PRIMARY KEY (`tile_id`),
--     KEY `tile_position` (`tile_position_x`, `tile_position_y`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='ゲームボードタイル';

