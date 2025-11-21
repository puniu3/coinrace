from dataclasses import dataclass, field, replace
from typing import List, Literal, Annotated
from random import sample

# ====== Aliases ======

Score = Annotated[int, 'non-negative']
PlayerIndex = Annotated[int, '0-based']
Coin = Literal[1, 2, 3]

# ====== UI->Logic ======

class DrawAction: ...
Action = DrawAction #| PlayAction, etc.

# ====== Logic->UI ======

@dataclass(slots=True, frozen=True)
class CoinAcquired:
    player_id : PlayerIndex
    amount : Coin

Message = CoinAcquired #| ErorrMessage, etc.

@dataclass(slots=True, frozen=True)
class View:
    players: List[Score] 
    active: PlayerIndex
    deck_size: int
    msg : List[Message]

# ====== Logic ======

@dataclass(slots=True, frozen=True)
class State:
    players: List[Score] 
    active: PlayerIndex
    deck: List[Coin]
    msg : List[Message]
    
def create_initial_state() -> State:
    CARDS = [1, 1, 1, 1, 2, 2, 2, 3, 3, 3]
    deck = sample(CARDS, len(CARDS))
    return State(
        players = [0, 0],
        active = 0,
        deck = deck,
        msg = []
    )
    
def advance(state: State, action: Action) -> State:
    if isinstance(action, DrawAction):
        coin = state.deck[0]
        players = [p + coin if i == state.active else p for i, p in enumerate(state.players)]
        active = (state.active + 1) % 2
        deck = state.deck[1:]
        msg = state.msg + [CoinAcquired(player_id=state.active, amount=coin)]
        
        return State(players, active, deck, msg)
    
    return state
    
def is_over(state: State) -> bool:
    return len(state.deck) == 0
    
# ====== Facade ======

@dataclass(slots=True)
class Game:
    history: List["State"] = field(default_factory=lambda: [create_initial_state()])

    def execute(self, action: "Action") -> None:
        s = self.history[-1]
        s = advance(replace(s, msg=[]), action)
        self.history.append(s)

    @property
    def view(self) -> "View":
        s = self.history[-1]
        return View(
            players = s.players,
            active = s.active,
            deck_size = len(s.deck),
            msg = s.msg,
        )

    @property
    def is_over(self) -> bool:
        return is_over(self.history[-1])
        
# ====== UI ======

def render(view: View) -> str:
    return view #TODO pretty print
    
def get_action(view: View) -> Action:
    return DrawAction() #TODO: pretty prompt

def main():
    game = Game()
    print(render(game.view))
    
    while not game.is_over:
        action = get_action(game.view)
        game.execute(action)
        print(render(game.view))
        
    print('======GAME OVER======')
    
if __name__ == '__main__': main()
