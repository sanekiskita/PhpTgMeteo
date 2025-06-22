<?php

namespace App\Services;

use App\Interfaces\WeatherServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenMeteoWeatherService implements WeatherServiceInterface
{
    private $client;
    private $apiUrl = "https://api.open-meteo.com/v1";
    
    public function __construct()
    {
        $this->client = new Client();
    }
    
    public function getWeather($latitude, $longitude)
    {
        try {
            $url = $this->apiUrl . "/forecast";
            $params = [
                "latitude" => $latitude,
                "longitude" => $longitude,
                "current" => "temperature_2m,relative_humidity_2m,pressure_msl,precipitation,weather_code",
                "timezone" => "auto"
            ];
            
            $response = $this->client->get($url, ["query" => $params]);
            $data = json_decode($response->getBody(), true);
            
            if (!$data || !isset($data["current"])) {
                throw new \Exception("Неверный ответ от API погоды");
            }
            
            return $this->formatWeatherData($data["current"]);
            
        } catch (RequestException $e) {
            error_log("Ошибка запроса к API погоды: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            error_log("Ошибка обработки данных погоды: " . $e->getMessage());
            return null;
        }
    }
    
    private function formatWeatherData($current)
    {
        $weatherCodes = [
            0 => "Ясно",
            1 => "Преимущественно ясно",
            2 => "Переменная облачность",
            3 => "Пасмурно",
            45 => "Туман",
            48 => "Туман с инеем",
            51 => "Легкая морось",
            53 => "Морось",
            55 => "Сильная морось",
            61 => "Небольшой дождь",
            63 => "Дождь",
            65 => "Сильный дождь",
            71 => "Небольшой снег",
            73 => "Снег",
            75 => "Сильный снег",
            95 => "Гроза"
        ];
        
        $weatherDescription = isset($weatherCodes[$current["weather_code"]]) 
            ? $weatherCodes[$current["weather_code"]] 
            : "-";
        
        return [
            "temperature" => round($current["temperature_2m"], 1),
            "humidity" => $current["relative_humidity_2m"],
            "pressure" => round($current["pressure_msl"] / 1.333, 1), // Конвертация в мм рт.ст.
            "precipitation" => $current["precipitation"],
            "weather_description" => $weatherDescription,
            "timestamp" => $current["time"]
        ];
    }
} 