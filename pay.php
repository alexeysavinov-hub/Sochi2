<?php
// BIOSOMA — онлайн-предоплата через ЮKassa. PHP 7.4+ с cURL.
declare(strict_types=1);
$cfg = require __DIR__ . '/config.php';
$program = (string)($_GET['program'] ?? '');
$guests = max(1, min(10, (int)($_GET['guests'] ?? 1)));
$phone = preg_replace('/\D+/', '', (string)($_GET['phone'] ?? ''));
$price = $cfg['prices'][$program] ?? null;
if ($price === null || empty($cfg['yk_shop_id']) || empty($cfg['yk_secret'])) {
  http_response_code(400); header('Content-Type: text/plain; charset=utf-8');
  exit('Онлайн-оплата пока недоступна. Мы пришлём ссылку на оплату после подтверждения дат.');
}
$amount = number_format($price * $guests * (float)$cfg['prepay_share'], 2, '.', '');
$titles = ['reset' => 'RESET', 'detox' => 'DETOX', 'longevity' => 'LONGEVITY', 'deep' => 'DEEP RESET', 'day' => 'DAY RETREAT'];
$desc = mb_substr('Предоплата BIOSOMA ' . ($titles[$program] ?? $program) . ', гостей: ' . $guests, 0, 128);
$payload = [
  'amount' => ['value' => $amount, 'currency' => 'RUB'],
  'capture' => true,
  'confirmation' => ['type' => 'redirect', 'return_url' => $cfg['return_url']],
  'description' => $desc,
];
if (!empty($cfg['yk_receipt']) && strlen($phone) >= 11) {
  $payload['receipt'] = [
    'customer' => ['phone' => $phone],
    'items' => [[
      'description' => $desc, 'quantity' => '1.00',
      'amount' => ['value' => $amount, 'currency' => 'RUB'],
      'vat_code' => (int)$cfg['yk_vat_code'], 'payment_mode' => 'partial_prepayment', 'payment_subject' => 'service',
    ]],
  ];
}
$ch = curl_init('https://api.yookassa.ru/v3/payments');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_USERPWD => $cfg['yk_shop_id'] . ':' . $cfg['yk_secret'],
  CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Idempotence-Key: ' . bin2hex(random_bytes(16))],
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
  CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
]);
$res = json_decode((string)curl_exec($ch), true); curl_close($ch);
$url = $res['confirmation']['confirmation_url'] ?? '';
if ($url !== '') { header('Location: ' . $url, true, 302); exit; }
http_response_code(502); header('Content-Type: text/plain; charset=utf-8');
echo 'Не удалось создать платёж. Напишите нам — мы пришлём ссылку на оплату.';
