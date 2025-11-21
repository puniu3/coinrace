
-- ------
-- BGA framework: usually you will not need to modify this section
-- ------

-- Table for storing the functional core state as a serialized object
CREATE TABLE IF NOT EXISTS `global_state` (
  `key` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
