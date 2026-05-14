<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plot;
use App\Models\Sensor;
use App\Models\Reading;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'     => 'Admin Agritech',
            'email'    => 'admin@agritech.co',
            'password' => Hash::make('Agritech2024!'),
            'role'     => 'admin',
        ]);

        $plot = Plot::create([
            'name'                 => 'Predio El Páramo',
            'latitude'             => 5.3450,
            'longitude'            => -73.9826,
            'area_hectares'        => 12.50,
            'location_description' => 'Carmen de Carupa, Cundinamarca',
            'user_id'              => $admin->id,
        ]);

        $sensors = [
            ['name' => 'Sensor Humedad S1',      'type' => 'humidity',     'unit' => '%'],
            ['name' => 'Sensor Temperatura T1',  'type' => 'temperature',  'unit' => '°C'],
            ['name' => 'Sensor Nutrientes N1',   'type' => 'nutrients',    'unit' => 'ppm'],
        ];

        foreach ($sensors as $sData) {
            $sensor = Sensor::create(array_merge($sData, ['plot_id' => $plot->id]));
            $this->seedReadings($sensor);
        }
    }

    private function seedReadings(Sensor $sensor): void
    {
        $points = 288; // 24h at 5-min intervals
        for ($i = $points; $i >= 0; $i--) {
            ['value' => $value, 'status' => $status] = $this->simulateValue($sensor->type);
            Reading::create([
                'sensor_id'  => $sensor->id,
                'value'      => $value,
                'unit'       => $sensor->unit,
                'status'     => $status,
                'created_at' => Carbon::now()->subMinutes($i * 5),
                'updated_at' => Carbon::now()->subMinutes($i * 5),
            ]);
        }
    }

    private function simulateValue(string $type): array
    {
        return match ($type) {
            'humidity'    => $this->humidityReading(),
            'temperature' => $this->temperatureReading(),
            'nutrients'   => $this->nutrientsReading(),
        };
    }

    private function humidityReading(): array
    {
        $v = rand(35, 85);
        return ['value' => $v, 'status' => $v < 45 || $v > 75 ? ($v < 40 || $v > 80 ? 'critical' : 'alert') : 'optimal'];
    }

    private function temperatureReading(): array
    {
        $v = rand(8, 28);
        return ['value' => $v, 'status' => $v < 10 || $v > 22 ? ($v < 8 || $v > 25 ? 'critical' : 'alert') : 'optimal'];
    }

    private function nutrientsReading(): array
    {
        $v = rand(100, 900);
        return ['value' => $v, 'status' => $v < 200 ? 'critical' : ($v < 350 ? 'alert' : 'optimal')];
    }
}
