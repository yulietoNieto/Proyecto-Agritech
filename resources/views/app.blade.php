<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AGRITECH — Monitoreo IoT Papa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<!-- AUTH SCREEN -->
<div id="auth-screen" class="auth-screen">
  <div class="auth-card">
    <div class="auth-logo">
      <img src="{{ asset('img/AGRITECH_NUEVO_LOGO.jpg') }}" alt="Logo AGRITECH" style="width:60px;object-fit:contain;border-radius:8px;">
      <span class="logo-text">AGRITECH</span>
    </div>
    <p class="auth-sub">Tecnología con sensores IoT para mejorar la producción de papa en el municipio de Carmen de Carupa</p>

    <div class="tab-group">
      <button class="tab-btn active" onclick="switchTab('login')">Iniciar Sesión</button>
      <button class="tab-btn" onclick="switchTab('register')">Registrarse</button>
    </div>

    <!-- Login form -->
    <div id="tab-login">
      <div class="field">
        <label>Correo electrónico</label>
        <input type="email" id="login-email" placeholder="admin@agritech.co" autocomplete="email">
      </div>
      <div class="field">
        <label>Contraseña</label>
        <input type="password" id="login-password" placeholder="••••••••" autocomplete="current-password">
      </div>
      <div id="login-error" class="form-error hidden"></div>
      <button class="btn-primary full" onclick="doLogin()">
        <i class="fas fa-sign-in-alt"></i> Ingresar
      </button>
      <p class="auth-hint">Demo: admin@agritech.co / Agritech2024!</p>
    </div>

    <!-- Register form -->
    <div id="tab-register" class="hidden">
      <div class="field">
        <label>Nombre completo</label>
        <input type="text" id="reg-name" placeholder="Tu nombre">
      </div>
      <div class="field">
        <label>Correo electrónico</label>
        <input type="email" id="reg-email" placeholder="tu@email.com">
      </div>
      <div class="field">
        <label>Contraseña</label>
        <input type="password" id="reg-password" placeholder="Mínimo 8 caracteres">
      </div>
      <div class="field">
        <label>Confirmar contraseña</label>
        <input type="password" id="reg-confirm" placeholder="Repite tu contraseña">
      </div>
      <div id="reg-error" class="form-error hidden"></div>
      <button class="btn-primary full" onclick="doRegister()">
        <i class="fas fa-user-plus"></i> Registrarse
      </button>
    </div>
  </div>
</div>

<!-- APP SHELL -->
<div id="app-shell" class="app-shell hidden">

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="{{ asset('img/AGRITECH_NUEVO_LOGO.jpg') }}" alt="Logo" class="logo-img-icon sidebar-small" style="width:32px;height:32px;object-fit:contain;border-radius:4px;">
      <span>AGRITECH</span>
    </div>
    <ul class="nav-list">
      <li><a href="#" class="nav-link active" data-page="dashboard"><i class="fas fa-th-large"></i><span>Dashboard</span></a></li>
      <li><a href="#" class="nav-link" data-page="monitoring"><i class="fas fa-microchip"></i><span>Monitoreo</span></a></li>
      <li><a href="#" class="nav-link" data-page="reports"><i class="fas fa-file-pdf"></i><span>Reportes</span></a></li>
    </ul>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><i class="fas fa-user"></i></div>
        <div>
          <div class="user-name" id="nav-user-name">—</div>
          <div class="user-role" id="nav-user-role">—</div>
        </div>
      </div>
      <button class="btn-logout" onclick="doLogout()" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></button>
    </div>
  </nav>

  <!-- Main content -->
  <main class="main-content">
    <div class="topbar">
      <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
      <div class="topbar-title" id="topbar-title">Dashboard</div>
      <div class="topbar-right">
        <span class="live-badge"><i class="fas fa-circle"></i> EN VIVO</span>
        <span id="clock" class="clock"></span>
      </div>
    </div>

    <!-- PAGES -->
    <div id="page-dashboard" class="page active">
      <!-- KPI Cards -->
      <div class="kpi-grid" id="kpi-grid">
        <div class="kpi-card skeleton"></div>
        <div class="kpi-card skeleton"></div>
        <div class="kpi-card skeleton"></div>
        <div class="kpi-card skeleton"></div>
      </div>

      <!-- Charts row 1: Humidity + Status donut -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="card-header">
            <h3>Humedad del suelo</h3>
            <span class="chart-legend">
              <span class="dot green"></span>Óptimo 45–75%
              <span style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;"><span style="width:18px;height:2px;background:#2563eb;display:inline-block;border-radius:1px;"></span> Lectura</span>
            </span>
          </div>
          <div class="chart-canvas-wrap">
            <canvas id="chart-humidity"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="card-header">
            <h3>Estado general</h3>
            <span class="chart-legend">
              <span class="dot green"></span>Óptimo
              <span class="dot yellow"></span>Alerta
              <span class="dot red"></span>Crítico
            </span>
          </div>
          <div class="chart-canvas-wrap">
            <canvas id="chart-status"></canvas>
          </div>
        </div>
      </div>

      <!-- Charts row 2: Temperature + Nutrients -->
      <div class="charts-row">
        <div class="chart-card">
          <div class="card-header">
            <h3>Temperatura</h3>
            <span class="chart-legend"><span class="dot yellow"></span>°C</span>
          </div>
          <div class="chart-canvas-wrap">
            <canvas id="chart-temp"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <div class="card-header">
            <h3>Nutrientes</h3>
          </div>
          <div class="chart-canvas-wrap">
            <canvas id="chart-nutrients"></canvas>
          </div>
        </div>
      </div>

      <!-- Location -->
      <div class="info-card">
        <div class="card-header"><h3><i class="fas fa-map-marker-alt"></i> Ubicación del Predio</h3></div>
        <div class="location-info">
          <div class="location-icon">📍</div>
          <div>
            <div class="location-name" id="plot-name">Cargando…</div>
            <div class="location-detail" id="plot-detail">—</div>
          </div>
          <div class="location-coords" id="plot-coords">—</div>
        </div>
      </div>
    </div>

    <!-- MONITORING PAGE -->
    <div id="page-monitoring" class="page hidden">
      <div class="page-header">
        <h2>Monitoreo en Tiempo Real</h2>
        <div class="controls">
          <select id="history-range" onchange="loadHistory()">
            <option value="day">Último día</option>
            <option value="week">Última semana</option>
          </select>
        </div>
      </div>

      <div class="sensors-grid" id="sensors-grid"></div>

      <div class="chart-card" style="margin-top:24px">
        <div class="card-header">
          <h3>Historial de Lecturas</h3>
          <select id="history-sensor" onchange="loadHistory()"></select>
        </div>
        <div class="chart-canvas-wrap">
          <canvas id="chart-history"></canvas>
        </div>
      </div>
    </div>

    <!-- REPORTS PAGE -->
    <div id="page-reports" class="page hidden">
      <div class="page-header"><h2>Generación de Reportes</h2></div>
      <div class="report-grid">
        <div class="report-type-card" onclick="setReportType('daily', this)">
          <div class="report-icon">📅</div>
          <div class="report-label">Reporte Diario</div>
          <div class="report-desc">Lecturas de las últimas 24 horas</div>
        </div>
        <div class="report-type-card" onclick="setReportType('weekly', this)">
          <div class="report-icon">📊</div>
          <div class="report-label">Reporte Semanal</div>
          <div class="report-desc">Análisis de los últimos 7 días</div>
        </div>
        <div class="report-type-card" onclick="setReportType('full', this)">
          <div class="report-icon">📋</div>
          <div class="report-label">Análisis Completo</div>
          <div class="report-desc">Reporte mensual con tendencias</div>
        </div>
      </div>
      <input type="hidden" id="report-type" value="daily">

      <div class="info-card" style="margin-top:16px">
        <button class="btn-primary" id="btn-gen-report" onclick="generateReport()">
          <i class="fas fa-file-pdf"></i> Generar PDF
        </button>
        <div id="report-status" class="report-status hidden"></div>
      </div>

      <div class="info-card" style="margin-top:16px">
        <div class="card-header"><h3>Historial de Reportes</h3></div>
        <div id="reports-list"></div>
      </div>
    </div>

  </main>

  <!-- CHATBOT UI -->
  <div id="chatbot-container" class="chatbot-container">
    <div id="chat-window" class="chat-window hidden">
      <div class="chat-header">
        <div class="chat-title">
          <i class="fas fa-robot"></i>
          <div>
            <strong>Agri-Bot</strong>
            <span class="online-status">En línea</span>
          </div>
        </div>
        <button class="btn-close-chat" onclick="toggleChat()"><i class="fas fa-times"></i></button>
      </div>
      <div id="chat-messages" class="chat-messages">
        <div class="msg bot">¡Hola! Soy Agri-Bot. ¿En qué puedo ayudarte hoy con tus cultivos?</div>
      </div>
      <div class="chat-input-area">
        <input type="text" id="chat-input" placeholder="Escribe tu consulta..." onkeypress="handleChatKey(event)">
        <button id="btn-send-chat" onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
      </div>
    </div>
    <button id="btn-chat-toggle" class="btn-chat-toggle" onclick="toggleChat()">
      <i class="fas fa-comment-dots"></i>
      <span class="chat-badge hidden">!</span>
    </button>
  </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
