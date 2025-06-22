<?php

require_once __DIR__ . "/../vendor/autoload.php";

use App\Services\TelegramService;
use App\Services\CityService;
use App\Services\OpenMeteoWeatherService;
use App\Factories\HandlerFactory;

// Загружаем конфигурацию
$config = require __DIR__ . "/../config/config.php";

try {
    // Создаем сервисы
    $cityService = new CityService();
    $weatherService = new OpenMeteoWeatherService();
    
    // Создаем логгер
    $logger = new \Monolog\Logger("telegram_bot");
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($config["app"]["log_file"]));
    
    // Создаем фабрику обработчиков
    $handlerFactory = new HandlerFactory($cityService, $weatherService, $logger);
    
    // Создаем TelegramService
    $telegramService = new TelegramService($config, $handlerFactory, $logger);
    
    // Обрабатываем webhook
    $telegramService->getTelegram()->handle();
    
} catch (Exception $e) {
    $logger->error('Webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error';
}
?>