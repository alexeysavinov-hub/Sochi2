<?php
// Настройки BIOSOMA. Заполните перед запуском. Прямой доступ к файлу закрыт в .htaccess.
return [
  'mail_to'      => 'booking@example.ru',     // куда приходят заявки
  'mail_from'    => 'no-reply@example.ru',    // адрес отправителя на вашем домене
  'tg_token'     => '',                       // токен Telegram-бота от @BotFather
  'tg_chat'      => '',                       // chat_id менеджера или группы
  'yk_shop_id'   => '',                       // ЮKassa: shopId
  'yk_secret'    => '',                       // ЮKassa: секретный ключ
  'yk_receipt'   => true,                     // формировать чек по 54-ФЗ через ЮKassa
  'yk_vat_code'  => 1,                        // код ставки НДС для чека (1 — без НДС)
  'prepay_share' => 0.3,                      // доля предоплаты
  'return_url'   => 'https://example.ru/?paid=1',
  'prices'       => ['reset' => 89000, 'detox' => 168000, 'longevity' => 256000, 'deep' => 390000, 'day' => 18000],
];
