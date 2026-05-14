<?php

namespace App\Http\Controllers;

use App\Models\Plot;
use App\Models\Reading;
use App\Models\Report;
use App\Models\Sensor;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'    => ['required', 'in:daily,weekly,full'],
            'plot_id' => ['required', 'integer', 'exists:plots,id'],
        ]);

        $plot     = Plot::with('sensors')->findOrFail($data['plot_id']);

        // Logo Base64 - Use forward slashes for Windows compatibility
        $logoPath   = str_replace('\\', '/', public_path('img/AGRITECH_NUEVO_LOGO.jpg'));
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoContent = @file_get_contents($logoPath);
            if ($logoContent) {
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoContent);
            }
        }

        $since    = match ($data['type']) {
            'daily'  => Carbon::now()->subDay(),
            'weekly' => Carbon::now()->subWeek(),
            'full'   => Carbon::now()->subMonth(),
        };

        $sensorsData = $plot->sensors->map(function (Sensor $sensor) use ($since) {
            $readings = Reading::where('sensor_id', $sensor->id)
                ->where('created_at', '>=', $since)
                ->orderBy('created_at')
                ->get(['value', 'unit', 'status', 'created_at']);

            $values = $readings->pluck('value');
            $labels = $readings->pluck('created_at')->map(fn($t) => Carbon::parse($t)->format('H:i'))->toArray();
            $data   = $values->toArray();

            // QuickChart URL generation - simpler config for compatibility
            $chartConfig = [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => $sensor->name,
                        'data' => $data,
                        'borderColor' => '#00b4d8',
                        'fill' => false
                    ]]
                ]
            ];
            
            $chartUrl = "https://quickchart.io/chart?w=500&h=250&v=2.9.4&c=" . urlencode(json_encode($chartConfig));
            
            $chartBase64 = null;
            try {
                $context = stream_context_create([
                    "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
                    "http" => ["timeout" => 10]
                ]);
                $imageData = @file_get_contents($chartUrl, false, $context);
                if ($imageData) {
                    $chartBase64 = 'data:image/png;base64,' . base64_encode($imageData);
                }
            } catch (\Exception $e) {
                \Log::error("PDF Chart Error: " . $e->getMessage());
            }

            return [
                'sensor'       => $sensor,
                'readings'     => $readings,
                'avg'          => round($values->avg(), 2),
                'min'          => $values->min(),
                'max'          => $values->max(),
                'chart_url'    => $chartBase64,
                'optimal_pct'  => $readings->where('status', 'optimal')->count() / max($readings->count(), 1) * 100,
                'alert_pct'    => $readings->where('status', 'alert')->count()   / max($readings->count(), 1) * 100,
                'critical_pct' => $readings->where('status', 'critical')->count()/ max($readings->count(), 1) * 100,
            ];
        });

        $snapshot = [
            'plot'        => $plot->only(['id', 'name', 'location_description', 'area_hectares']),
            'type'        => $data['type'],
            'generated_at'=> now()->toDateTimeString(),
            'sensors'     => $sensorsData->toArray(),
        ];

        // Generate Analysis Summary
        $humAvg  = $sensorsData->where('sensor.type', 'humidity')->first()['avg'] ?? 0;
        $tempMax = $sensorsData->where('sensor.type', 'temperature')->first()['max'] ?? 0;
        $nutMin  = $sensorsData->where('sensor.type', 'nutrients')->first()['min'] ?? 0;

        $analysisSummary = "El promedio de humedad fue {$humAvg}%. La temperatura máxima registrada fue {$tempMax}°C. El valor mínimo de nutrientes fue {$nutMin} mg/L. Estos resultados muestran la importancia del monitoreo en tiempo real para optimizar el riego y la fertilización en el cultivo de papa.";

        // Ensure DomPDF is initialized with all necessary options
        $pdf = Pdf::setOption([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'logOutputFile' => storage_path('logs/dompdf.html'),
        ])->loadView('reports.pdf', compact('plot', 'sensorsData', 'data', 'logoBase64', 'analysisSummary'))
          ->setPaper('a4');
        $filename = "report_{$data['type']}_{$plot->id}_" . now()->format('Ymd_His') . '.pdf';
        $path     = "reports/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        $report = Report::create([
            'user_id'       => $request->user()->id,
            'plot_id'       => $plot->id,
            'type'          => $data['type'],
            'file_path'     => $path,
            'data_snapshot' => $snapshot,
        ]);

        return response()->json([
            'report'       => $report,
            'download_url' => url("/api/reports/{$report->id}/download"),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $reports = Report::where('user_id', $request->user()->id)
            ->with('plot')
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function download(Report $report)
    {
        if (! Storage::disk('public')->exists($report->file_path)) {
            return response()->json(['message' => 'Archivo no encontrado.'], 404);
        }
        return Storage::disk('public')->download($report->file_path);
    }
}
