<?php
session_start();

$validColors = ['red', 'blue', 'green', 'yellow'/*, 'wild'*/];
$validValues = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'skip', 'reverse', 'draw2', 'draw4','draw8', 'draw40'];

// Helper function to save the game state to a file
function saveGameState() {
    file_put_contents('game_state.json', json_encode($_SESSION['game']));
}

/**
 * Removes an element from an array
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
    global $validColors;
    global $validValues;
    //$colors = ['red', 'green', 'blue', 'yellow'];
    //$values = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'skip', 'reverse', 'draw2', 'draw4','draw8', 'draw40'];
    $deck = [];
    

    foreach ($validColors as $color) {
        foreach ($validValues as $value) {
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
        sortHand($player);
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

            sortHand($playerId);

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

function isCard($card){
    if (is_string($card)) {
        $cardComponents = explode('-', $card);
        global $validColors;
        global $validValues;
        return count($cardComponents) == 2 && in_array($cardComponents[0], $validColors) && in_array($cardComponents[1], $validValues);
    } 
    return false;
}

/**
 * Inserts an item into an array, at an index. Why isn't this built-in?
 * @param array $arr
 * @param mixed $item 
 * @param int $index
 * @return void
 */
function array_insert(array &$arr, $item, int $index) {
    $arrLength = count($arr);

    if ($index < 0 || $index > $arrLength) {
        throw new Exception("Index out of range. Index must be between 0 and " + $arrLength);
    }
    if ($index == -1) {
        $index = $arrLength;
    }

    array_splice($arr, $index, 0, $item);
}

function sortHand($playerId) {
    //check if it's the player's turn
    if ($playerId == $_SESSION['game']['current_turn']) {
        //Only need to sort the hand if it has at least two cards
        if (count($_SESSION['game']['players'][$playerId]['hand']) >= 2) {
            bucketHand($_SESSION['game']['players'][$playerId]['hand']);
            saveGameState();
        }
    }
}

function sortingVal($cardVal) {
    global $validValues;
    return array_search($cardVal, $validValues);
}

/**
 * Returns an array [cardColor, cardVal] if the card is valid
 * @param mixed $card
 * @return array|bool
 */
function getCardComponents($card) {
    if (isCard($card)) {
        return explode('-', $card);
    }
    else {
        return $card + " isn't a valid card.";
    }
}

//Helper function that sorts a hand into four sorted arrays, one of each color
function bucketHand(&$hand){
    if (is_array($hand)) {
        $red = [];
        $blue = [];
        $yellow = [];
        $green = [];

        // An array of array[2] where [0] is the color and [1] is the value
        $cardComponents = array_map('getCardComponents', $hand);

        //sort the hand by value
        uasort($cardComponents, function($a, $b) {
            return sortingVal($a) - sortingVal($b);
        });

        foreach ($cardComponents as $card) {
            //If new colors (like wild) are added, this function will need to be modified
            //$cardComponents = getCardComponents($card);
            switch ($card[0]) {
                case "red":
                    $red[] = $card[0].'-'.$card[1];
                    break;
                case "blue":
                    $blue[] = $card[0].'-'.$card[1];
                    break;
                case "yellow":
                    $yellow[] = $card[0].'-'.$card[1];
                    break;
                case "green":
                    $green[] = $card[0].'-'.$card[1];
                    break;
            }
        }

        $hand = array_merge($red, $yellow, $green, $blue);

    } else {
        return "bucketHand only accepts arrays of cards. You tried to pass in a " + gettype($hand);
    }
}

//returns the id of the player whose turn is next
function nextPlayer() {
    return strval(((($_SESSION['game']['current_turn'] + (1 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players']));
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


                             //---------------------------------------//
                            //        god's ugliest php code         //
                           //---------------------------------------//

    
                           
    else if (explode('-', $card)[1] == "draw2") {
        $nextPlayer = nextPlayer();
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        sortHand($nextPlayer);
    }

    else if(explode('-', $card)[1] == "draw4") {
        $nextPlayer = nextPlayer();
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        sortHand($nextPlayer);
    }

    else if(explode('-', $card)[1] == "draw8") {
        $nextPlayer = nextPlayer();
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        sortHand($nextPlayer);
    }

    else if(explode('-', $card)[1] == "draw40") {
        $nextPlayer = nextPlayer();
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        $_SESSION['game']['players'][$nextPlayer]['hand'][] = array_pop($_SESSION['game']['deck']);
        sortHand($nextPlayer);
    }

    else if(explode('-', $card)[1] == "skip") {
        $_SESSION['game']['current_turn'] = ((($_SESSION['game']['current_turn'] + (2 * $_SESSION['game']['direction'])) % count($_SESSION['game']['players'])) + count($_SESSION['game']['players'])) % count($_SESSION['game']['players']);
    } 
    
    // Move to the next player
    else {
        $_SESSION['game']['current_turn'] = nextPlayer();
    }
    saveGameState();
    return "Card played!";
}

//Check if the move is valid (card matches color or value)
function isValidMove($card, $topCard) {
    //$cardColor = explode('-', $card)[0];
    //$cardValue = explode('-', $card)[1];
    $cardArr = explode('-', $card);
    $cardColor = $cardArr[0];
    $cardValue = $cardArr[1];
    //$topCardColor = explode('-', $topCard)[0];
    //$topCardValue = explode('-', $topCard)[1];
    $topCardArr = explode('-', $topCard);
    $topCardColor = $topCardArr[0];
    $topCardValue = $topCardArr[1];

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
                if (isset( $_POST[ 'playerId' ] ) ) {
                    echo (drawTurn($_POST['playerId']));
                }
                break;
            case 'state':
                // Sort hand and then respond with the current game state
                echo json_encode($_SESSION['game']);
                break;
            
            case 'test':
                if (isset($_POST['playerId']) && isset($_POST['card']) && isset($_POST['index'])) {
                    //echo isCard($_POST['card']);
                    //array_insert($_SESSION['game']['players'][$_POST['playerId']]['hand'], $_POST['card'], $_POST['index']);
                    //echo implode($_SESSION['game']['players'][$_POST['playerId']]['hand']);
                    sortHand($_POST['playerId']);
                    echo "Your cards are:".implode($_SESSION['game']['players'][$_POST['playerId']]['hand']);
                    saveGameState();
                    break;
                }
        }
    }
}


?>


