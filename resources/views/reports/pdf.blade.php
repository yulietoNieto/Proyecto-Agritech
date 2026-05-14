<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }
  .header { background: linear-gradient(135deg, #0f3460 0%, #16213e 100%); color: #fff; padding: 24px 32px; }
  .header h1 { font-size: 22px; font-weight: 700; letter-spacing: 1px; }
  .header p  { font-size: 11px; opacity: .8; margin-top: 4px; }
  .badge { display:inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
  .badge-optimal  { background: #d1fae5; color: #065f46; }
  .badge-alert    { background: #fef3c7; color: #92400e; }
  .badge-critical { background: #fee2e2; color: #991b1b; }
  .section { padding: 20px 32px; border-bottom: 1px solid #e5e7eb; }
  .section h2 { font-size: 14px; font-weight: 700; color: #0f3460; margin-bottom: 12px; border-left: 4px solid #00b4d8; padding-left: 8px; }
  .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .meta-card { background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; }
  .meta-card .label { font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: .5px; }
  .meta-card .value { font-size: 16px; font-weight: 700; color: #0f3460; margin-top: 4px; }
  .meta-card .unit  { font-size: 10px; color: #94a3b8; }
  .sensor-block { margin-bottom: 20px; }
  .sensor-block h3 { font-size: 12px; font-weight: 700; margin-bottom: 8px; color: #16213e; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th { background: #0f3460; color: #fff; padding: 6px 10px; text-align: left; }
  td { padding: 5px 10px; border-bottom: 1px solid #f1f5f9; }
  tr:nth-child(even) td { background: #f8fafc; }
  .bar-wrap { background: #e2e8f0; border-radius: 4px; height: 10px; width: 100%; }
  .bar-fill  { border-radius: 4px; height: 10px; }
  .bg-green  { background: #10b981; }
  .bg-yellow { background: #f59e0b; }
  .bg-red    { background: #ef4444; }
  .interp    { background: #f0f9ff; border-left: 4px solid #00b4d8; padding: 12px; border-radius: 0 8px 8px 0; font-size: 11px; line-height: 1.6; }
  .footer    { padding: 16px 32px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e5e7eb; }
  .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 10px; }
  .stat { text-align: center; background: #f8fafc; border-radius: 6px; padding: 8px; }
  .stat .n { font-size: 15px; font-weight: 700; color: #0f3460; }
  .stat .l { font-size: 9px; color: #64748b; }
</style>
</head>
<body>

<div class="header">
  <table style="width:100%;border:none;background:transparent;">
    <tr>
      <td style="border:none;background:transparent;width:70px;">
        @if($logoBase64)
          <img src="{{ $logoBase64 }}" style="width:60px;height:60px;object-fit:contain;border-radius:8px;">
        @endif
      </td>
      <td style="border:none;background:transparent;color:#fff;vertical-align:middle;padding-left:15px;">
        <h1 style="color:#fff;margin:0;">AGRITECH — Reporte {{ strtoupper($data['type']) }}</h1>
        <p style="color:#fff;margin:4px 0 0;opacity:0.8;">{{ $plot->name }} · {{ $plot->location_description }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <h2>Información del Predio</h2>
  <div class="meta-grid">
    <div class="meta-card">
      <div class="label">Predio</div>
      <div class="value">{{ $plot->name }}</div>
    </div>
    <div class="meta-card">
      <div class="label">Área</div>
      <div class="value">{{ $plot->area_hectares }}<span class="unit"> ha</span></div>
    </div>
    <div class="meta-card">
      <div class="label">Ubicación</div>
      <div class="value" style="font-size:12px">{{ $plot->location_description }}</div>
    </div>
  </div>
</div>

@foreach($sensorsData as $item)
<div class="section">
  <h2>{{ $item['sensor']->name }} ({{ $item['sensor']->type }})</h2>

  <div class="stats-row">
    <div class="stat"><div class="n">{{ $item['avg'] }} {{ $item['sensor']->unit }}</div><div class="l">Promedio</div></div>
    <div class="stat"><div class="n">{{ $item['min'] }} {{ $item['sensor']->unit }}</div><div class="l">Mínimo</div></div>
    <div class="stat"><div class="n">{{ $item['max'] }} {{ $item['sensor']->unit }}</div><div class="l">Máximo</div></div>
    <div class="stat"><div class="n">{{ $item['readings']->count() }}</div><div class="l">Lecturas</div></div>
  </div>

  <p style="margin-bottom:6px;font-size:10px;font-weight:600;">Distribución de estados:</p>
  <table style="margin-bottom:12px">
    <tr>
      <th>Estado</th><th>%</th><th>Barra</th>
    </tr>
    <tr>
      <td><span class="badge badge-optimal">Óptimo</span></td>
      <td>{{ round($item['optimal_pct'], 1) }}%</td>
      <td><div class="bar-wrap"><div class="bar-fill bg-green" style="width:{{ $item['optimal_pct'] }}%"></div></div></td>
    </tr>
    <tr>
      <td><span class="badge badge-alert">Alerta</span></td>
      <td>{{ round($item['alert_pct'], 1) }}%</td>
      <td><div class="bar-wrap"><div class="bar-fill bg-yellow" style="width:{{ $item['alert_pct'] }}%"></div></div></td>
    </tr>
    <tr>
      <td><span class="badge badge-critical">Crítico</span></td>
      <td>{{ round($item['critical_pct'], 1) }}%</td>
      <td><div class="bar-wrap"><div class="bar-fill bg-red" style="width:{{ $item['critical_pct'] }}%"></div></div></td>
    </tr>
  </table>

  @if($item['chart_url'])
  <div style="text-align:center;margin-bottom:15px;">
    <img src="{{ $item['chart_url'] }}" style="width:100%;max-width:550px;border-radius:8px;border:1px solid #e5e7eb;">
  </div>
  @endif

  <div class="interp">
    <strong>Interpretación:</strong>
    @if($item['sensor']->type === 'humidity')
      @if($item['avg'] < 45) La humedad promedio es baja ({{ $item['avg'] }}%). Se recomienda activar riego.
      @elseif($item['avg'] > 75) La humedad promedio es alta ({{ $item['avg'] }}%). Verificar drenaje.
      @else La humedad ({{ $item['avg'] }}%) se encuentra en rango óptimo para el cultivo de papa (45–75%).
      @endif
    @elseif($item['sensor']->type === 'temperature')
      @if($item['avg'] < 12) Temperatura baja ({{ $item['avg'] }}°C). Riesgo de helada. Considerar protección.
      @elseif($item['avg'] > 22) Temperatura alta ({{ $item['avg'] }}°C). Monitorear estrés hídrico.
      @else Temperatura ({{ $item['avg'] }}°C) en rango óptimo para papa (12–22°C).
      @endif
    @else
      @if($item['avg'] < 200) Nivel de nutrientes crítico ({{ $item['avg'] }} ppm). Aplicar fertilización urgente.
      @elseif($item['avg'] < 350) Nivel de nutrientes bajo ({{ $item['avg'] }} ppm). Planificar fertilización.
      @else Nivel de nutrientes adecuado ({{ $item['avg'] }} ppm).
      @endif
    @endif
  </div>

  @if($item['readings']->count() > 0)
  <p style="margin:10px 0 6px;font-size:10px;font-weight:600;">Últimas 10 lecturas:</p>
  <table>
    <tr><th>Fecha/Hora</th><th>Valor</th><th>Estado</th></tr>
    @foreach($item['readings']->sortByDesc('created_at')->take(10) as $r)
    <tr>
      <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
      <td>{{ $r->value }} {{ $r->unit }}</td>
      <td><span class="badge badge-{{ $r->status }}">{{ $r->status }}</span></td>
    </tr>
    @endforeach
  </table>
  @endif
</div>
@endforeach

<div class="section">
  <h2>Análisis de resultados</h2>
  <p style="font-size: 11px; line-height: 1.5; color: #1e293b;">
    {{ $analysisSummary }}
  </p>
</div>

<div class="footer">
  AGRITECH · Sistema de Monitoreo IoT · Carmen de Carupa, Cundinamarca · Reporte generado automáticamente
</div>
</body>
</html>
