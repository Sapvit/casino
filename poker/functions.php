<?php
function getDeck() {
    $suits = ['clubs', 'diamonds', 'hearts', 'spades'];
    $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'jack', 'queen', 'king', 'ace'];
    $deck = [];

    foreach ($suits as $suit) {
        foreach ($ranks as $rank) {
            $deck[] = ['rank' => $rank, 'suit' => $suit, 'file' => "{$rank}_of_{$suit}.svg"];
        }
    }
    return $deck;
}

?>