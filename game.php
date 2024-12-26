<?php
session_start();

// Helper function to save the game state to a file
function saveGameState() {
    file_put_contents('game_state.json', json_encode($_SESSION['game']));
}

/**
 * Summary of remEl
 * @param mixed $keyword
 * @param array $arr
 * @return void
 */
function remEl($idx, $arr) {
    $newarr = [];
    foreach($arr as $val => $i) {
        if($val == $idx) {
            
        } else {
            array_push($newarr, $i);
        }
    }
    return $newarr;
}

// Helper function to load the game state from a file
function loadGameState() {
    if (file_exists('game_state.json')) {
        $_SESSION['game'] = json_decode(file_get_contents('game_state.json'), true);
    } else {
        $_SESSION['game'] = [
            'players' => [],
            'current_turn' => 0,
            'deck' => generateDeck(),
            'discard_pile' => [],
            'status' => 'waiting', // Initial game status
        ];
    }
}

// Generate the UNO deck
function generateDeck() {
    $colors = ['red', 'green', 'blue', 'yellow'];
    $values = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'skip', 'reverse', 'draw2', 'draw4','draw8', 'draw40'];
    $deck = [];

    foreach ($colors as $color) {
        foreach ($values as $value) {
            $deck[] = $color . '-' . $value;
        }
    }
    shuffle($deck);
    return $deck;
}

// Join a game (add player)
function joinGame($playerName) {
    if (count($_SESSION['game']['players']) >= 4) {
        return "Game is full!";
    }

    $_SESSION['game']['players'][] = ['name' => $playerName, 'hand' => []];
    saveGameState();
    return "Player joined!";
}

// Start the game
function startGame() {
    if (count($_SESSION['game']['players']) < 2) {
        return "Not enough players!";
    }

    foreach ($_SESSION['game']['players'] as &$player) {
        $player['hand'] = [];
        for ($i = 0; $i < 7; $i++) {
            $player['hand'][] = array_pop($_SESSION['game']['deck']);
        }
    }

    // Start with a random card on the discard pile
    $_SESSION['game']['discard_pile'][] = array_pop($_SESSION['game']['deck']);

    $_SESSION['game']['status'] = 'started';

    $_SESSION['game']['direction'] = 1;
    saveGameState();
    return "Game started!";
}

function drawTurn($playerId) {
    if ($playerId == $_SESSION['game']['current_turn']) {
        // Check if the deck is empty
        if (count($_SESSION['game']['deck']) > 0) {
            // Draw a card and add it to the player's hand
            $drawnCard = array_pop($_SESSION['game']['deck']);
            $_SESSION['game']['players'][$playerId]['hand'][] = $drawnCard;

            $_SESSION['game']['current_turn'] = ($_SESSION['game']['current_turn'] + 1) % count($_SESSION['game']['players']);
            saveGameState();
            
            return "Card drawn!";
        } else {
            return "The deck is empty!";
        }
    } else {
        return "IT'S NOT YOUR TURN!";
    }
}

// Play a turn (play a card)
function playTurn($playerId, $card) {
    if ($playerId == $_SESSION['game']['current_turn']) {
        // ...
    } else {
        return "ITS NOT YOUR TURN BRO ";
    }

    $topCard = end($_SESSION['game']['discard_pile']);
    if (!isValidMove($card, $topCard)) {
        return "You can't play that card! ( Draw if no playable cards... )";
    }

    // Play the card
    //$_SESSION['game']['players'][$playerId]['hand'] = array_diff(
    //    $_SESSION['game']['players'][$playerId]['hand'],
    //    [$card]
    //);
    //$_SESSION['game']['players'][$playerId]['hand'] = array_splice($_SESSION['game']['players'][$playerId]['hand'], array_search($card, $_SESSION['game']['players'][$playerId]['hand']),1);
    $_SESSION['game']['players'][$playerId]['hand'] = remEl(array_search($card, $_SESSION['game']['players'][$playerId]['hand']), $_SESSION['game']['players'][$playerId]['hand']);
    $_SESSION['game']['discard_pile'][] = $card;

    if(explode('-', $card)[1] == "reverse") {
        $_SESSION['game']['direction'] *= -1;
    }

    if(explode('-', $card)[1] == "draw2") {
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
    }

    if(explode('-', $card)[1] == "draw4") {
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
    }

    if(explode('-', $card)[1] == "draw8") {
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
    }

    if(explode('-', $card)[1] == "draw40") {
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players'])]['hand'][] = array_pop($_SESSION['game']['deck']);
    }

    // Move to the next player

    if(explode('-', $card)[1] == "skip") {
        $_SESSION['game']['current_turn'] = ((($_SESSION['game']['current_turn'] + (2 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players']);
    } else {
        $_SESSION['game']['current_turn'] = ((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players']);
    }
    saveGameState();
    return "Card played!";
}

// Check if the move is valid (card matches color or value)
function isValidMove($card, $topCard) {
    $cardColor = explode('-', $card)[0];
    $cardValue = explode('-', $card)[1];
    $topCardColor = explode('-', $topCard)[0];
    $topCardValue = explode('-', $topCard)[1];

    return ($cardColor === $topCardColor || $cardValue === $topCardValue);
}

// Handle incoming AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    loadGameState();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'getid':
                echo (count($_SESSION['game']['players']));
                break; 
            case 'join':
                if (isset($_POST['playerName'])) {
                    echo joinGame($_POST['playerName']);
                }
                break;
            case 'start':
                echo startGame();
                break;
            case 'play':
                if (isset($_POST['playerId']) && isset($_POST['card'])) {
                    echo (playTurn($_POST['playerId'], $_POST['card']));
                }
                break;
            case 'draw':
                if ( isset( $_POST[ 'playerId' ] ) ) {
                    echo (drawTurn($_POST['playerId']));
                }
                break;
            case 'state':
                // Respond with the current game state
                echo json_encode($_SESSION['game']);
                break;
        }
    }
}
?>
