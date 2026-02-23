<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManagerLogRequest;
use App\Http\Requests\UpdateManagerLogRequest;
use App\Http\Resources\ManagerLogResource;
use App\Models\Event;
use App\Models\ManagerLog;
use App\Models\ScheduleEntry;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * ManagerLogController manages daily operational log entries for a location.
 *
 * Each log captures freeform manager notes along with auto-populated snapshots
 * of weather, events, and scheduled staff at creation time. Snapshots are
 * immutable -- updates only allow editing the body text.
 */
class ManagerLogController extends Controller
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
     * List all manager logs for the authenticated user's location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of manager logs ordered by date descending.
     */
    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $logs = ManagerLog::where('location_id', $locationId)
            ->with('creator')
            ->orderByDesc('log_date')
            ->get();

        return response()->json(ManagerLogResource::collection($logs));
    }

    /**
     * Create a new manager log with auto-populated snapshots.
     *
     * Fetches current weather, today's events, and today's schedule and
     * saves them as immutable JSON snapshots alongside the manager's notes.
     *
     * @param  \App\Http\Requests\StoreManagerLogRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created log with a 201 status.
     */
    public function store(StoreManagerLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $location = $user->location;
        $logDate = $validated['log_date'];

        $log = ManagerLog::create([
            'location_id' => $user->location_id,
            'created_by' => $user->id,
            'log_date' => $logDate,
            'body' => $validated['body'],
            'weather_snapshot' => $this->fetchWeatherSnapshot($location),
            'events_snapshot' => $this->fetchEventsSnapshot($location->id, $logDate),
            'schedule_snapshot' => $this->fetchScheduleSnapshot($location->id, $logDate),
        ]);

        $log->load('creator');

        return response()->json(new ManagerLogResource($log), 201);
    }

    /**
     * Update an existing manager log (body only -- snapshots are immutable).
     *
     * @param  \App\Http\Requests\UpdateManagerLogRequest  $request
     * @param  \App\Models\ManagerLog  $managerLog  The log to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated log.
     */
    public function update(UpdateManagerLogRequest $request, ManagerLog $managerLog): JsonResponse
    {
        $this->authorize('update', $managerLog);

        $managerLog->update($request->validated());

        return response()->json(new ManagerLogResource($managerLog));
    }

    /**
     * Delete a manager log permanently.
     *
     * @param  \App\Models\ManagerLog  $managerLog  The log to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(ManagerLog $managerLog): Response
    {
        $this->authorize('delete', $managerLog);

        $managerLog->delete();

        return response()->noContent();
    }

    /**
     * Fetch weather data for snapshot from Open-Meteo API.
     *
     * Reuses the same API call and WMO code map as WeatherController.
     * Returns null if the location has no coordinates or the API call fails.
     *
     * @param  \App\Models\Location  $location
     * @return array|null
     */
    private function fetchWeatherSnapshot($location): ?array
    {
        if (!$location || !$location->latitude || !$location->longitude) {
            return null;
        }

        $cacheKey = "weather_location_{$location->id}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($location) {
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
    }

    /**
     * Build an events snapshot for the given location and date.
     *
     * @param  int     $locationId
     * @param  string  $date
     * @return array
     */
    private function fetchEventsSnapshot(int $locationId, string $date): array
    {
        return Event::where('location_id', $locationId)
            ->forDate($date)
            ->with('creator')
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'event_time' => $event->event_time,
                'created_by' => $event->creator?->name,
            ])
            ->toArray();
    }

    /**
     * Build a schedule snapshot for the given location and date.
     *
     * Only includes entries from published schedules so the snapshot
     * reflects what staff actually see.
     *
     * @param  int     $locationId
     * @param  string  $date
     * @return array
     */
    private function fetchScheduleSnapshot(int $locationId, string $date): array
    {
        return ScheduleEntry::whereHas('schedule', function ($q) use ($locationId) {
            $q->where('location_id', $locationId)
              ->where('status', 'published');
        })
            ->whereDate('date', $date)
            ->with(['user', 'shiftTemplate'])
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'user_name' => $entry->user?->name,
                'role' => $entry->role,
                'shift_name' => $entry->shiftTemplate?->name,
                'start_time' => $entry->shiftTemplate?->start_time,
            ])
            ->toArray();
    }
}
