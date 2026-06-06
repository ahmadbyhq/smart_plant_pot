<?php
// C:\xampp\htdocs\smart-plant-pot\dashboard.php
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Plant Pot — IoT Dashboard</title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Google Fonts (preconnect for performance) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🪴</text></svg>">
</head>

<body>

  <!-- ═══════════════════════════════════════════════
     SIDEBAR OVERLAY (mobile)
     ═══════════════════════════════════════════════ -->
  <div id="sidebarOverlay" class="sidebar-overlay"></div>

  <!-- ═══════════════════════════════════════════════
     SIDEBAR
     ═══════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <a class="sidebar-brand">
      <div class="sidebar-brand-icon">🪴</div>
      <div class="sidebar-brand-text">
        <span class="sidebar-brand-title">Smart Plant Pot</span>
        <span class="sidebar-brand-sub">IoT Monitor v1.0</span>
      </div>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <span class="nav-section-label">Dashboard</span>

      <div class="nav-item">
        <button class="nav-link-custom active" data-section="realtime">
          <span class="nav-icon">📊</span>
          Realtime Monitoring
        </button>
      </div>

      <div class="nav-item">
        <button class="nav-link-custom" data-section="charts">
          <span class="nav-icon">📈</span>
          Grafik Sensor
        </button>
      </div>

      <span class="nav-section-label">Notifikasi</span>

      <div class="nav-item">
        <button class="nav-link-custom" data-section="alerts">
          <span class="nav-icon">🔔</span>
          Alert Panel
        </button>
      </div>

      <span class="nav-section-label">Riwayat</span>

      <div class="nav-item">
        <button class="nav-link-custom" data-section="history">
          <span class="nav-icon">📋</span>
          Riwayat Monitoring
        </button>
      </div>

      <div class="nav-item">
        <button class="nav-link-custom" data-section="alertlog">
          <span class="nav-icon">📜</span>
          Riwayat Alert
        </button>
      </div>
    </nav>

    <!-- Footer Status -->
    <div class="sidebar-footer">
      <div class="connection-status">
        <span class="status-dot" id="connDot"></span>
        <span id="connText">Terhubung • Live</span>
      </div>
    </div>
  </aside>

  <!-- ═══════════════════════════════════════════════
     MAIN WRAPPER
     ═══════════════════════════════════════════════ -->
  <div class="main-wrapper">

    <!-- ── TOP HEADER ── -->
    <header class="top-header">
      <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar" aria-label="Toggle Sidebar">
          <span class="burger-icon">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </button>
        <div>
          <h1 class="page-title" id="pageTitle">📊 Realtime Monitoring</h1>
        </div>
      </div>
      <div class="header-right">
        <div class="refresh-badge">
          <span class="refresh-indicator" id="refreshDot"></span>
          Refresh #<span id="refreshCount">0</span>
        </div>
        <div class="live-clock" id="liveClock">--:--:--</div>
        <button class="dark-toggle" id="darkToggle" title="Toggle Dark Mode">🌙</button>
      </div>
    </header>

    <!-- ════════════════════════════════════════════
         PAGE CONTENT
         ════════════════════════════════════════════ -->
    <main class="page-content">

      <!-- ══════════════════════════
             SECTION: REALTIME
             ══════════════════════════ -->
      <section data-section-content="realtime">

        <!-- Hero Banner -->
        <div class="hero-banner">
          <h2 class="hero-title">🌿 Smart Plant Pot Monitoring</h2>
          <p class="hero-sub">Pemantauan sensor tanaman secara realtime berbasis IoT</p>
          <div class="hero-meta">
            <span class="hero-badge">🕒 <span id="liveDate">—</span></span>
            <span class="hero-badge">⚡ Auto Refresh 5 Detik</span>
            <span class="hero-badge">📡 ESP32 Connected</span>
            <span class="hero-badge" id="lastUpdate">Menunggu data…</span>
          </div>
        </div>

        <!-- Stat Cards Row 1 -->
        <div class="row g-3 mb-4">

          <!-- Temperature -->
          <div class="col-6 col-lg-4 col-xl">
            <div class="stat-card temp">
              <div class="stat-header">
                <div>
                  <p class="stat-label">Suhu</p>
                  <p class="stat-value">
                    <span id="statTemp">--</span><span class="stat-unit">°C</span>
                  </p>
                  <div id="statTempBadge"></div>
                </div>
                <div class="stat-icon-wrap">🌡️</div>
              </div>
            </div>
          </div>

          <!-- Humidity -->
          <div class="col-6 col-lg-4 col-xl">
            <div class="stat-card hum">
              <div class="stat-header">
                <div>
                  <p class="stat-label">Kelembaban Udara</p>
                  <p class="stat-value">
                    <span id="statHum">--</span><span class="stat-unit">%</span>
                  </p>
                  <div id="statHumBadge"></div>
                </div>
                <div class="stat-icon-wrap">💧</div>
              </div>
              <div class="stat-progress">
                <div class="stat-progress-fill" id="progHum" style="width:0%"></div>
              </div>
            </div>
          </div>

          <!-- Soil Moisture -->
          <div class="col-6 col-lg-4 col-xl">
            <div class="stat-card soil">
              <div class="stat-header">
                <div>
                  <p class="stat-label">Kelembaban Tanah</p>
                  <p class="stat-value">
                    <span id="statSoil">--</span><span class="stat-unit">%</span>
                  </p>
                  <div id="statSoilBadge"></div>
                </div>
                <div class="stat-icon-wrap">🪴</div>
              </div>
              <div class="stat-progress">
                <div class="stat-progress-fill" id="progSoil" style="width:0%"></div>
              </div>
            </div>
          </div>

          <!-- Water Level -->
          <div class="col-6 col-lg-4 col-xl">
            <div class="stat-card water">
              <div class="stat-header">
                <div>
                  <p class="stat-label">Level Air</p>
                  <p class="stat-value">
                    <span id="statWater">--</span><span class="stat-unit">%</span>
                  </p>
                  <div id="statWaterBadge"></div>
                </div>
                <div class="stat-icon-wrap">🫧</div>
              </div>
              <div class="stat-progress">
                <div class="stat-progress-fill" id="progWater" style="width:0%"></div>
              </div>
            </div>
          </div>

          <!-- Pump Status -->
          <div class="col-12 col-lg-4 col-xl">
            <div class="stat-card pump">
              <div class="stat-header">
                <div>
                  <p class="stat-label">Status Pompa</p>
                  <p class="stat-value" id="statPump">--</p>
                  <div id="statPumpBadge"></div>
                </div>
                <div class="stat-icon-wrap">⚙️</div>
              </div>
              <p style="font-size:.75rem;color:var(--text-secondary);margin:8px 0 0;"
                id="statPumpLabel">Menunggu data…</p>
            </div>
          </div>

        </div><!-- /row stat cards -->

      </section><!-- /realtime -->


      <!-- ══════════════════════════
             SECTION: CHARTS
             ══════════════════════════ -->
      <section data-section-content="charts" style="display:none;">

        <div class="hero-banner" style="margin-bottom:24px;">
          <h2 class="hero-title" style="font-size:1.2rem;">📈 Grafik Sensor Realtime</h2>
          <p class="hero-sub">Visualisasi 20 data terakhir • Diperbarui setiap 5 detik</p>
        </div>

        <div class="row chart-grid g-4">

          <!-- Temp Chart -->
          <div class="col-12 col-xl-6">
            <div class="section-card">
              <div class="section-header">
                <h3 class="section-title">
                  <span class="section-icon">🌡️</span>
                  Suhu Udara
                </h3>
                <span class="stat-status-badge badge-normal" style="font-size:.7rem;">°C</span>
              </div>
              <div class="section-body">
                <div class="chart-container">
                  <canvas id="chartTemp"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Humidity Chart -->
          <div class="col-12 col-xl-6">
            <div class="section-card">
              <div class="section-header">
                <h3 class="section-title">
                  <span class="section-icon">💧</span>
                  Kelembaban Udara
                </h3>
                <span class="stat-status-badge badge-normal" style="font-size:.7rem;">%</span>
              </div>
              <div class="section-body">
                <div class="chart-container">
                  <canvas id="chartHum"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Soil Chart -->
          <div class="col-12 col-xl-6">
            <div class="section-card">
              <div class="section-header">
                <h3 class="section-title">
                  <span class="section-icon">🪴</span>
                  Kelembaban Tanah
                </h3>
                <span class="stat-status-badge badge-normal" style="font-size:.7rem;">%</span>
              </div>
              <div class="section-body">
                <div class="chart-container">
                  <canvas id="chartSoil"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Water Chart -->
          <div class="col-12 col-xl-6">
            <div class="section-card">
              <div class="section-header">
                <h3 class="section-title">
                  <span class="section-icon">🫧</span>
                  Level Air Tangki
                </h3>
                <span class="stat-status-badge badge-normal" style="font-size:.7rem;">%</span>
              </div>
              <div class="section-body">
                <div class="chart-container">
                  <canvas id="chartWater"></canvas>
                </div>
              </div>
            </div>
          </div>

        </div>
      </section><!-- /charts -->


      <!-- ══════════════════════════
             SECTION: ALERTS
             ══════════════════════════ -->
      <section data-section-content="alerts" style="display:none;">

        <!-- Alert Summary Stats -->
        <div class="row g-3 mb-4">
          <div class="col-4">
            <div class="mini-stat critical-mini">
              <div class="mini-stat-icon">🔴</div>
              <div class="mini-stat-body">
                <div class="mini-stat-val" id="statCritical">--</div>
                <div class="mini-stat-lbl">Critical</div>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="mini-stat warning-mini">
              <div class="mini-stat-icon">🟡</div>
              <div class="mini-stat-body">
                <div class="mini-stat-val" id="statWarning">--</div>
                <div class="mini-stat-lbl">Warning</div>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="mini-stat normal-mini">
              <div class="mini-stat-icon">🟢</div>
              <div class="mini-stat-body">
                <div class="mini-stat-val" id="statNormal">--</div>
                <div class="mini-stat-lbl">Normal</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Alert List -->
        <div class="section-card">
          <div class="section-header">
            <h3 class="section-title">
              <span class="section-icon">🔔</span>
              Alert Terbaru
            </h3>
            <span class="stat-status-badge badge-normal" style="font-size:.72rem;">
              ⚡ Live Update
            </span>
          </div>
          <div class="section-body" style="padding:16px;">
            <div id="alertPanel">
              <div class="alert-empty">⏳ Memuat data alert…</div>
            </div>
          </div>
        </div>

      </section><!-- /alerts -->


      <!-- ══════════════════════════
             SECTION: HISTORY MONITORING
             ══════════════════════════ -->
      <section data-section-content="history" style="display:none;">

        <div class="section-card">
          <div class="section-header">
            <h3 class="section-title">
              <span class="section-icon">📋</span>
              Riwayat Monitoring
              <span class="stat-status-badge badge-normal ms-2"
                id="monTotal" style="font-size:.7rem;">—</span>
            </h3>
          </div>
          <div class="section-body">

            <!-- Controls -->
            <div class="table-controls">
              <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="search" class="form-control" id="monSearch"
                  placeholder="Cari (status, waktu)…">
              </div>
              <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <label style="font-size:.8rem;color:var(--text-secondary);white-space:nowrap;">Per halaman:</label>
                <select class="form-select form-select-sm" id="monLimit"
                  style="width:70px;border-radius:var(--radius-sm);border:1px solid var(--border-color);background:var(--bg-body);color:var(--text-primary);">
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive-custom">
              <table class="data-table">
                <thead>
                  <tr>
                    <th data-sortcol="id" onclick="sortMon('id')">ID<span class="sort-icon">↓</span></th>
                    <th data-sortcol="temperature" onclick="sortMon('temperature')">Suhu<span class="sort-icon">⇅</span></th>
                    <th data-sortcol="humidity" onclick="sortMon('humidity')">Kelembaban<span class="sort-icon">⇅</span></th>
                    <th data-sortcol="soil_percent" onclick="sortMon('soil_percent')">Tanah<span class="sort-icon">⇅</span></th>
                    <th data-sortcol="water_percent" onclick="sortMon('water_percent')">Air<span class="sort-icon">⇅</span></th>
                    <th>Soil Alert</th>
                    <th>Water Alert</th>
                    <th>Temp Alert</th>
                    <th>Hum Alert</th>
                    <th data-sortcol="pump_status" onclick="sortMon('pump_status')">Pompa<span class="sort-icon">⇅</span></th>
                    <th data-sortcol="created_at" onclick="sortMon('created_at')">Waktu<span class="sort-icon">⇅</span></th>
                  </tr>
                </thead>
                <tbody id="monTbody">
                  <tr>
                    <td colspan="11" class="text-center py-4 text-secondary">
                      ⏳ Memuat data…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div id="monPagination"></div>

          </div>
        </div>
      </section><!-- /history -->


      <!-- ══════════════════════════
             SECTION: ALERT LOG
             ══════════════════════════ -->
      <section data-section-content="alertlog" style="display:none;">

        <div class="section-card">
          <div class="section-header">
            <h3 class="section-title">
              <span class="section-icon">📜</span>
              Riwayat Alert
              <span class="stat-status-badge badge-normal ms-2"
                id="alertTotal" style="font-size:.7rem;">—</span>
            </h3>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <!-- Filter tabs -->
              <div class="filter-tabs">
                <button class="filter-tab active" data-level="">Semua</button>
                <button class="filter-tab warning" data-level="WARNING">⚠️ Warning</button>
                <button class="filter-tab critical" data-level="CRITICAL">🔴 Critical</button>
                <button class="filter-tab" data-level="NORMAL">✅ Normal</button>
              </div>
              <!-- Per page -->
              <select class="form-select form-select-sm" id="alertLimit"
                style="width:70px;border-radius:var(--radius-sm);border:1px solid var(--border-color);background:var(--bg-body);color:var(--text-primary);">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>
          </div>
          <div class="section-body">
            <div class="table-responsive-custom">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tipe Sensor</th>
                    <th>Level</th>
                    <th>Pesan</th>
                    <th>Waktu</th>
                  </tr>
                </thead>
                <tbody id="alertTbody">
                  <tr>
                    <td colspan="5" class="text-center py-4 text-secondary">
                      ⏳ Memuat data…
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div id="alertPagination"></div>
          </div>
        </div>

      </section><!-- /alertlog -->


    </main><!-- /page-content -->

    <!-- Footer -->
    <footer style="padding:16px 28px;border-top:1px solid var(--border-color);
                   display:flex;justify-content:space-between;align-items:center;
                   font-size:.75rem;color:var(--text-secondary);flex-wrap:wrap;gap:8px;">
      <span>🪴 Smart Plant Pot IoT Dashboard &copy; <?= date('Y') ?></span>
      <span style="font-family:var(--font-mono);">ESP32 + PHP + MySQL + Chart.js</span>
    </footer>

  </div><!-- /main-wrapper -->

  <!-- ═══════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════ -->
  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

  <!-- Dashboard JS -->
  <script src="assets/js/app.js"></script>

</body>

</html>