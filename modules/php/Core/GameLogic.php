<?php
declare(strict_types=1);

namespace Bga\Games\CoinRace\Core;

/**
 * GameLogic - Functional Core
 *
 * Pure functions for game logic. No side effects, no database access.
 * This is the functional core of the functional core / imperative shell architecture.
 */
class GameLogic
{
    /**
     * Create initial game state
     *
     * @return State Initial state with shuffled deck
     */
    public static function create_initial_state(): State
    {
        $CARDS = [1, 1, 1, 1, 2, 2, 2, 3, 3, 3];

        // Shuffle the deck
        $deck = $CARDS;
        shuffle($deck);

        return new State(
            players: [0, 0], // Initial scores for player 0 and 1
            active: 0,       // Player 0 starts
            deck: $deck,     // Shuffled deck
            msg: []          // No messages initially
        );
    }

    /**
     * Advance game state with an action
     *
     * Pure function: takes current state and action, returns new state.
     * Does not modify the input state.
     *
     * @param State $state Current game state
     * @param object $action Action to perform
     * @return State New game state after applying action
     */
    public static function advance(State $state, object $action): State
    {
        // Clone state to avoid mutations
        $nextState = clone $state;

        // Reset messages (messages are per-turn)
        $nextState->msg = [];

        if ($action instanceof DrawAction) {
            if (empty($nextState->deck)) {
                return $state; // No cards left, return unchanged state
            }

            // Draw top card
            $coin = $nextState->deck[0];

            // Remove drawn card from deck
            $nextState->deck = array_slice($nextState->deck, 1);

            // Update active player's score
            $nextState->players[$nextState->active] += $coin;

            // Add message about coin acquisition
            $nextState->msg[] = new CoinAcquired(
                player_id: $nextState->active,
                amount: $coin
            );

            // Switch to next player
            $nextState->active = ($nextState->active + 1) % 2;

            return $nextState;
        }

        return $state;
    }

    /**
     * Check if game is over
     *
     * @param State $state Current game state
     * @return bool True if game is over (deck is empty)
     */
    public static function is_over(State $state): bool
    {
        return count($state->deck) === 0;
    }
}
