<?php
// Assistant configuration. Fill these values before using Telegram forwarding.
return [
    // Telegram bot token (e.g. '123456:ABC-DEF...')
    'telegram_bot_token' => '',
    // Telegram chat id (user or group id) to receive lead notifications
    'telegram_chat_id' => '',
    // storage path for leads (relative to project root)
    'storage_file' => __DIR__ . '/../storage/assistant_leads.json',
];
