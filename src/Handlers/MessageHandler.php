<?php

namespace App\Handlers;

use App\Interfaces\MessageHandlerInterface;
use App\Services\CityService;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Message;

# TODO: вынести формирования текста сообщений
class MessageHandler implements MessageHandlerInterface
{
    private CityService $cityService;
    
    public function __construct(CityService $cityService)
    {
        $this->cityService = $cityService;
    }
    
    public function handleMessage(Message $message): void
    {
        $chatId = $message->getChat()->getId();
        switch ($message->getText()) {
            case "/start":
                $this->handleStartCommand($chatId);
                break;
            case "/help":
                $this->handleHelpCommand($chatId);
                break;
            case "/weather":
                $this->handleWeatherCommand($chatId);
                break;
            default:
                $this->handleGenericMessage($chatId);
                break;
        }
    }
    

    public function getListCitiesCommand(int $chatId, int $page = 1): array
    {
        $keyboard = $this->buildCityKeyboard($page);
        
        return [
            "chat_id" => $chatId,
            "text" => "<b>Прогноз погоды</b>\n\nВыберите город для получения текущей погоды:",
            "parse_mode" => "HTML",
            "reply_markup" => json_encode($keyboard)
        ];
    }
    
    private function handleStartCommand(int $chatId): void
    {
        Request::sendMessage($this->getListCitiesCommand($chatId));
    }
    
    private function handleHelpCommand(int $chatId): void
    {
        $helpText = $this->buildHelpText();
        
        Request::sendMessage([
            "chat_id" => $chatId,
            "text" => $helpText,
            "parse_mode" => "HTML"
        ]);
    }
    
    private function handleWeatherCommand(int $chatId): void
    {
        $this->handleStartCommand($chatId);
    }
    
    private function handleGenericMessage(int $chatId): void
    {
        $response = "Привет! Я бот погоды. Используйте команды:\n";
        $response .= "/start - Начать работу\n";
        $response .= "/weather - Выбрать город\n";
        $response .= "/help - Справка";

        Request::sendMessage([
            "chat_id" => $chatId,
            "text" => $response
        ]);
        
    }
    
    private function buildCityKeyboard(int $page = 1): array
    {
        $citiesAll = $this->cityService->getAllCities();
        $keyboard = ["inline_keyboard" => []];
        $viewCities = 5;
        $start = ($page - 1) * $viewCities;
        $totalCities = count($citiesAll);
        $totalPages = ceil($totalCities / $viewCities);

        if ($totalCities === 0) {
            return $keyboard;
        }

        // Получаем города для текущей страницы
        $cities = array_slice($citiesAll, $start, $viewCities, true);

        $row = [];
        foreach ($cities as $code => $city) {
            $row[] = [
                "text" => "{$city->getName()}", 
                "callback_data" => "weather_{$code}"
            ];

            if (count($row) === 2) {
                $keyboard["inline_keyboard"][] = $row;
                $row = [];
            }
        }
        
        // Добавляем оставшиеся города в последний ряд
        if (!empty($row)) {
            $keyboard["inline_keyboard"][] = $row;
        }

        // Добавляем навигационные кнопки
        $navigationRow = [];
        
        if ($page > 1) {
            $navigationRow[] = [
                "text" => "⬅️", 
                "callback_data" => "list_cities_" . ($page - 1)
            ];
        }
        
        if ($page < $totalPages) {
            $navigationRow[] = [
                "text" => "➡️", 
                "callback_data" => "list_cities_" . ($page + 1)
            ];
        }
        
        if (!empty($navigationRow)) {
            $keyboard["inline_keyboard"][] = $navigationRow;
        }
        
        return $keyboard;
    }
    
    private function buildHelpText(): string
    {
        $helpText = "<b>Бот погоды</b>\n\n";
        $helpText .= "<b>Доступные команды:</b>\n";
        $helpText .= "• /start - Начать работу с ботом\n";
        $helpText .= "• /weather - Выбор города для погоды\n";
        $helpText .= "• /help - Эта справка\n\n";
        $helpText .= "<b>Доступные города:</b>\n";
        
        $cities = $this->cityService->getAllCities();
        foreach ($cities as $city) {
            $helpText .= "• {$city->getName()}\n";
        }
        
        return $helpText;
    }
} 