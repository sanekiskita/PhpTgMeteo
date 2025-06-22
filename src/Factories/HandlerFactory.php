<?php

namespace App\Factories;

use App\Handlers\MessageHandler;
use App\Handlers\WeatherCallbackHandler;
use App\Interfaces\MessageHandlerInterface;
use App\Interfaces\CallbackHandlerInterface;
use App\Services\CityService;
use App\Services\OpenMeteoWeatherService;

class HandlerFactory
{
    private CityService $cityService;
    private OpenMeteoWeatherService $weatherService;

    public function __construct(
        CityService $cityService,
        OpenMeteoWeatherService $weatherService
    ) {
        $this->cityService = $cityService;
        $this->weatherService = $weatherService;
    }
    
    public function createMessageHandler(): MessageHandlerInterface
    {
        return new MessageHandler($this->cityService);
    }
    
    public function createCallbackHandler(): CallbackHandlerInterface
    {
        return new WeatherCallbackHandler(
            $this->cityService,
            $this->weatherService,
            new MessageHandler($this->cityService)
        );
    }
} 