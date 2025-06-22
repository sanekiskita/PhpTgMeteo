<?php

namespace App\Services;

use App\Models\City;

class CityService
{
    private $cities = [];
    
    public function __construct()
    {
        $this->initializeCities();
    }
    
    private function initializeCities()
    {
        $this->cities = [
            "moscow" => new City("Москва", 55.7558, 37.6176, "Europe/Moscow"),
            "spb" => new City("Санкт-Петербург", 59.9311, 30.3609, "Europe/Moscow"),
            "novosibirsk" => new City("Новосибирск", 55.0084, 82.9357, "Asia/Novosibirsk"),
            "ekaterinburg" => new City("Екатеринбург", 56.8519, 60.6122, "Asia/Yekaterinburg"),
            "kazan" => new City("Казань", 55.8304, 49.0661, "Europe/Moscow"),
            "nizhny" => new City("Нижний Новгород", 56.2965, 43.9361, "Europe/Moscow"),
            "chelyabinsk" => new City("Челябинск", 55.1644, 61.4368, "Asia/Yekaterinburg"),
            "samara" => new City("Самара", 53.2001, 50.1500, "Europe/Samara"),
            "ufa" => new City("Уфа", 54.7388, 55.9721, "Asia/Yekaterinburg"),
            "rostov" => new City("Ростов-на-Дону", 47.2357, 39.7015, "Europe/Moscow")
        ];
    }
    
    public function getCity($cityCode)
    {
        return isset($this->cities[$cityCode]) ? $this->cities[$cityCode] : null;
    }
    
    public function getAllCities()
    {
        return $this->cities;
    }
} 