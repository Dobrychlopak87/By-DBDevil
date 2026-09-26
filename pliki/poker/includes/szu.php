<?php
/**
 * Wielki Szu — komputerowy przeciwnik do Texas Hold'em.
 *
 * Działa w całości lokalnie (czysty PHP, bez usług i zasobów sieciowych):
 *  - szybka ocena układów 5–7 kart,
 *  - symulacja Monte Carlo przeciwko ZAKRESOM rąk przeciwników (a nie losowym kartom),
 *    zawężanym bayesowsko na podstawie ich akcji w bieżącym rozdaniu,
 *  - model przeciwnika budowany z historii rozdań (VPIP, PFR, agresja, pasowanie na zakład),
 *  - strategia uwzględniająca pozycję, pot odds, implied odds, wielkość stosów, fakturę stołu,
 *    blef/semi-blef, slowplay oraz losowe mieszanie decyzji (trudny do przewidzenia).
 *
 * Uczciwość: Szu zna wyłącznie własne karty, karty wspólne i publiczne akcje — nigdy talii ani kart rywali.
 */
declare(strict_types=1);

require_once __DIR__ . '/szu_preflop.php';

const SZU_NAME = 'Wielki Szu';
const SZU_MAX_BOTS_TOTAL = 90;       // limit kont komputera w całej instalacji
const SZU_THINK_MIN = 1.2;           // s — „namysł”, aby gra wyglądała naturalnie
const SZU_THINK_MAX = 2.9;
const SZU_TIME_BUDGET = 0.35;        // s — maksymalny czas obliczeń jednej decyzji
const SZU_MAX_SIMS = 14000;
const SZU_MIN_SIMS = 400;

// ---------------------------------------------------------------------------
// Karty i ocena układów
// ---------------------------------------------------------------------------

function szuCardInt(string $card): int
{
    static $ranks = array('2' => 0, '3' => 1, '4' => 2, '5' => 3, '6' => 4, '7' => 5, '8' => 6, '9' => 7, 'T' => 8, 'J' => 9, 'Q' => 10, 'K' => 11, 'A' => 12);
    static $suits = array('s' => 0, 'h' => 1, 'd' => 2, 'c' => 3);
    return $ranks[$card[0]] * 4 + $suits[$card[1]];
}

/** @param array<int,string> $cards @return array<int,int> */
function szuCardsToInts(array $cards): array
{
    $out = array();
    foreach ($cards as $card) {
        $out[] = szuCardInt((string)$card);
    }
    return $out;
}

/**
 * Ocena najlepszego układu z 5–7 kart (int: kategoria << 20 | starszeństwo kart).
 * Kategorie: 8 poker, 7 kareta, 6 full, 5 kolor, 4 strit, 3 trójka, 2 dwie pary, 1 para, 0 wysoka karta.
 * @param array<int,int> $c
 */
function szuEval(array $c): int
{
    $rc = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    $sc = array(0, 0, 0, 0);
    $sm = array(0, 0, 0, 0);
    $rm = 0;
    foreach ($c as $card) {
        $r = $card >> 2;
        $s = $card & 3;
        $rc[$r]++;
        $sc[$s]++;
        $sm[$s] |= 1 << $r;
        $rm |= 1 << $r;
    }
    for ($s = 0; $s < 4; $s++) {
        if ($sc[$s] >= 5) {
            $m = $sm[$s];
            for ($h = 12; $h >= 4; $h--) {
                $need = 0x1F << ($h - 4);
                if (($m & $need) === $need) {
                    return (8 << 20) | ($h << 16);
                }
            }
            if (($m & 0x100F) === 0x100F) {
                return (8 << 20) | (3 << 16);
            }
            $v = 5 << 20;
            $k = 0;
            for ($r = 12; $r >= 0 && $k < 5; $r--) {
                if (($m >> $r) & 1) {
                    $v |= $r << (16 - 4 * $k);
                    $k++;
                }
            }
            return $v;
        }
    }
    $q = -1; $t1 = -1; $t2 = -1; $p1 = -1; $p2 = -1;
    for ($r = 12; $r >= 0; $r--) {
        $n = $rc[$r];
        if ($n === 4) { $q = $r; }
        elseif ($n === 3) { if ($t1 < 0) { $t1 = $r; } elseif ($t2 < 0) { $t2 = $r; } }
        elseif ($n === 2) { if ($p1 < 0) { $p1 = $r; } elseif ($p2 < 0) { $p2 = $r; } }
    }
    if ($q >= 0) {
        $k = 0;
        for ($r = 12; $r >= 0; $r--) { if ($r !== $q && $rc[$r]) { $k = $r; break; } }
        return (7 << 20) | ($q << 16) | ($k << 12);
    }
    if ($t1 >= 0 && ($t2 >= 0 || $p1 >= 0)) {
        return (6 << 20) | ($t1 << 16) | (max($t2, $p1) << 12);
    }
    for ($h = 12; $h >= 4; $h--) {
        $need = 0x1F << ($h - 4);
        if (($rm & $need) === $need) {
            return (4 << 20) | ($h << 16);
        }
    }
    if (($rm & 0x100F) === 0x100F) {
        return (4 << 20) | (3 << 16);
    }
    if ($t1 >= 0) {
        $v = (3 << 20) | ($t1 << 16);
        $k = 0;
        for ($r = 12; $r >= 0 && $k < 2; $r--) { if ($r !== $t1 && $rc[$r]) { $v |= $r << (12 - 4 * $k); $k++; } }
        return $v;
    }
    if ($p1 >= 0 && $p2 >= 0) {
        $k = 0;
        for ($r = 12; $r >= 0; $r--) { if ($r !== $p1 && $r !== $p2 && $rc[$r]) { $k = $r; break; } }
        return (2 << 20) | ($p1 << 16) | ($p2 << 12) | ($k << 8);
    }
    if ($p1 >= 0) {
        $v = (1 << 20) | ($p1 << 16);
        $k = 0;
        for ($r = 12; $r >= 0 && $k < 3; $r--) { if ($r !== $p1 && $rc[$r]) { $v |= $r << (12 - 4 * $k); $k++; } }
        return $v;
    }
    $v = 0;
    $k = 0;
    for ($r = 12; $r >= 0 && $k < 5; $r--) { if ($rc[$r]) { $v |= $r << (16 - 4 * $k); $k++; } }
    return $v;
}

/** Klasa ręki startowej, np. „AKs”, „T9o”, „77”. */
function szuHandClass(int $a, int $b): string
{
    static $names = '23456789TJQKA';
    $ra = $a >> 2;
    $rb = $b >> 2;
    if ($ra < $rb) { $t = $ra; $ra = $rb; $rb = $t; }
    if ($ra === $rb) {
        return $names[$ra] . $names[$rb];
    }
    return $names[$ra] . $names[$rb] . ((($a & 3) === ($b & 3)) ? 's' : 'o');
}

/** Percentyl siły ręki startowej (0 = najlepsza, 1 = najsłabsza), z pamięcią podręczną 52×52. */
function szuPercentile(int $a, int $b): float
{
    static $cache = array();
    $key = $a < $b ? $a * 52 + $b : $b * 52 + $a;
    if (!isset($cache[$key])) {
        $row = SZU_PREFLOP[szuHandClass($a, $b)] ?? array(0.5, 0.33, 0.25, 0.5);
        $cache[$key] = (float)$row[3];
    }
    return $cache[$key];
}

/** Kategoria samych kart wspólnych na podstawie powtórzeń rang (0 nic, 1 para, 2 dwie pary, 3 trójka+). */
function szuBoardPairCategory(array $board): int
{
    $counts = array();
    foreach ($board as $card) {
        $r = $card >> 2;
        $counts[$r] = ($counts[$r] ?? 0) + 1;
    }
    $pairs = 0; $trips = false;
    foreach ($counts as $n) {
        if ($n >= 3) { $trips = true; }
        elseif ($n === 2) { $pairs++; }
    }
    return $trips ? 3 : ($pairs >= 2 ? 2 : ($pairs === 1 ? 1 : 0));
}

/**
 * Informacje o dobieraniu (dla własnej ręki): liczba „czystych” outów do koloru/strita.
 * @return array{flush:bool,oesd:bool,gutshot:bool,outs:int}
 */
function szuDrawInfo(array $hole, array $board): array
{
    $all = array_merge($hole, $board);
    $info = array('flush' => false, 'oesd' => false, 'gutshot' => false, 'outs' => 0);
    if (count($board) >= 5 || count($board) < 3) {
        return $info;
    }
    $suitCount = array(0, 0, 0, 0);
    foreach ($all as $c) { $suitCount[$c & 3]++; }
    foreach ($hole as $c) {
        if ($suitCount[$c & 3] === 4) { $info['flush'] = true; }
    }
    $mask = 0; $holeMask = 0;
    foreach ($all as $c) { $mask |= 1 << ($c >> 2); }
    foreach ($hole as $c) { $holeMask |= 1 << ($c >> 2); }
    $m = (($mask & ((1 << 13) - 1)) << 1) | (($mask >> 12) & 1); // bit0 = as niski
    $hm = (($holeMask & ((1 << 13) - 1)) << 1) | (($holeMask >> 12) & 1);
    $made = szuEval($all) >> 20;
    if ($made < 4) {
        for ($lo = 0; $lo <= 10; $lo++) {
            $window = 0xF << $lo;
            if (($m & $window) === $window && ($hm & $window)) {
                $below = $lo > 0 ? !(($m >> ($lo - 1)) & 1) : false;
                $above = $lo + 4 <= 13 ? !(($m >> ($lo + 4)) & 1) : false;
                if ($below && $above) { $info['oesd'] = true; }
                elseif ($below || $above) { $info['gutshot'] = true; }
            }
        }
        if (!$info['oesd']) {
            for ($lo = 0; $lo <= 9; $lo++) {
                $window = 0x1F << $lo;
                $bits = $m & $window;
                if (substr_count(decbin($bits), '1') === 4 && ($hm & $window)) { $info['gutshot'] = true; }
            }
        }
    }
    $outs = 0;
    if ($info['flush']) { $outs += 9; }
    if ($info['oesd']) { $outs += $info['flush'] ? 6 : 8; }
    elseif ($info['gutshot']) { $outs += $info['flush'] ? 3 : 4; }
    $info['outs'] = $outs;
    return $info;
}

/**
 * Siła ręki przeciwnika względem kart wspólnych (do zawężania jego zakresu):
 * 0 powietrze, 1 słaba para / dobieranie, 2 średnia (top para słaby kicker), 3 mocna (top para dobry kicker, overpara, dwie pary), 4 potwór.
 */
function szuStrengthClass(int $h1, int $h2, array $board, int $boardCat, int $topRank): int
{
    $cards = $board;
    $cards[] = $h1;
    $cards[] = $h2;
    $v = szuEval($cards);
    $cat = $v >> 20;
    $r1 = $h1 >> 2;
    $r2 = $h2 >> 2;
    if ($cat >= 4) {
        return 4;
    }
    if ($cat === 3) {
        if ($boardCat >= 3) { return max($r1, $r2) >= 10 ? 2 : 1; }
        return 4; // set lub trips z kartą w ręce
    }
    if ($cat === 2) {
        if ($boardCat >= 2) { return max($r1, $r2) >= 10 ? 1 : 0; }
        if ($boardCat === 1) { return ($r1 === $r2 && $r1 > $topRank) ? 3 : 2; }
        return 3;
    }
    if ($cat === 1) {
        if ($boardCat >= 1) {
            return max($r1, $r2) >= 11 ? 1 : 0;
        }
        $pair = ($v >> 16) & 15;
        if ($r1 === $r2) {
            return $r1 > $topRank ? ($r1 >= 8 ? 3 : 2) : 1;
        }
        if ($pair === $topRank) {
            $kicker = $r1 === $pair ? $r2 : $r1;
            return $kicker >= 8 ? 3 : 2;
        }
        return 1;
    }
    if (count($board) < 5) {
        $suits = array(0, 0, 0, 0);
        foreach ($cards as $c) { $suits[$c & 3]++; }
        if ($suits[$h1 & 3] >= 4 || $suits[$h2 & 3] >= 4) { return 1; }
        $mask = 0;
        foreach ($cards as $c) { $mask |= 1 << ($c >> 2); }
        for ($lo = 0; $lo <= 9; $lo++) {
            if ((($mask >> $lo) & 0xF) === 0xF) { return 1; }
        }
    }
    return 0;
}

// ---------------------------------------------------------------------------
// Model przeciwnika
// ---------------------------------------------------------------------------

/** @return array{hands:int,vpip:float,pfr:float,af:float,ftb:float} */
function szuProfile(PDO $pdo, int $userId): array
{
    $prior = array('hands' => 0, 'vpip' => 0.38, 'pfr' => 0.16, 'af' => 1.4, 'ftb' => 0.45);
    try {
        $stmt = $pdo->prepare('SELECT * FROM poker_szu_stats WHERE user_id = ? LIMIT 1');
        $stmt->execute(array($userId));
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        $row = null;
    }
    if (!$row) {
        return $prior;
    }
    $hands = (int)$row['hands'];
    $k = 18.0; // siła założeń wstępnych — im więcej rozdań, tym bardziej ufamy obserwacjom
    $vpip = ((int)$row['vpip'] + $prior['vpip'] * $k) / ($hands + $k);
    $pfr = ((int)$row['pfr'] + $prior['pfr'] * $k) / ($hands + $k);
    $aggr = (int)$row['post_aggr'];
    $calls = (int)$row['post_calls'];
    $af = ($aggr + $prior['af'] * 6) / ($calls + 6);
    $ftb = ((int)$row['fold_to_bet'] + $prior['ftb'] * 8) / ((int)$row['faced_bet'] + 8);
    return array('hands' => $hands, 'vpip' => $vpip, 'pfr' => $pfr, 'af' => $af, 'ftb' => $ftb);
}

/** Zapis statystyk ludzi po zakończonym rozdaniu (odporne na błędy — nigdy nie przerywa gry). */
function szuRecordHand(PDO $pdo, array $state, array $players): void
{
    try {
        $log = $state['log'] ?? array();
        if (!$log) {
            return;
        }
        $showdown = (($state['last_result']['type'] ?? '') === 'showdown');
        $stmt = $pdo->prepare('INSERT INTO poker_szu_stats (user_id, hands, vpip, pfr, post_aggr, post_calls, faced_bet, fold_to_bet, showdowns, updated_at)
            VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE hands = hands + 1, vpip = vpip + VALUES(vpip), pfr = pfr + VALUES(pfr), post_aggr = post_aggr + VALUES(post_aggr),
            post_calls = post_calls + VALUES(post_calls), faced_bet = faced_bet + VALUES(faced_bet), fold_to_bet = fold_to_bet + VALUES(fold_to_bet),
            showdowns = showdowns + VALUES(showdowns), updated_at = UTC_TIMESTAMP()');
        foreach ($players as $player) {
            if (($player['auth_type'] ?? '') === 'bot') { continue; }
            $cards = json_decode((string)$player['hole_cards_json'], true) ?: array();
            if (!$cards) { continue; }
            $uid = (int)$player['user_id'];
            $vpip = 0; $pfr = 0; $aggr = 0; $calls = 0; $faced = 0; $foldToBet = 0;
            foreach ($log as $entry) {
                if ((int)$entry[0] !== $uid) { continue; }
                $phase = (string)$entry[1];
                $act = (string)$entry[2];
                $facing = (int)($entry[4] ?? 0) > 0;
                if ($phase === 'preflop') {
                    if ($act === 'call' || $act === 'raise') { $vpip = 1; }
                    if ($act === 'raise') { $pfr = 1; }
                } else {
                    if ($act === 'raise') { $aggr++; }
                    if ($act === 'call') { $calls++; }
                    if ($facing && in_array($act, array('fold', 'call', 'raise'), true)) {
                        $faced++;
                        if ($act === 'fold') { $foldToBet++; }
                    }
                }
            }
            $wentToShowdown = $showdown && (int)$player['folded'] === 0 ? 1 : 0;
            $stmt->execute(array($uid, $vpip, $pfr, $aggr, $calls, $faced, $foldToBet, $wentToShowdown));
        }
    } catch (Throwable $e) {
        error_log('Wielki Szu: zapis statystyk nieudany: ' . $e->getMessage());
    }
}

/**
 * Szacowany zakres przeciwnika na podstawie jego akcji w tym rozdaniu i profilu.
 * @return array{top:float,cap:float,aggr:int,calls:int,checks:int,bluff:float}
 */
function szuOpponentRange(int $uid, array $log, array $profile): array
{
    $preRaises = 0; $preCalls = 0; $raisesBefore = 0; $facedRaise = false;
    $aggr = 0; $calls = 0; $checks = 0;
    $raisesSeen = 0;
    foreach ($log as $entry) {
        $isMe = (int)$entry[0] === $uid;
        $phase = (string)$entry[1];
        $act = (string)$entry[2];
        if ($phase === 'preflop') {
            if ($isMe) {
                if ($act === 'raise') { $preRaises++; $raisesBefore = max($raisesBefore, $raisesSeen); }
                if ($act === 'call') { $preCalls++; if ($raisesSeen > 0) { $facedRaise = true; } }
            }
            if ($act === 'raise') { $raisesSeen++; }
        } elseif ($isMe) {
            if ($act === 'raise') { $aggr++; }
            elseif ($act === 'call') { $calls++; }
            elseif ($act === 'check') { $checks++; }
        }
    }
    $vpip = max(0.08, min(0.95, $profile['vpip']));
    $pfr = max(0.04, min(0.70, $profile['pfr']));
    $cap = 0.0; // odsetek najmocniejszych rąk rzadziej obecnych (gracz by je podbił)
    if ($preRaises >= 2 || ($preRaises >= 1 && $raisesBefore >= 1)) {
        $top = max(0.025, min(0.20, $pfr * 0.38));
    } elseif ($preRaises === 1) {
        $top = max(0.07, min(0.60, $pfr * 1.25));
    } elseif ($facedRaise) {
        $top = max(0.10, min(0.65, $vpip * 0.70));
        $cap = min(0.04, $pfr * 0.25);
    } elseif ($preCalls > 0) {
        $top = max(0.22, min(0.95, $vpip * 1.05));
        $cap = min(0.08, $pfr * 0.45);
    } else {
        $top = 1.0;
    }
    $bluff = max(0.45, min(2.4, $profile['af'] / 1.4));
    return array('top' => $top, 'cap' => $cap, 'aggr' => $aggr, 'calls' => $calls, 'checks' => $checks, 'bluff' => $bluff);
}

/** Waga (0–1) wylosowanej ręki przeciwnika — łączy zakres przedflopowy i zachowanie po flopie. */
function szuHandWeight(int $h1, int $h2, array $range, array $board, int $boardCat, int $topRank): float
{
    $p = szuPercentile($h1, $h2);
    $w = $p <= $range['top'] ? 1.0 : exp(-($p - $range['top']) / 0.07);
    if ($range['cap'] > 0 && $p < $range['cap']) {
        $w *= 0.45;
    }
    if (!$board || ($range['aggr'] + $range['calls'] + $range['checks']) === 0) {
        return $w;
    }
    $ss = szuStrengthClass($h1, $h2, $board, $boardCat, $topRank);
    static $aggrTable = array(0.16, 0.42, 0.78, 1.0, 1.0);
    static $callTable = array(0.22, 0.80, 1.0, 0.95, 0.80);
    static $checkTable = array(1.0, 1.0, 0.85, 0.62, 0.45);
    $a = $aggrTable[$ss];
    if ($ss === 0) { $a = min(1.0, $a * $range['bluff']); }
    for ($i = 0; $i < min(3, $range['aggr']); $i++) { $w *= $a; }
    for ($i = 0; $i < min(3, $range['calls']); $i++) { $w *= $callTable[$ss]; }
    for ($i = 0; $i < min(2, $range['checks']); $i++) { $w *= $checkTable[$ss]; }
    return $w;
}

/**
 * Equity (udział w puli 0–1) własnej ręki wobec zakresów aktywnych przeciwników — Monte Carlo z ograniczeniem czasu.
 * @param array<int,array> $ranges
 */
function szuEquity(array $hole, array $board, array $ranges, float $budget = SZU_TIME_BUDGET): array
{
    $known = array_merge($hole, $board);
    $deck = array();
    for ($c = 0; $c < 52; $c++) {
        if (!in_array($c, $known, true)) { $deck[] = $c; }
    }
    $boardNeed = 5 - count($board);
    $boardCat = szuBoardPairCategory($board);
    $topRank = -1;
    foreach ($board as $card) { $topRank = max($topRank, $card >> 2); }
    $nOpp = count($ranges);
    $start = microtime(true);
    $total = 0.0;
    $sims = 0;
    $n = count($deck);
    while ($sims < SZU_MAX_SIMS) {
        if ($sims >= SZU_MIN_SIMS && ($sims & 63) === 0 && microtime(true) - $start > $budget) {
            break;
        }
        $d = $deck;
        $pos = 0;
        $oppHands = array();
        foreach ($ranges as $range) {
            for ($try = 0; $try < 60; $try++) {
                $i = $pos + mt_rand(0, $n - $pos - 1);
                $t = $d[$pos]; $d[$pos] = $d[$i]; $d[$i] = $t;
                $j = $pos + 1 + mt_rand(0, $n - $pos - 2);
                $t = $d[$pos + 1]; $d[$pos + 1] = $d[$j]; $d[$j] = $t;
                $w = szuHandWeight($d[$pos], $d[$pos + 1], $range, $board, $boardCat, $topRank);
                if ($w >= 1.0 || mt_rand() / mt_getrandmax() < $w) {
                    break;
                }
            }
            $oppHands[] = array($d[$pos], $d[$pos + 1]);
            $pos += 2;
        }
        $full = $board;
        for ($k = 0; $k < $boardNeed; $k++) {
            $i = $pos + mt_rand(0, $n - $pos - 1);
            $t = $d[$pos]; $d[$pos] = $d[$i]; $d[$i] = $t;
            $full[] = $d[$pos];
            $pos++;
        }
        $mine = $full; $mine[] = $hole[0]; $mine[] = $hole[1];
        $my = szuEval($mine);
        $win = true; $ties = 1;
        foreach ($oppHands as $oh) {
            $cards = $full; $cards[] = $oh[0]; $cards[] = $oh[1];
            $v = szuEval($cards);
            if ($v > $my) { $win = false; break; }
            if ($v === $my) { $ties++; }
        }
        if ($win) { $total += 1.0 / $ties; }
        $sims++;
    }
    return array('equity' => $sims > 0 ? $total / $sims : 0.0, 'sims' => $sims, 'opponents' => $nOpp);
}

// ---------------------------------------------------------------------------
// Strategia
// ---------------------------------------------------------------------------

function szuRand(): float
{
    return mt_rand() / mt_getrandmax();
}

/** Faktura stołu: 0 sucha … 1 bardzo „mokra” (dużo dobierających układów). */
function szuBoardWetness(array $board): float
{
    if (count($board) < 3) { return 0.0; }
    $suits = array(0, 0, 0, 0);
    $ranks = array();
    foreach ($board as $c) { $suits[$c & 3]++; $ranks[] = $c >> 2; }
    $wet = 0.0;
    $maxSuit = max($suits);
    if ($maxSuit >= 3) { $wet += 0.45; } elseif ($maxSuit === 2) { $wet += 0.2; }
    sort($ranks);
    $ranks = array_values(array_unique($ranks));
    $connected = 0;
    for ($i = 1; $i < count($ranks); $i++) {
        if ($ranks[$i] - $ranks[$i - 1] <= 2) { $connected++; }
    }
    $wet += min(0.45, $connected * 0.18);
    if (szuBoardPairCategory($board) >= 1) { $wet -= 0.1; }
    return max(0.0, min(1.0, $wet));
}

/**
 * Decyzja Wielkiego Szu.
 * @param array<string,mixed> $ctx
 * @return array{action:string,raise_to:int,why:string}
 */
function szuDecide(PDO $pdo, array $ctx): array
{
    $hole = szuCardsToInts($ctx['hole']);
    $board = szuCardsToInts($ctx['board']);
    $phase = (string)$ctx['phase'];
    $bb = max(1, (int)$ctx['big_blind']);
    $toCall = (int)$ctx['to_call'];
    $pot = (int)$ctx['pot'];                  // wszystkie wpłaty w rozdaniu, łącznie z bieżącą rundą
    $myBet = (int)$ctx['my_bet'];
    $myChips = (int)$ctx['my_chips'];
    $currentBet = (int)$ctx['current_bet'];
    $minRaise = max($bb, (int)$ctx['min_raise']);
    $maxTo = $myBet + $myChips;
    $minTo = min($maxTo, $currentBet + $minRaise);
    $canRaise = $maxTo > $currentBet && $ctx['can_raise'];
    $log = $ctx['log'];
    $opponents = $ctx['opponents'];            // aktywni (niespasowani) rywale
    $nOpp = max(1, count($opponents));
    $dealtCount = max(2, (int)$ctx['dealt_count']);
    $inPosition = (bool)$ctx['in_position'];
    $noise = (szuRand() - 0.5) * 0.05;         // mieszanie strategii — Szu nie jest przewidywalny

    // Efektywny stos: nie ryzykujemy więcej, niż może stracić najgłębszy rywal.
    $deepest = 0;
    foreach ($opponents as $opp) { $deepest = max($deepest, (int)$opp['chips'] + (int)$opp['current_bet']); }
    $effective = min($maxTo, max($deepest, $currentBet));
    $effBB = $effective / $bb;
    // „All-in” Szu nigdy nie przekracza tego, co rywale mogą faktycznie postawić.
    $shove = max($minTo, min($maxTo, $effective));

    $raiseTo = function (float $target) use ($minTo, $shove, $pot, $currentBet): int {
        $to = (int)round($target);
        $to = max($minTo, min($shove, $to));
        // Gdy po podbiciu zostałoby niewiele — lepiej od razu całość efektywnego stosu.
        if ($shove - $to < max(1, (int)(0.28 * ($pot + $to - $currentBet)))) { $to = $shove; }
        return $to;
    };
    $act = function (string $action, int $to = 0, string $why = '') use ($toCall, $canRaise, $maxTo): array {
        if ($action === 'raise' && (!$canRaise || $to <= 0)) { $action = $toCall > 0 ? 'call' : 'check'; }
        if ($action === 'call' && $toCall === 0) { $action = 'check'; }
        if ($action === 'check' && $toCall > 0) { $action = 'fold'; }
        return array('action' => $action, 'raise_to' => $action === 'raise' ? $to : 0, 'why' => $why);
    };

    // Zakresy przeciwników i equity.
    $ranges = array();
    $ftbSum = 0.0;
    foreach ($opponents as $opp) {
        $profile = szuProfile($pdo, (int)$opp['user_id']);
        $ftbSum += $profile['ftb'];
        $ranges[] = szuOpponentRange((int)$opp['user_id'], $log, $profile);
    }
    $avgFtb = $ftbSum / $nOpp;
    $eq = szuEquity($hole, $board, $ranges);
    $equity = $eq['equity'];
    $potOdds = $toCall > 0 ? $toCall / ($pot + $toCall) : 0.0;

    $preRaises = 0;
    $iAmAggressor = false;
    foreach ($log as $entry) {
        if ($entry[1] === 'preflop' && $entry[2] === 'raise') {
            $preRaises++;
            $iAmAggressor = (int)$entry[0] === (int)$ctx['my_user_id'];
        }
    }
    $streetRaises = 0;
    foreach ($log as $entry) {
        if ($entry[1] === $phase && $entry[2] === 'raise') { $streetRaises++; }
    }

    // ------------------------------------------------------------------ PREFLOP
    if ($phase === 'preflop') {
        $pct = szuPercentile($hole[0], $hole[1]);
        $limpers = 0;
        foreach ($log as $entry) {
            if ($entry[1] === 'preflop' && $entry[2] === 'call' && (int)$entry[4] <= $bb) { $limpers++; }
        }

        // Krótki stos: strategia push/fold.
        if ($effBB <= 11) {
            $pushRange = $preRaises === 0 ? ($dealtCount === 2 ? 0.62 : ($ctx['is_late'] ? 0.40 : 0.24)) : 0.11;
            if ($preRaises > 0 && $toCall > 0) {
                $pushRange = max($pushRange, $equity > $potOdds + 0.06 ? 0.30 : 0.0);
            }
            if ($pct <= $pushRange + $noise) { return $act('raise', $shove, 'push/fold: all-in'); }
            if ($toCall === 0) { return $act('check'); }
            if ($equity >= $potOdds + 0.02) { return $act('call', 0, 'push/fold: tanie sprawdzenie'); }
            return $act('fold');
        }

        if ($preRaises === 0) {
            if ($toCall === 0) {
                // Duża ciemna po limpach.
                if ($pct <= 0.17 + $noise || ($pct <= 0.30 && szuRand() < 0.22)) {
                    return $act('raise', $raiseTo($bb * (3.5 + $limpers)), 'izolacja limperów');
                }
                return $act('check');
            }
            $open = $dealtCount === 2 ? 0.82 : ($dealtCount === 3 ? ($ctx['is_late'] ? 0.56 : 0.42) : ($ctx['is_late'] ? 0.47 : 0.30));
            if ($limpers > 0) {
                if ($pct <= $open * 0.62 + $noise) {
                    return $act('raise', $raiseTo($bb * (3.5 + $limpers)), 'izolacja');
                }
                if ($pct <= $open * 1.05 && $toCall <= $bb) { return $act('call', 0, 'dołączenie do limpów'); }
                return $act('fold');
            }
            if ($pct <= $open + $noise) {
                $size = $dealtCount === 2 ? 2.4 : 2.8;
                return $act('raise', $raiseTo($bb * $size), 'otwarcie');
            }
            if ($toCall <= $bb / 2 && $pct <= 0.62 && szuRand() < 0.35) { return $act('call', 0, 'dopełnienie SB'); }
            return $act('fold');
        }

        // Ktoś podbił.
        $valueRe = $preRaises === 1 ? ($dealtCount === 2 ? 0.10 : 0.055) : 0.028;
        if ($pct <= $valueRe + $noise * 0.3) {
            $mult = $preRaises === 1 ? ($inPosition ? 3.0 : 3.6) : 2.3;
            $target = $currentBet * $mult + max(0, $pot - $currentBet * 2) * 0.5;
            if ($target >= $effective * 0.38) { $target = $shove; }
            return $act('raise', $raiseTo($target), 'value re-raise');
        }
        if ($preRaises === 1 && $pct > 0.10 && $pct <= 0.30 && szuRand() < 0.07 && $avgFtb > 0.35) {
            $target = $currentBet * ($inPosition ? 3.0 : 3.5);
            if ($target < $effective * 0.35) { return $act('raise', $raiseTo($target), 'light 3-bet'); }
        }
        $margin = 0.025 + ($toCall > 0.25 * $myChips ? 0.06 : 0.0) - ($inPosition ? 0.015 : 0.0);
        if ($toCall > 0 && $equity >= $potOdds + $margin + $noise * 0.5) {
            return $act('call', 0, 'sprawdzenie z equity ' . round($equity, 3));
        }
        return $act($toCall === 0 ? 'check' : 'fold');
    }

    // ------------------------------------------------------------------ PO FLOPIE
    $draw = szuDrawInfo($hole, $board);
    $wet = szuBoardWetness($board);
    $isRiver = $phase === 'river';
    $made = szuEval(array_merge($hole, $board)) >> 20;
    $valueLine = $nOpp === 1 ? 0.62 : ($nOpp === 2 ? 0.68 : 0.72);
    $strongLine = $nOpp === 1 ? 0.80 : 0.84;
    $sizeFrac = 0.50 + $wet * 0.30 + (szuRand() - 0.5) * 0.12;
    $spr = $pot > 0 ? $effective / $pot : 99;

    if ($toCall === 0) {
        if ($equity >= $valueLine + $noise) {
            // Slowplay potworów na suchym flopie — czasem.
            if ($phase === 'flop' && $equity >= 0.9 && $wet < 0.3 && szuRand() < 0.25 && $nOpp === 1) {
                return $act('check', 0, 'slowplay');
            }
            $frac = $equity >= $strongLine ? $sizeFrac + 0.12 : $sizeFrac;
            if ($isRiver && $equity >= 0.93 && szuRand() < 0.25) { $frac = 1.25; } // overbet z nutsem
            if ($spr < 1.4) { return $act('raise', $shove, 'value: niski SPR'); }
            return $act('raise', $raiseTo($frac * $pot), 'value bet ' . round($equity, 3));
        }
        if (!$isRiver && $draw['outs'] >= 8 && szuRand() < ($inPosition ? 0.62 : 0.48)) {
            return $act('raise', $raiseTo(max(0.55, $sizeFrac) * $pot), 'semi-bluff');
        }
        if ($phase === 'flop' && $iAmAggressor && $nOpp <= 2) {
            $freq = ($nOpp === 1 ? 0.62 : 0.38) + (0.3 - $wet) * 0.35 + ($avgFtb - 0.45) * 0.5;
            if (szuRand() < max(0.15, min(0.85, $freq))) {
                return $act('raise', $raiseTo((0.33 + $wet * 0.2) * $pot), 'c-bet');
            }
        }
        if ($phase === 'turn' && $iAmAggressor && $nOpp === 1 && $equity >= 0.42 && szuRand() < 0.40) {
            return $act('raise', $raiseTo(0.6 * $pot), 'druga lufa');
        }
        if ($isRiver && $nOpp === 1 && $equity < 0.22 && $inPosition) {
            $bluffFreq = max(0.06, min(0.34, $avgFtb * 0.6));
            if (szuRand() < $bluffFreq) { return $act('raise', $raiseTo(0.7 * $pot), 'blef na riverze'); }
        }
        return $act('check');
    }

    // Zakład do wyrównania.
    $implied = (!$isRiver && $draw['outs'] >= 8) ? min(0.08, $effective / max(1, $pot) * 0.02 + 0.03) : 0.0;
    $commit = $toCall >= 0.45 * $myChips;
    $callNeed = $potOdds + 0.02 + ($commit ? 0.05 : 0.0) - $implied;

    if ($equity >= $strongLine + $noise && $canRaise) {
        if ($spr < 2.2 || $streetRaises >= 2) { return $act('raise', $shove, 'value all-in'); }
        $target = $currentBet * 2.6 + ($pot - $currentBet) * 0.45;
        if ($isRiver && $streetRaises >= 1 && $equity < 0.9) {
            return $act('call', 0, 'ostrożne sprawdzenie przebicia');
        }
        return $act('raise', $raiseTo($target), 'value raise');
    }
    if (!$isRiver && $draw['outs'] >= 12 && $streetRaises <= 1 && $canRaise && szuRand() < 0.33 && $nOpp === 1) {
        return $act('raise', $raiseTo($currentBet * 2.8 + ($pot - $currentBet) * 0.4), 'semi-bluff raise');
    }
    if ($phase === 'flop' && $nOpp === 1 && $streetRaises === 1 && $inPosition && $equity < 0.30 && $wet < 0.25
        && $toCall <= 0.5 * $pot && $avgFtb > 0.5 && szuRand() < 0.08 && $canRaise) {
        return $act('raise', $raiseTo($currentBet * 3.0), 'blef-przebicie');
    }
    if ($equity >= $callNeed + $noise * 0.5) {
        return $act('call', 0, 'sprawdzenie ' . round($equity, 3) . ' vs ' . round($callNeed, 3));
    }
    return $act('fold', 0, 'pas ' . round($equity, 3) . ' < ' . round($callNeed, 3));
}

// ---------------------------------------------------------------------------
// Konta komputera i integracja ze stołem
// ---------------------------------------------------------------------------

function szuIsBot(array $player): bool
{
    return ($player['auth_type'] ?? '') === 'bot';
}

function szuRoman(int $n): string
{
    $map = array(1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I');
    $out = '';
    foreach ($map as $value => $symbol) {
        while ($n >= $value) { $out .= $symbol; $n -= $value; }
    }
    return $out;
}

/** Czy nick jest zastrzeżony dla komputera. */
function szuReservedName(string $name): bool
{
    return preg_match('/^\s*wielki[\s_\-.]*szu/iu', $name) === 1;
}

/** Wolne konto komputera (tworzy nowe: „Wielki Szu”, „Wielki Szu II”, …). */
function szuAcquireBotUser(PDO $pdo, array $excludeIds = array()): int
{
    $sql = "SELECT u.id FROM users u LEFT JOIN table_players tp ON tp.user_id = u.id
            WHERE u.auth_type = 'bot' AND tp.id IS NULL";
    if ($excludeIds) {
        $sql .= ' AND u.id NOT IN (' . implode(',', array_map('intval', $excludeIds)) . ')';
    }
    $sql .= ' ORDER BY u.id LIMIT 1';
    $id = (int)($pdo->query($sql)->fetchColumn() ?: 0);
    if ($id > 0) {
        return $id;
    }
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE auth_type = 'bot'")->fetchColumn();
    if ($count >= SZU_MAX_BOTS_TOTAL) {
        throw new RuntimeException('Wielki Szu gra już przy wszystkich możliwych stołach. Spróbuj za chwilę.');
    }
    for ($n = $count + 1; $n < $count + 20; $n++) {
        $name = $n === 1 ? SZU_NAME : SZU_NAME . ' ' . szuRoman($n);
        $exists = $pdo->prepare('SELECT id, auth_type FROM users WHERE username = ? LIMIT 1');
        $exists->execute(array($name));
        $row = $exists->fetch();
        if ($row) {
            continue;
        }
        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, auth_type, site_uid, chips, last_refill_at, last_activity_at)
            VALUES (?, NULL, 'bot', ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
        $insert->execute(array($name, 'bot:' . $n, DEFAULT_CHIPS));
        return (int)$pdo->lastInsertId();
    }
    throw new RuntimeException('Nie udało się przygotować Wielkiego Szu.');
}

/** Sprawdza, czy użytkownik siedzi przy stole (warunek zarządzania komputerem). */
function szuRequireSeated(PDO $pdo, int $tableId, int $userId): void
{
    $stmt = $pdo->prepare('SELECT 1 FROM table_players WHERE table_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute(array($tableId, $userId));
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Usiądź przy stole, aby zaprosić Wielkiego Szu.');
    }
}

/** Dosadza Wielkiego Szu do stołu. Zwraca nazwę komputera. */
function szuAddBot(PDO $pdo, int $tableId, int $requesterId): string
{
    szuRequireSeated($pdo, $tableId, $requesterId);
    $tried = array();
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $botId = szuAcquireBotUser($pdo, $tried);
        $tried[] = $botId;
        $pdo->prepare('UPDATE users SET chips = ?, last_activity_at = UTC_TIMESTAMP() WHERE id = ?')->execute(array(DEFAULT_CHIPS, $botId));
        try {
            seatPlayer($pdo, $tableId, $botId);
        } catch (RuntimeException $exception) {
            if (strpos($exception->getMessage(), 'opuść aktualny stół') !== false) {
                continue; // konto zajął równoległy stół — bierzemy następne
            }
            throw $exception;
        }
        $pdo->prepare('UPDATE table_players SET chips = ? WHERE table_id = ? AND user_id = ?')->execute(array(DEFAULT_CHIPS, $tableId, $botId));
        return SZU_NAME;
    }
    throw new RuntimeException('Nie udało się dosadzić Wielkiego Szu. Spróbuj ponownie.');
}

/** Usuwa wskazanego komputerowego gracza ze stołu (tylko między rozdaniami). */
function szuRemoveBot(PDO $pdo, int $tableId, int $botUserId, int $requesterId): void
{
    szuRequireSeated($pdo, $tableId, $requesterId);
    $pdo->beginTransaction();
    try {
        $table = $pdo->prepare('SELECT status FROM poker_tables WHERE id = ? FOR UPDATE');
        $table->execute(array($tableId));
        $status = (string)$table->fetchColumn();
        if ($status === 'playing') {
            throw new RuntimeException('Wielki Szu może odejść po zakończeniu bieżącego rozdania.');
        }
        $del = $pdo->prepare("DELETE tp FROM table_players tp INNER JOIN users u ON u.id = tp.user_id
            WHERE tp.table_id = ? AND tp.user_id = ? AND u.auth_type = 'bot'");
        $del->execute(array($tableId, $botUserId));
        if ($del->rowCount() < 1) {
            throw new RuntimeException('Ten gracz nie jest komputerem przy tym stole.');
        }
        $pdo->prepare('UPDATE poker_tables SET updated_at = UTC_TIMESTAMP() WHERE id = ?')->execute(array($tableId));
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $exception;
    }
}

/** Komputer nie gra sam ze sobą: po odejściu wszystkich ludzi zwalnia miejsca (poza trwającym rozdaniem). */
function szuCleanupOrphans(PDO $pdo): void
{
    try {
        $rows = $pdo->query("SELECT tp.table_id,
                SUM(u.auth_type = 'bot') AS bots, SUM(u.auth_type <> 'bot') AS humans, MAX(t.status) AS status
            FROM table_players tp
            INNER JOIN users u ON u.id = tp.user_id
            INNER JOIN poker_tables t ON t.id = tp.table_id
            GROUP BY tp.table_id
            HAVING bots > 0 AND humans = 0")->fetchAll();
        foreach ($rows as $row) {
            if ((string)$row['status'] === 'playing') {
                continue; // rozdanie zostanie dokończone lub zresetowane przy najbliższym odświeżeniu stołu
            }
            $pdo->prepare("DELETE tp FROM table_players tp INNER JOIN users u ON u.id = tp.user_id WHERE tp.table_id = ? AND u.auth_type = 'bot'")
                ->execute(array((int)$row['table_id']));
        }
        szuDeleteEmptySzuTables($pdo);
    } catch (Throwable $e) {
        error_log('Wielki Szu: sprzątanie nieudane: ' . $e->getMessage());
    }
}

/**
 * Przy każdym stole komputer przedstawia się jako „Wielki Szu” (kolejni: „Wielki Szu II”, „III”),
 * niezależnie od technicznej nazwy konta w bazie.
 */
function szuAliasPlayers(array &$players): void
{
    $n = 0;
    foreach ($players as &$player) {
        if (szuIsBot($player)) {
            $n++;
            $player['username'] = $n === 1 ? SZU_NAME : SZU_NAME . ' ' . szuRoman($n);
        }
    }
    unset($player);
}

/** Usuwa puste stoły utworzone przyciskiem „Zagraj z Wielkim Szu”. */
function szuDeleteEmptySzuTables(PDO $pdo): void
{
    try {
        $rows = $pdo->query("SELECT t.id, t.state_json FROM poker_tables t LEFT JOIN table_players tp ON tp.table_id = t.id
            WHERE tp.id IS NULL AND t.status <> 'playing' AND t.state_json LIKE '%\"szu_table\":true%'")->fetchAll();
        $del = $pdo->prepare('DELETE FROM poker_tables WHERE id = ?');
        foreach ($rows as $row) {
            $state = json_decode((string)$row['state_json'], true);
            if (!empty($state['szu_table'])) {
                $del->execute(array((int)$row['id']));
            }
        }
    } catch (Throwable $e) {
        error_log('Wielki Szu: usuwanie pustych stołów nieudane: ' . $e->getMessage());
    }
}

/** Liczba ludzi przy stole, którzy mogą grać (mają żetony i nie są oznaczeni jako nieaktywni). */
function szuActiveHumans(array $players, array $state): int
{
    $n = 0;
    foreach ($players as $player) {
        if (szuIsBot($player) || (int)$player['chips'] <= 0) { continue; }
        if ((int)($state['afk'][(string)$player['user_id']] ?? 0) >= 2) { continue; }
        $n++;
    }
    return $n;
}

/** Komputer dokupuje żetony, gdy zabraknie mu na ciemne — gra z nim może trwać bez końca. */
function szuRebuyBots(PDO $pdo, array &$players, array &$messages): void
{
    $stmt = $pdo->prepare('UPDATE table_players SET chips = ? WHERE id = ?');
    $wallet = $pdo->prepare('UPDATE users SET chips = ? WHERE id = ?');
    foreach ($players as &$player) {
        if (szuIsBot($player) && (int)$player['chips'] < BIG_BLIND * 2) {
            $player['chips'] = DEFAULT_CHIPS;
            $stmt->execute(array(DEFAULT_CHIPS, (int)$player['id']));
            $wallet->execute(array(DEFAULT_CHIPS, (int)$player['user_id']));
            $messages[] = $player['username'] . ' dokupuje żetony (' . formatChips(DEFAULT_CHIPS) . ').';
        }
    }
    unset($player);
}

/** Czas „namysłu” komputera przed ruchem. */
function szuThinkDelay(): float
{
    return SZU_THINK_MIN + (SZU_THINK_MAX - SZU_THINK_MIN) * szuRand();
}

/**
 * Wykonuje ruch komputera, którego jest kolej (wywoływane w transakcji tickTable).
 */
function szuTakeTurn(PDO $pdo, int $tableId, array $table, array &$state, array &$players): void
{
    $userId = (int)$state['turn_user_id'];
    $me = null;
    foreach ($players as $player) {
        if ((int)$player['user_id'] === $userId) { $me = $player; break; }
    }
    if ($me === null) {
        return;
    }
    $toCall = max(0, (int)$state['current_bet'] - (int)$me['current_bet']);
    $decision = array('action' => $toCall > 0 ? 'fold' : 'check', 'raise_to' => 0, 'why' => 'awaryjnie');
    try {
        $opponents = array();
        $dealt = 0;
        foreach ($players as $player) {
            if ((int)$player['in_hand'] === 1) { $dealt++; }
            if ((int)$player['user_id'] === $userId) { continue; }
            if ((int)$player['in_hand'] === 1 && (int)$player['folded'] === 0) {
                $opponents[] = array('user_id' => (int)$player['user_id'], 'chips' => (int)$player['chips'], 'current_bet' => (int)$player['current_bet']);
            }
        }
        // Pozycja: czy Szu działa jako ostatni po flopie (najbliżej rozdającego od strony zegara).
        $order = array();
        foreach ($players as $player) {
            if ((int)$player['in_hand'] === 1 && (int)$player['folded'] === 0) { $order[] = (int)$player['seat']; }
        }
        sort($order);
        $dealerSeat = (int)$state['dealer_seat'];
        $rotated = array();
        foreach ($order as $seat) { if ($seat > $dealerSeat) { $rotated[] = $seat; } }
        foreach ($order as $seat) { if ($seat <= $dealerSeat) { $rotated[] = $seat; } }
        $inPosition = $rotated && (int)end($rotated) === (int)$me['seat'];
        $isLate = (int)$me['seat'] === $dealerSeat || $inPosition;

        // Czy przebicie jest dozwolone (po niepełnym all-in rywala nie wolno ponownie podbijać graczowi, który już działał).
        $canRaise = true;
        $othersCanAct = 0;
        foreach ($players as $player) {
            if ((int)$player['user_id'] !== $userId && (int)$player['in_hand'] === 1 && (int)$player['folded'] === 0 && (int)$player['all_in'] === 0) {
                $othersCanAct++;
            }
        }
        if ($othersCanAct === 0) { $canRaise = false; } // wszyscy rywale all-in — przebijanie nie ma sensu

        $ctx = array(
            'my_user_id' => $userId,
            'hole' => json_decode((string)$me['hole_cards_json'], true) ?: array(),
            'board' => $state['board'] ?? array(),
            'phase' => (string)$state['phase'],
            'big_blind' => (int)$table['big_blind'],
            'to_call' => $toCall,
            'pot' => array_sum(array_map(function (array $p): int { return (int)$p['total_bet']; }, $players)),
            'my_bet' => (int)$me['current_bet'],
            'my_chips' => (int)$me['chips'],
            'current_bet' => (int)$state['current_bet'],
            'min_raise' => (int)$state['min_raise'],
            'can_raise' => $canRaise,
            'log' => $state['log'] ?? array(),
            'opponents' => $opponents,
            'dealt_count' => $dealt,
            'in_position' => $inPosition,
            'is_late' => $isLate,
        );
        if (count($ctx['hole']) === 2 && $opponents) {
            $decision = szuDecide($pdo, $ctx);
        }
    } catch (Throwable $e) {
        error_log('Wielki Szu: błąd decyzji: ' . $e->getMessage());
    }

    // Walidacja końcowa — ruch zawsze musi być legalny.
    $action = $decision['action'];
    $raiseTo = (int)$decision['raise_to'];
    $maxTo = (int)$me['current_bet'] + (int)$me['chips'];
    if ($action === 'raise') {
        $minTo = (int)$state['current_bet'] + max(1, (int)$state['min_raise']);
        if ($raiseTo > $maxTo) { $raiseTo = $maxTo; }
        if ($raiseTo < $minTo && $raiseTo !== $maxTo) { $raiseTo = min($minTo, $maxTo); }
        if ($raiseTo <= (int)$state['current_bet']) { $action = $toCall > 0 ? 'call' : 'check'; }
    }
    if ($action === 'call' && $toCall === 0) { $action = 'check'; }
    if ($action === 'check' && $toCall > 0) { $action = 'fold'; }
    $debugFile = getenv('SZU_DEBUG_FILE');
    if (is_string($debugFile) && $debugFile !== '') {
        @file_put_contents($debugFile, sprintf("%s #%d %s %s board=%s toCall=%d pot=%d -> %s %d (%s)\n", date('H:i:s'), (int)$state['hand_no'], $me['username'],
            implode('', json_decode((string)$me['hole_cards_json'], true) ?: array()), implode('', $state['board'] ?? array()), $toCall,
            (int)$state['pot'], $action, $raiseTo, $decision['why'] ?? ''), FILE_APPEND);
    }
    try {
        applyPlayerAction($pdo, $tableId, $state, $players, $userId, $action, $raiseTo);
    } catch (Throwable $e) {
        error_log('Wielki Szu: ruch odrzucony (' . $action . '): ' . $e->getMessage());
        applyPlayerAction($pdo, $tableId, $state, $players, $userId, $toCall > 0 ? 'fold' : 'check', 0);
    }
}
