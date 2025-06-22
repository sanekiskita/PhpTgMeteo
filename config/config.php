<?php

return [
    "telegram" => [
        "bot_token" => getenv("TELEGRAM_BOT_TOKEN") ?: NULL,
        "bot_username" => getenv("TELEGRAM_BOT_USERNAME") ?: NULL,
        "webhook_url" => getenv("TELEGRAM_WEBHOOK_URL") ?: NULL,
    ],
    "app" => [
        "log_file" => __DIR__ . "/../logs/bot.log",
    ]
]; 