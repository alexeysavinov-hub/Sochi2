<?php
// BIOSOMA — приём заявок с сайта: письмо и/или сообщение в Telegram. PHP 7.4+.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
$cfg = require __DIR__ . '/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }
if (!empty($_POST['website'])) { echo json_encode(['ok' => true]); exit; } // ловушка для ботов

function field(string $k, int $max = 200): string {
  $v = isset($_POST[$k]) ? (string)$_POST[$k] : '';
  return trim(mb_substr(strip_tags($v), 0, $max));
}
$labels = ['reset' => 'BIOSOMA RESET · 3 дня / 2 ночи', 'detox' => 'BIOSOMA DETOX · 5 дней / 4 ночи', 'longevity' => 'BIOSOMA LONGEVITY · 7 дней / 6 ночей', 'deep' => 'BIOSOMA DEEP RESET · 10–14 дней', 'day' => 'DAY RETREAT'];
$program = field('program', 40);
$guests = field('guests', 10);
$lead = [
  'Программа'   => $labels[$program] ?? $program,
  'Дата заезда' => field('date', 20),
  'Гостей'      => $guests === 'team' ? 'Команда' : $guests,
  'Имя'         => field('name', 80),
  'Телефон'     => field('phone', 40),
];
if ($lead['Имя'] === '' || $lead['Телефон'] === '' || $lead['Дата заезда'] === '' || empty($_POST['consent'])) {
  http_response_code(422); echo json_encode(['ok' => false, 'error' => 'validation']); exit;
}
$text = "Новая заявка BIOSOMA\n";
foreach ($lead as $k => $v) { $text .= $k . ': ' . $v . "\n"; }
$text .= "Согласие на обработку ПДн: получено\nОтправлено: " . date('d.m.Y H:i');

$sent = false;
if (!empty($cfg['mail_to'])) {
  $subject = '=?UTF-8?B?' . base64_encode('Заявка с сайта BIOSOMA') . '?=';
  $headers = 'From: ' . $cfg['mail_from'] . "\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: 8bit";
  $sent = mail($cfg['mail_to'], $subject, $text, $headers) || $sent;
}
if (!empty($cfg['tg_token']) && !empty($cfg['tg_chat']) && function_exists('curl_init')) {
  $ch = curl_init('https://api.telegram.org/bot' . $cfg['tg_token'] . '/sendMessage');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => ['chat_id' => $cfg['tg_chat'], 'text' => $text], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
  $res = curl_exec($ch); curl_close($ch);
  $sent = (is_string($res) && strpos($res, '"ok":true') !== false) || $sent;
}
if (!$sent) { http_response_code(502); }
echo json_encode(['ok' => $sent]);
