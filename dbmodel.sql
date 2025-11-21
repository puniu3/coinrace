-- CoinRace Database Schema
--
-- This file defines the database structure for the CoinRace game.
-- It will be automatically executed during game setup.

-- Deck table: stores the game deck with coin values
-- card_position: position in deck (0-based index)
-- card_value: coin value (1, 2, or 3)
CREATE TABLE IF NOT EXISTS `deck` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_value` int(10) unsigned NOT NULL,
  `card_position` int(10) unsigned NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- Note: Player scores are stored in the default 'player' table's 'player_score' column
-- Note: BGA's default 'player_no' (1-based, AUTO_INCREMENT) is used for player ordering
--       We convert to 0-based index in the code by subtracting 1
-- Note: Active player is managed by BGA's built-in game state mechanism
