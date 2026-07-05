<?php
error_reporting(0);
ini_set('display_errors', '0');
// Deskripsi: Game Matematika
// Contoh: {"level":"easy"}
// JANGAN HAPUS CONTOH DIATAS - ITU FORMAT PARAMETER YANG BENAR
// @param level (noob|easy|medium|hard|extreme) Level Kesulitan

header('Content-Type: application/json; charset=utf-8');

$credit = ['creator' => 'Nanzz'];
set_time_limit(15);

$level = $_GET['level'] ?? 'easy';

$modes = [
    'noob' => [-3, 3, -3, 3, '+-', 15000, 10],
    'easy' => [-10, 10, -10, 10, '*/+-', 20000, 40],
    'medium' => [-40, 40, -20, 20, '*/+-', 40000, 150],
    'hard' => [-100, 100, -70, 70, '*/+-', 60000, 350],
    'extreme' => [-999999, 999999, -999999, 999999, '*/', 99999, 9999]
];

if (!isset($modes[$level])) {
    echo json_encode(array_merge($credit, ['status' => false, 'message' => 'Level tidak valid']), JSON_PRETTY_PRINT);
    exit;
}

$m = $modes[$level];
$a = rand($m[0], $m[1]);
$b = rand($m[2], $m[3]);
$ops = str_split($m[4]);
$op = $ops[array_rand($ops)];

$opMap = ['+' => '+', '-' => '-', '*' => '×', '/' => '÷'];

if ($op == '/') {
    while ($b == 0) $b = rand($m[2], $m[3]);
    $a = $a * $b;
}

switch ($op) {
    case '+': $res = $a + $b; break;
    case '-': $res = $a - $b; break;
    case '*': $res = $a * $b; break;
    case '/': $res = $a / $b; break;
}

$result = [
    'str' => "$a {$opMap[$op]} $b",
    'mode' => $level,
    'time' => $m[5],
    'bonus' => $m[6],
    'result' => $res
];

echo json_encode(array_merge($credit, ['status' => true, 'result' => $result]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>