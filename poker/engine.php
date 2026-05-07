<?php
header('Content-Type: application/json');
set_time_limit(10);

$data = json_decode(file_get_contents('php://input'), true);

class Card {
    public $r; public $s;
    public function __construct($r, $s) { $this->r = $r; $this->s = $s; }
}

$rankMap = ['2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,'jack'=>11,'queen'=>12,'king'=>13,'ace'=>14];
$suitMap = ['clubs'=>1, 'diamonds'=>2, 'hearts'=>3, 'spades'=>4];

// Улучшенный парсер: достает ранг и масть даже из сложных URL
function parse($url) {
    global $rankMap, $suitMap;
    if (!$url) return null;
    
    // Берем только имя файла без расширения и параметров
    $filename = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
    $parts = explode('_of_', $filename);
    
    if (count($parts) !== 2) return null;
    
    $r = $rankMap[$parts[0]] ?? null;
    $s = $suitMap[$parts[1]] ?? null;
    
    return ($r && $s) ? new Card($r, $s) : null;
}

// 1. Сбор данных
$hero = array_map('parse', array_filter($data['hero']));
$board = array_map('parse', array_filter($data['board']));
$opponents = [];
foreach ($data['opponents'] as $opp) {
    $opponents[] = array_map('parse', array_filter($opp));
}

// 2. Создание колоды
$deadCards = [];
foreach (array_merge($hero, $board) as $c) $deadCards["{$c->r}-{$c->s}"] = true;
foreach ($opponents as $opp) foreach ($opp as $c) $deadCards["{$c->r}-{$c->s}"] = true;

$deck = [];
for ($r = 2; $r <= 14; $r++) {
    for ($s = 1; $s <= 4; $s++) {
        if (!isset($deadCards["$r-$s"])) $deck[] = new Card($r, $s);
    }
}

// 3. Оценщик (evaluateHand и getStraightHigh остаются твои, они логически верны)
function getStraightHigh($uniqueRanks) {
    sort($uniqueRanks);
    $uniqueRanks = array_reverse($uniqueRanks);
    $wheel = (in_array(14, $uniqueRanks) && in_array(2, $uniqueRanks) && in_array(3, $uniqueRanks) && in_array(4, $uniqueRanks) && in_array(5, $uniqueRanks)) ? 5 : 0;
    
    for ($i = 0; $i <= count($uniqueRanks) - 5; $i++) {
        if ($uniqueRanks[$i] - $uniqueRanks[$i+4] == 4) return $uniqueRanks[$i];
    }
    return $wheel;
}

function evaluateHand($cards) {
    $sCount = [1=>[], 2=>[], 3=>[], 4=>[]];
    $ranks = [];
    foreach ($cards as $c) {
        $sCount[$c->s][] = $c->r;
        $ranks[] = $c->r;
    }
    
    foreach ($sCount as $suit => $sRanks) {
        if (count($sRanks) >= 5) {
            $strHigh = getStraightHigh($sRanks);
            if ($strHigh) {
                if ($strHigh == 14) return 9000000; // Royal Flush (отдельная категория)
                return 8000000 + $strHigh; // Straight Flush
            }
            return 5000000 + max($sRanks); // Flush
        }
    }
    
    $counts = array_count_values($ranks);
    arsort($counts); // Сортируем по количеству вхождений
    
    $rKeys = array_keys($counts);
    $firstVal = reset($counts);
    $firstRank = key($counts);
    
    if ($firstVal == 4) return 7000000 + $firstRank;
    
    if ($firstVal == 3) {
        next($counts);
        if (current($counts) >= 2) return 6000000 + ($firstRank * 100) + key($counts);
        return 3000000 + $firstRank;
    }
    
    $strHigh = getStraightHigh(array_unique($ranks));
    if ($strHigh) return 4000000 + $strHigh;
    
    if ($firstVal == 2) {
        next($counts);
        if (current($counts) == 2) return 2000000 + ($firstRank * 100) + key($counts);
        return 1000000 + $firstRank;
    }
    
    return max($ranks);
}

// 4. Симуляция
$iters = 100000;
$heroResults = array_fill(0, 10, 0);
$oppResults = array_fill(0, count($opponents), array_fill(0, 10, 0));
$wins = 0;

for ($i = 0; $i < $iters; $i++) {
    shuffle($deck);
    $dIdx = 0;
    $simBoard = $board;
    while (count($simBoard) < 5) $simBoard[] = $deck[$dIdx++];
    
    $hScore = evaluateHand(array_merge($hero, $simBoard));
    $heroResults[(int)($hScore / 1000000)]++;
    
    $bestOppScore = 0;
    foreach ($opponents as $idx => $oppHand) {
        $actualOpp = (count($oppHand) == 2) ? $oppHand : [$deck[$dIdx++], $deck[$dIdx++]];
        $oScore = evaluateHand(array_merge($actualOpp, $simBoard));
        $oppResults[$idx][(int)($oScore / 1000000)]++;
        if ($oScore > $bestOppScore) $bestOppScore = $oScore;
    }
    if ($hScore > $bestOppScore) $wins++;
}

// 5. Вывод
$comboNames = ["High Card", "Pair", "Two Pair", "Three of a Kind", "Straight", "Flush", "Full House", "Four of a Kind", "Straight Flush", "Royal Flush"];
$res = ['hero' => [], 'opponents' => [], 'equity' => ($wins / $iters) * 100];

foreach ($heroResults as $s => $count) $res['hero'][] = ['name' => $comboNames[$s], 'prob' => ($count/$iters)*100];
foreach ($oppResults as $idx => $stats) {
    $temp = [];
    foreach ($stats as $s => $count) $temp[] = ['name' => $comboNames[$s], 'prob' => ($count/$iters)*100];
    $res['opponents'][] = $temp;
}
echo json_encode($res);