<?php

namespace App\Interfaces;

interface WeatherServiceInterface
{
    /**
     * Получить погоду для города
     * 
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getWeather($latitude, $longitude);
} 