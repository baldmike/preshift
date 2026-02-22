<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * WeatherController fetches current weather data for the user's location
 * via the Open-Meteo API (free, no API key required).
 */
class WeatherController extends Controller
{
    /**
     * WMO Weather interpretation codes mapped to human-readable descriptions.
     *
     * @see https://open-meteo.com/en/docs#weathervariables
     */
    private const WEATHER_DESCRIPTIONS = [
        0 => 'Clear Sky',
        1 => 'Mainly Clear',
        2 => 'Partly Cloudy',
        3 => 'Overcast',
        45 => 'Foggy',
        48 => 'Depositing Rime Fog',
        51 => 'Light Drizzle',
        53 => 'Moderate Drizzle',
        55 => 'Dense Drizzle',
        56 => 'Light Freezing Drizzle',
        57 => 'Dense Freezing Drizzle',
        61 => 'Slight Rain',
        63 => 'Moderate Rain',
        65 => 'Heavy Rain',
        66 => 'Light Freezing Rain',
        67 => 'Heavy Freezing Rain',
        71 => 'Slight Snowfall',
        73 => 'Moderate Snowfall',
        75 => 'Heavy Snowfall',
        77 => 'Snow Grains',
        80 => 'Slight Rain Showers',
        81 => 'Moderate Rain Showers',
        82 => 'Violent Rain Showers',
        85 => 'Slight Snow Showers',
        86 => 'Heavy Snow Showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm with Slight Hail',
        99 => 'Thunderstorm with Heavy Hail',
    ];

    /**
     * Return current weather and today's forecast for the user's location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $location = $request->user()->location;

        if (!$location || !$location->latitude || !$location->longitude) {
            return response()->json(['message' => 'Location coordinates not configured.'], 404);
        }

        $cacheKey = "weather_location_{$location->id}";

        $weather = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($location) {
            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                'temperature_unit' => 'fahrenheit',
                'wind_speed_unit' => 'mph',
                'timezone' => 'auto',
                'forecast_days' => 1,
            ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            $current = $data['current'] ?? [];
            $daily = $data['daily'] ?? [];

            return [
                'current' => [
                    'temperature' => round($current['temperature_2m'] ?? 0),
                    'feels_like' => round($current['apparent_temperature'] ?? 0),
                    'humidity' => round($current['relative_humidity_2m'] ?? 0),
                    'wind_speed' => round($current['wind_speed_10m'] ?? 0),
                    'weather_code' => $current['weather_code'] ?? 0,
                    'description' => self::WEATHER_DESCRIPTIONS[$current['weather_code'] ?? 0] ?? 'Unknown',
                ],
                'today' => [
                    'high' => round(($daily['temperature_2m_max'][0] ?? 0)),
                    'low' => round(($daily['temperature_2m_min'][0] ?? 0)),
                    'weather_code' => $daily['weather_code'][0] ?? 0,
                    'description' => self::WEATHER_DESCRIPTIONS[$daily['weather_code'][0] ?? 0] ?? 'Unknown',
                ],
            ];
        });

        if ($weather === null) {
            return response()->json(['message' => 'Unable to fetch weather data.'], 502);
        }

        return response()->json($weather);
    }
}
