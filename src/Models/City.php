<?php

namespace App\Models;

class City
{
    private $name;
    private $latitude;
    private $longitude;
    private $timezone;
    public function __construct($name, $latitude, $longitude, $timezone)
    {
        $this->name = $name;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timezone = $timezone;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getLatitude()
    {
        return $this->latitude;
    }
    
    public function getLongitude()
    {
        return $this->longitude;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }
    
    public function toArray()
    {
        return [
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
    }
} 