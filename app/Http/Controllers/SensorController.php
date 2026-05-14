<?php

namespace App\Http\Controllers;

use App\Models\Reading;
use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'sensor_id' => ['required', 'integer', 'exists:sensors,id'],
            'range'     => ['sometimes', 'in:day,week'],
        ]);

        $range = $request->query('range', 'day');
        $since = $range === 'week' ? Carbon::now()->subWeek() : Carbon::now()->subDay();

        $readings = Reading::where('sensor_id', $request->query('sensor_id'))
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get(['value', 'unit', 'status', 'created_at']);

        return response()->json(['readings' => $readings, 'range' => $range]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sensor_id' => ['required', 'integer', 'exists:sensors,id'],
            'value'     => ['required', 'numeric'],
            'unit'      => ['required', 'string', 'max:10'],
        ]);

        $sensor  = Sensor::findOrFail($data['sensor_id']);
        $status  = $this->computeStatus($sensor->type, $data['value']);
        $reading = Reading::create(array_merge($data, ['status' => $status]));

        return response()->json($reading, 201);
    }

    private function computeStatus(string $type, float $value): string
    {
        return match ($type) {
            'humidity'    => $value < 45 || $value > 75 ? ($value < 40 || $value > 80 ? 'critical' : 'alert') : 'optimal',
            'temperature' => $value < 12 || $value > 22 ? ($value < 10 || $value > 25 ? 'critical' : 'alert') : 'optimal',
            'nutrients'   => $value < 200 ? 'critical' : ($value < 350 ? 'alert' : 'optimal'),
            default       => 'optimal',
        };
    }

    public function index(): JsonResponse
    {
        return response()->json(Sensor::with('plot', 'latestReading')->get());
    }
}
