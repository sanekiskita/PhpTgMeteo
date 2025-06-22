<?php

namespace App\Interfaces;

use Longman\TelegramBot\Entities\CallbackQuery;

interface CallbackHandlerInterface
{
    /**
     * Обработать callback query
     * 
     * @param CallbackQuery $callbackQuery
     * @return void
     */
    public function handleCallback(CallbackQuery $callbackQuery): void;
} 