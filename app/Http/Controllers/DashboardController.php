<?php

namespace App\Http\Controllers;

use App\Models\Plot;
use App\Models\Reading;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user  = $request->user();
        $plots = Plot::where('user_id', $user->id)->with(['sensors.latestReading'])->get();

        $summary = $plots->map(function (Plot $plot) {
            $sensors = $plot->sensors->map(function (Sensor $sensor) {
                $latest = $sensor->latestReading;
                return [
                    'id'     => $sensor->id,
                    'name'   => $sensor->name,
                    'type'   => $sensor->type,
                    'unit'   => $sensor->unit,
                    'status' => $sensor->status,
                    'latest' => $latest ? [
                        'value'      => $latest->value,
                        'status'     => $latest->status,
                        'created_at' => $latest->created_at,
                    ] : null,
                ];
            });

            $overallStatus = $this->computeStatus($sensors);

            return [
                'id'          => $plot->id,
                'name'        => $plot->name,
                'latitude'    => $plot->latitude,
                'longitude'   => $plot->longitude,
                'location'    => $plot->location_description,
                'area'        => $plot->area_hectares,
                'status'      => $overallStatus,
                'sensors'     => $sensors,
            ];
        });

        return response()->json(['plots' => $summary, 'generated_at' => now()]);
    }

    private function computeStatus($sensors): string
    {
        $statuses = $sensors->pluck('latest.status')->filter()->values();
        if ($statuses->contains('critical')) return 'critical';
        if ($statuses->contains('alert'))    return 'alert';
        return 'optimal';
    }

    public function realtime(Request $request): JsonResponse
    {
        $plotId  = $request->query('plot_id');
        $sensors = Sensor::when($plotId, fn($q) => $q->where('plot_id', $plotId))
            ->with('latestReading')
            ->get();

        // Simulate new reading on each poll
        foreach ($sensors as $sensor) {
            $this->injectSimulatedReading($sensor);
        }

        return response()->json($sensors->load('latestReading'));
    }

    private function injectSimulatedReading(Sensor $sensor): void
    {
        $data = match ($sensor->type) {
            'humidity'    => ['value' => rand(40, 80),   'unit' => '%',   'status' => $this->humidityStatus(rand(40, 80))],
            'temperature' => ['value' => rand(10, 25),   'unit' => '°C',  'status' => $this->tempStatus(rand(10, 25))],
            'nutrients'   => ['value' => rand(150, 900), 'unit' => 'ppm', 'status' => $this->nutrientStatus(rand(150, 900))],
        };

        Reading::create(array_merge(['sensor_id' => $sensor->id], $data));
    }

    private function humidityStatus(float $v): string
    {
        return $v < 45 || $v > 75 ? ($v < 40 || $v > 80 ? 'critical' : 'alert') : 'optimal';
    }

    private function tempStatus(float $v): string
    {
        return $v < 12 || $v > 22 ? ($v < 10 || $v > 25 ? 'critical' : 'alert') : 'optimal';
    }

    private function nutrientStatus(float $v): string
    {
        return $v < 200 ? 'critical' : ($v < 350 ? 'alert' : 'optimal');
    }
}
