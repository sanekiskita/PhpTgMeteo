<?php

namespace App\Handlers;

use App\Handlers\MessageHandler;
use App\Interfaces\CallbackHandlerInterface;
use App\Services\CityService;
use App\Services\OpenMeteoWeatherService;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;

# TODO: вынести формирования текста сообщений
class WeatherCallbackHandler implements CallbackHandlerInterface
{
    private CityService $cityService;
    private OpenMeteoWeatherService $weatherService;
    private MessageHandler $messageHandler;

    public function __construct(
        CityService $cityService, 
        OpenMeteoWeatherService $weatherService,
        MessageHandler $messageHandler
    ) {
        $this->cityService = $cityService;
        $this->weatherService = $weatherService;
        $this->messageHandler = $messageHandler;
    }
    
    public function handleCallback(CallbackQuery $callbackQuery): void
    {
        $callbackData = $callbackQuery->getData();
        
        if (strpos($callbackData, "weather_update_") === 0) {
            $cityCode = str_replace("weather_update_", "", $callbackData);
            $this->handleWeatherUpdateRequest($callbackQuery, $cityCode);
            return;
        }

        if (strpos($callbackData, "weather_") === 0) {
            $cityCode = str_replace("weather_", "", $callbackData);
            $this->handleWeatherRequest($callbackQuery, $cityCode);
            return;
        }

        if (strpos($callbackData, "list_cities_") === 0) {
            $page = (int)str_replace("list_cities_", "", $callbackData);
            $this->handleListCitiesRequest($callbackQuery, $page);
            return;
        }
    }

    private function handleListCitiesRequest(CallbackQuery $callbackQuery, int $page): void
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $callbackQueryId = $callbackQuery->getId();
        $messageId = $callbackQuery->getMessage()->getMessageId();
        
        // Отвечаем на callback query
        Request::answerCallbackQuery([
            "callback_query_id" => $callbackQueryId,
            "text" => "Страница $page"
        ]);
        
        // Редактируем сообщение с новой клавиатурой
        $messageData = $this->messageHandler->getListCitiesCommand($chatId, $page);
        
        Request::editMessageText(array_merge($messageData, ["message_id" => $messageId]));
    }

    private function handleWeatherUpdateRequest(CallbackQuery $callbackQuery, string $cityCode): void
    {
        $city = $this->cityService->getCity($cityCode);
        $callbackQueryId = $callbackQuery->getId();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        
        if (!$city) {
            Request::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Город не найден"
            ]);
            return;
        }
        
        Request::answerCallbackQuery([
            "callback_query_id" => $callbackQueryId,
            "text" => "Обновляем погоду..."
        ]);
        
        Request::editMessageText(
            array_merge(
                $this->getWeatherMessage($cityCode),
                [
                    "message_id" => $callbackQuery->getMessage()->getMessageId(),
                    "chat_id" => $chatId
                ]
            )
        );
    }
    
    private function handleWeatherRequest(CallbackQuery $callbackQuery, string $cityCode): void
    {
        $city = $this->cityService->getCity($cityCode);
        $callbackQueryId = $callbackQuery->getId();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        
        if (!$city) {
            Request::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Город не найден"
            ]);
            return;
        }
        
        Request::answerCallbackQuery([
            "callback_query_id" => $callbackQueryId,
            "text" => "Получаем погоду..."
        ]);
        
        Request::sendMessage(
            array_merge(
                $this->getWeatherMessage($cityCode),
                ["chat_id" => $chatId]
            )
        );
    }

    private function getWeatherMessage($cityCode): array {
        $city = $this->cityService->getCity($cityCode);
        $weather = $this->weatherService->getWeather(
            $city->getLatitude(), 
            $city->getLongitude()
        );
        
        if ($weather) {
            $message = $this->formatWeatherMessage($city, $weather);
        } else {
            $message = "❌ Не удалось получить данные о погоде для {$city->getName()}";
        }

        $buttonUpdate = [
            "text" => "Обновить",
            "callback_data" => "weather_update_{$cityCode}"
        ];
        
        return [
            "text" => $message,
            "parse_mode" => "HTML",
            "reply_markup" => json_encode([
                "inline_keyboard" => [[
                    $buttonUpdate
                ]]
            ])
        ];
    }
    
    private function formatWeatherMessage($city, array $weather): string
    {
        $emoji = $this->getWeatherEmoji($weather["weather_description"]);
        $timeFormat = "d.m.Y H:i";
        
        try {
            $currentDate = new \DateTime();
            $currentDate->setTimezone(new \DateTimeZone($city->getTimezone()));
            $localTime = $currentDate->format($timeFormat);
        } catch (\Exception $e) {
            $localTime = "-";
        }

        try {
            $lastUpdate = new \DateTime($weather["timestamp"], new \DateTimeZone($city->getTimezone()));
            $lastUpdate->setTimezone(new \DateTimeZone('Europe/Moscow'));
            $lastUpdate = $lastUpdate->format($timeFormat);
        } catch (\Exception $e) {
            $lastUpdate = "-";
        }

        // TODO: дату отображать в виде сегодня, вчера, 2 дня назад, 3 дня назад иначе вермя
        return "{$emoji} <b>Погода в {$city->getName()}</b>\n\n" .
               "🌡 Температура: {$weather['temperature']}°C\n" .
               "💧 Влажность: {$weather['humidity']}%\n" .
               "🌪 Давление: {$weather['pressure']} мм рт.ст.\n" .
               "☁️ Погода: {$weather['weather_description']}\n" .
               "⏰ Местное время: {$localTime}\n" .
               "⏰ Последнее обновление (по москве): {$lastUpdate}";
    }
    
    private function getWeatherEmoji(string $description): string
    {
        $emojiMap = [
            "Ясно" => "☀️",
            "Преимущественно ясно" => "🌤",
            "Переменная облачность" => "⛅",
            "Пасмурно" => "☁️",
            "Туман" => "🌫",
            "Туман с инеем" => "🌨",
            "Легкая морось" => "🌧",
            "Морось" => "🌧",
            "Сильная морось" => "🌧",
            "Небольшой дождь" => "🌧",
            "Дождь" => "🌧",
            "Сильный дождь" => "⛈",
            "Небольшой снег" => "❄️",
            "Снег" => "❄️",
            "Сильный снег" => "❄️",
            "Гроза" => "⛈"
        ];
        
        return $emojiMap[$description] ?? "❓";
    }
} 