<?php

namespace App\Interfaces;

use Longman\TelegramBot\Entities\Message;

interface MessageHandlerInterface
{
    /**
     * Обработать текстовое сообщение
     * 
     * @param Message $message
     * @return void
     */
    public function handleMessage(Message $message): void;

    /**
     * Получить список городов в виде клавиатуры
     * 
     * @param int $chatId
     * @param int $page
     * @return array
     */
    public function getListCitiesCommand(int $chatId, int $page = 1): array;
} 