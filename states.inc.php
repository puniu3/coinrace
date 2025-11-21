<?php
/**
 * CoinRace - Game States
 *
 * BGA uses this file to define the state machine.
 */

$machinestates = [
    // The initial state. Please do not modify.
    1 => [
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => [ "" => 10 ] // Start with PlayerTurn (10)
    ],

    // Active Player Turn
    10 => [
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must draw a coin'),
        "descriptionmyturn" => clienttranslate('${you} must draw a coin'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn", // Optional, if we want to pass args
        "possibleactions" => [ "actDraw" ],
        "transitions" => [
            "Bga\Games\CoinRace\States\NextPlayer" => 90,
            "zombie" => 99
        ]
    ],

    // Next Player / Game Loop
    90 => [
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => [
            "Bga\Games\CoinRace\States\PlayerTurn" => 10,
            "Bga\Games\CoinRace\States\EndScore" => 98,
            "endGame" => 99
        ]
    ],

    // End Score Calculation
    98 => [
        "name" => "endScore",
        "description" => "",
        "type" => "game",
        "action" => "stEndScore",
        "transitions" => [
            "endGame" => 99
        ]
    ],

    // Final state. Please do not modify.
    99 => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ]
];
