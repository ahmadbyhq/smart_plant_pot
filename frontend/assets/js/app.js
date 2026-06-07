/**
 * Smart Plant Pot — Dashboard JS
 * Stack : Vanilla ES6 + Chart.js + Bootstrap 5
 * Fixed: sidebar, overlay blur, swipe, resize
 */

"use strict";

/* ════════════════════════════════════════════
   CONSTANTS & STATE
   ════════════════════════════════════════════ */
const API = "api/realtime.php";
const TICK = 5000;
const MAX_PTS = 20;

const state = {
    darkMode: false,
    sidebarOpen: false,
    charts: {},
    monTable: { page: 1, limit: 10, search: "", sort: "id", dir: "DESC" },
    alertTable: { page: 1, limit: 10, level: "" },
    refreshTimer: null,
    isOnline: true,
};

/* ════════════════════════════════════════════
   DOM HELPERS
   ════════════════════════════════════════════ */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
const el = (id) => document.getElementById(id);

/* ════════════════════════════════════════════
   DARK MODE
   ════════════════════════════════════════════ */
function initDarkMode() {
    state.darkMode = localStorage.getItem("spp-theme") === "dark";
    applyTheme();
}

function applyTheme() {
    document.documentElement.setAttribute(
        "data-theme",
        state.darkMode ? "dark" : "light",
    );
    const btn = el("darkToggle");
    if (btn) btn.innerHTML = state.darkMode ? "☀️" : "🌙";
    updateChartTheme();
}

function toggleDark() {
    state.darkMode = !state.darkMode;
    localStorage.setItem("spp-theme", state.darkMode ? "dark" : "light");
    applyTheme();
}

/* ════════════════════════════════════════════
   SIDEBAR — FIXED
   ════════════════════════════════════════════ */
function toggleSidebar() {
    if (window.innerWidth >= 992) return; // desktop: no toggle
    state.sidebarOpen = !state.sidebarOpen;
    _applySidebarState();
}

function closeSidebar() {
    if (window.innerWidth >= 992) return;
    state.sidebarOpen = false;
    _applySidebarState();
}

function _applySidebarState() {
    const sb = el("sidebar");
    const ov = el("sidebarOverlay");
    const btn = el("sidebarToggle");
    const isMobile = window.innerWidth < 992;

    if (!isMobile) {
        // Desktop: sidebar selalu terbuka, overlay tidak boleh ada
        if (sb) sb.classList.add("open");
        if (ov) ov.classList.remove("show");
        document.body.style.overflow = "";
        if (btn) btn.classList.remove("active");
        state.sidebarOpen = true; // force state
        return;
    }

    // Mobile behavior
    if (state.sidebarOpen) {
        sb.classList.add("open");
        ov.classList.add("show");
        document.body.style.overflow = "hidden";
        if (btn) btn.classList.add("active");
    } else {
        sb.classList.remove("open");
        ov.classList.remove("show");
        document.body.style.overflow = "";
        if (btn) btn.classList.remove("active");
    }
}

function setActiveNav(section) {
    $$(".nav-link-custom").forEach((l) => l.classList.remove("active"));
    const target = $(`.nav-link-custom[data-section="${section}"]`);
    if (target) target.classList.add("active");
    el("pageTitle").textContent =
        {
            realtime: "📊 Realtime Monitoring",
            charts: "📈 Grafik Sensor",
            alerts: "🔔 Alert Panel",
            history: "📋 Riwayat Monitoring",
            alertlog: "📜 Riwayat Alert",
        }[section] || "Dashboard";
}

/* ════════════════════════════════════════════
   SWIPE GESTURE — safe for Android
   ════════════════════════════════════════════ */
function _initSwipeGesture() {
    let touchStartX = 0;
    let touchStartY = 0;

    document.addEventListener(
        "touchstart",
        (e) => {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        },
        { passive: true },
    );

    document.addEventListener("touchend", (e) => {
        if (window.innerWidth >= 992) return;
        const dx = e.changedTouches[0].clientX - touchStartX;
        const dy = e.changedTouches[0].clientY - touchStartY;
        if (Math.abs(dx) < Math.abs(dy) * 1.2) return;
        if (Math.abs(dx) < 50) return;

        // Jangan ganggu scroll horizontal di tabel/chart
        const target = e.target.closest(
            ".table-responsive-custom, .chart-container",
        );
        if (target && target.scrollWidth > target.clientWidth) return;

        if (dx > 0 && touchStartX < 40 && !state.sidebarOpen) {
            toggleSidebar();
        } else if (dx < 0 && state.sidebarOpen) {
            closeSidebar();
        }
    });
}

/* ════════════════════════════════════════════
   LIVE CLOCK
   ════════════════════════════════════════════ */
function startClock() {
    function tick() {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        const str = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        const date = now.toLocaleDateString("id-ID", {
            weekday: "short",
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
        const clockEl = el("liveClock");
        if (clockEl) clockEl.textContent = str;
        const dateEl = el("liveDate");
        if (dateEl) dateEl.textContent = date;
    }
    tick();
    setInterval(tick, 1000);
}

/* ════════════════════════════════════════════
   CHART.JS (tidak berubah, tetap sama)
   ════════════════════════════════════════════ */
const CHART_COLORS = {
    temp: { border: "#ef4444", bg: "rgba(239,68,68,.12)" },
    hum: { border: "#3b82f6", bg: "rgba(59,130,246,.12)" },
    soil: { border: "#8b5cf6", bg: "rgba(139,92,246,.12)" },
    water: { border: "#0ea5e9", bg: "rgba(14,165,233,.12)" },
};

function chartDefaults() {
    const dark = state.darkMode;
    return {
        gridColor: dark ? "rgba(255,255,255,.06)" : "rgba(0,0,0,.06)",
        textColor: dark ? "#94a3b8" : "#64748b",
    };
}

function buildChart(canvasId, label, color) {
    const ctx = el(canvasId);
    if (!ctx) return null;
    const { gridColor, textColor } = chartDefaults();
    return new Chart(ctx, {
        type: "line",
        data: {
            labels: [],
            datasets: [
                {
                    label,
                    data: [],
                    borderColor: color.border,
                    backgroundColor: color.bg,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.4,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            interaction: { intersect: false, mode: "index" },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: state.darkMode ? "#1e293b" : "#fff",
                    titleColor: state.darkMode ? "#f1f5f9" : "#0f172a",
                    bodyColor: state.darkMode ? "#94a3b8" : "#64748b",
                    borderColor: state.darkMode ? "#334155" : "#e2e8f0",
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        maxTicksLimit: 6,
                        font: { family: "JetBrains Mono", size: 10 },
                    },
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        font: { family: "JetBrains Mono", size: 10 },
                    },
                },
            },
        },
    });
}

function initCharts() {
    state.charts.temp = buildChart("chartTemp", "Suhu (°C)", CHART_COLORS.temp);
    state.charts.hum = buildChart(
        "chartHum",
        "Kelembaban (%)",
        CHART_COLORS.hum,
    );
    state.charts.soil = buildChart(
        "chartSoil",
        "Kelembaban Tanah (%)",
        CHART_COLORS.soil,
    );
    state.charts.water = buildChart(
        "chartWater",
        "Level Air (%)",
        CHART_COLORS.water,
    );
}

function updateCharts(rows) {
    if (!rows?.length) return;
    const labels = rows.map((r) => formatTime(r.created_at));
    const push = (chart, key) => {
        if (chart) {
            chart.data.labels = labels;
            chart.data.datasets[0].data = rows.map(
                (r) => parseFloat(r[key]) || 0,
            );
            chart.update("none");
        }
    };
    push(state.charts.temp, "temperature");
    push(state.charts.hum, "humidity");
    push(state.charts.soil, "soil_percent");
    push(state.charts.water, "water_percent");
}

function updateChartTheme() {
    Object.values(state.charts).forEach((c) => {
        if (!c) return;
        const { gridColor, textColor } = chartDefaults();
        c.options.scales.x.grid.color = gridColor;
        c.options.scales.y.grid.color = gridColor;
        c.options.scales.x.ticks.color = textColor;
        c.options.scales.y.ticks.color = textColor;
        c.options.plugins.tooltip.backgroundColor = state.darkMode
            ? "#1e293b"
            : "#fff";
        c.update("none");
    });
}

/* ════════════════════════════════════════════
   STAT CARDS
   ════════════════════════════════════════════ */
const SENSOR_ICONS = { SOIL: "🪴", WATER: "💧", TEMP: "🌡️", HUM: "💨" };
function alertBadgeClass(lvl) {
    return lvl === "CRITICAL"
        ? "badge-critical"
        : lvl === "WARNING"
          ? "badge-warning"
          : "badge-normal";
}
function alertLabel(lvl) {
    return lvl === "CRITICAL"
        ? "🔴 Critical"
        : lvl === "WARNING"
          ? "🟡 Warning"
          : "🟢 Normal";
}

function updateStatCards(d) {
    if (!d) return;
    setText(
        "statTemp",
        d.temperature ? parseFloat(d.temperature).toFixed(1) : "--",
    );
    setHTML(
        "statTempBadge",
        `<span class="stat-status-badge ${alertBadgeClass(d.temp_alert)}">${alertLabel(d.temp_alert)}</span>`,
    );
    setText("statHum", d.humidity ? parseFloat(d.humidity).toFixed(1) : "--");
    setHTML(
        "statHumBadge",
        `<span class="stat-status-badge ${alertBadgeClass(d.hum_alert)}">${alertLabel(d.hum_alert)}</span>`,
    );
    setProgress("progHum", d.humidity);
    setText("statSoil", d.soil_percent ?? "--");
    setHTML(
        "statSoilBadge",
        `<span class="stat-status-badge ${alertBadgeClass(d.soil_alert)}">${alertLabel(d.soil_alert)}</span>`,
    );
    setProgress("progSoil", d.soil_percent);
    setText("statWater", d.water_percent ?? "--");
    setHTML(
        "statWaterBadge",
        `<span class="stat-status-badge ${alertBadgeClass(d.water_alert)}">${alertLabel(d.water_alert)}</span>`,
    );
    setProgress("progWater", d.water_percent);
    const on = d.pump_status == 1;
    setText("statPump", on ? "ON" : "OFF");
    setHTML(
        "statPumpBadge",
        on
            ? `<span class="stat-status-badge badge-active"><span class="pump-spinner"></span> Aktif</span>`
            : `<span class="stat-status-badge badge-inactive">Mati</span>`,
    );
    setText("statPumpLabel", on ? "⚡ Sedang menyiram" : "💤 Pompa standby");
    if (d.created_at)
        setText("lastUpdate", "Update: " + formatDateTime(d.created_at));
}

function setText(id, val) {
    const n = el(id);
    if (n) n.textContent = val;
}
function setHTML(id, html) {
    const n = el(id);
    if (n) n.innerHTML = html;
}
function setProgress(id, val) {
    const bar = el(id);
    if (bar)
        bar.style.width =
            Math.min(100, Math.max(0, parseFloat(val) || 0)) + "%";
}

function updateAlertPanel(alerts) {
    const wrap = el("alertPanel");
    if (!wrap) return;
    if (!alerts?.length) {
        wrap.innerHTML =
            '<div class="alert-empty">✅ Tidak ada alert aktif</div>';
        return;
    }
    wrap.innerHTML = alerts
        .map(
            (a) => `
        <div class="alert-item ${a.alert_level.toLowerCase()}">
            <div class="alert-type-icon">${SENSOR_ICONS[a.alert_type] || "⚠️"}</div>
            <div class="alert-body"><p class="alert-msg">${escHtml(a.message)}</p><span class="alert-time">${formatDateTime(a.created_at)} &bull; ${escHtml(a.alert_type)}</span></div>
            <span class="stat-status-badge ${alertBadgeClass(a.alert_level)}">${a.alert_level}</span>
        </div>`,
        )
        .join("");
}

function updateAlertStats(stats) {
    if (!stats) return;
    setText("statCritical", stats.total_critical ?? 0);
    setText("statWarning", stats.total_warning ?? 0);
    setText("statNormal", stats.total_normal ?? 0);
}

/* ════════════════════════════════════════════
   TABLES & PAGINATION (tidak berubah esensial)
   ════════════════════════════════════════════ */
function fetchMonitoringHistory() {
    const { page, limit, search, sort, dir } = state.monTable;
    const params = new URLSearchParams({
        action: "history",
        page,
        limit,
        search,
        sort,
        dir,
    });
    fetch(`${API}?${params}`)
        .then((r) => r.json())
        .then((data) => {
            if (data.success) renderMonTable(data);
        })
        .catch(console.error);
}

function renderMonTable(data) {
    const tbody = el("monTbody");
    if (!tbody) return;
    if (!data.data.length) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-secondary">Tidak ada data ditemukan</td></tr>`;
    } else {
        tbody.innerHTML = data.data
            .map(
                (r) => `
            <tr>
                <td class="mono text-secondary">${r.id}</td>
                <td class="mono"><strong>${parseFloat(r.temperature).toFixed(1)}</strong> °C</td>
                <td class="mono">${parseFloat(r.humidity).toFixed(1)} %</td>
                <td class="mono">${r.soil_percent} %</td>
                <td class="mono">${r.water_percent} %</td>
                <td><span class="stat-status-badge ${alertBadgeClass(r.soil_alert)}">${r.soil_alert}</span></td>
                <td><span class="stat-status-badge ${alertBadgeClass(r.water_alert)}">${r.water_alert}</span></td>
                <td><span class="stat-status-badge ${alertBadgeClass(r.temp_alert)}">${r.temp_alert}</span></td>
                <td><span class="stat-status-badge ${alertBadgeClass(r.hum_alert)}">${r.hum_alert}</span></td>
                <td><span class="stat-status-badge ${r.pump_status == 1 ? "badge-active" : "badge-inactive"}">${r.pump_status == 1 ? "⚡ ON" : "OFF"}</span></td>
                <td class="mono text-secondary">${formatDateTime(r.created_at)}</td>
            </tr>`,
            )
            .join("");
    }
    renderPagination("monPagination", data, (p) => {
        state.monTable.page = p;
        fetchMonitoringHistory();
    });
    setText("monTotal", `${data.total} data`);
}

function fetchAlertHistory() {
    const { page, limit, level } = state.alertTable;
    const params = new URLSearchParams({
        action: "alert_history",
        page,
        limit,
        level,
    });
    fetch(`${API}?${params}`)
        .then((r) => r.json())
        .then((data) => {
            if (data.success) renderAlertTable(data);
        })
        .catch(console.error);
}

function renderAlertTable(data) {
    const tbody = el("alertTbody");
    if (!tbody) return;
    if (!data.data.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-secondary">Tidak ada data alert</td></tr>`;
    } else {
        tbody.innerHTML = data.data
            .map(
                (r) => `
            <tr>
                <td class="mono text-secondary">${r.id}</td>
                <td><span style="display:flex;align-items:center;gap:6px;"><span>${SENSOR_ICONS[r.alert_type] || "⚠️"}</span><strong>${escHtml(r.alert_type)}</strong></span></td>
                <td><span class="stat-status-badge ${alertBadgeClass(r.alert_level)}">${r.alert_level}</span></td>
                <td>${escHtml(r.message)}</td>
                <td class="mono text-secondary">${formatDateTime(r.created_at)}</td>
            </tr>`,
            )
            .join("");
    }
    renderPagination("alertPagination", data, (p) => {
        state.alertTable.page = p;
        fetchAlertHistory();
    });
    setText("alertTotal", `${data.total} alert`);
}

function renderPagination(containerId, data, onPage) {
    const wrap = el(containerId);
    if (!wrap) return;
    const { page, total_pages, total, limit } = data;
    const from = total === 0 ? 0 : (page - 1) * limit + 1;
    const to = Math.min(page * limit, total);
    const info = `<span class="pagination-info">Menampilkan ${from}–${to} dari ${total}</span>`;
    let btns = `<div class="pagination-btns"><button class="pg-btn" onclick="${onPage.name}(1)" ${page === 1 ? "disabled" : ""}>«</button><button class="pg-btn" onclick="${onPage.name}(${page - 1})" ${page === 1 ? "disabled" : ""}>‹</button>`;
    const range = pageRange(page, total_pages);
    range.forEach((p) => {
        if (p === "...") btns += `<button class="pg-btn" disabled>…</button>`;
        else
            btns += `<button class="pg-btn ${p === page ? "active" : ""}" onclick="goPage('${containerId}',${p})">${p}</button>`;
    });
    btns += `<button class="pg-btn" onclick="${onPage.name}(${page + 1})" ${page === total_pages ? "disabled" : ""}>›</button><button class="pg-btn" onclick="${onPage.name}(${total_pages})" ${page === total_pages ? "disabled" : ""}>»</button></div>`;
    wrap.innerHTML = `<div class="pagination-wrap">${info}${btns}</div>`;
}

window.goPage = function (containerId, p) {
    if (containerId === "monPagination") {
        state.monTable.page = p;
        fetchMonitoringHistory();
    } else {
        state.alertTable.page = p;
        fetchAlertHistory();
    }
};

function pageRange(current, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    if (current <= 4) return [1, 2, 3, 4, 5, "...", total];
    if (current >= total - 3)
        return [1, "...", total - 4, total - 3, total - 2, total - 1, total];
    return [1, "...", current - 1, current, current + 1, "...", total];
}

function sortMon(col) {
    if (state.monTable.sort === col)
        state.monTable.dir = state.monTable.dir === "ASC" ? "DESC" : "ASC";
    else {
        state.monTable.sort = col;
        state.monTable.dir = "DESC";
    }
    state.monTable.page = 1;
    updateSortIcons();
    fetchMonitoringHistory();
}
window.sortMon = sortMon;

function updateSortIcons() {
    $$("[data-sortcol]").forEach((th) => {
        th.classList.remove("sorted");
        const ic = th.querySelector(".sort-icon");
        if (ic) ic.textContent = "⇅";
    });
    const active = $(`[data-sortcol="${state.monTable.sort}"]`);
    if (active) {
        active.classList.add("sorted");
        const ic = active.querySelector(".sort-icon");
        if (ic) ic.textContent = state.monTable.dir === "ASC" ? "↑" : "↓";
    }
}

/* ════════════════════════════════════════════
   REALTIME FETCH
   ════════════════════════════════════════════ */
let refreshCount = 0;
function fetchRealtime() {
    fetch(`${API}?action=realtime&_=${Date.now()}`)
        .then((r) => {
            if (!r.ok) throw new Error();
            return r.json();
        })
        .then((data) => {
            if (!data.success) return;
            // setOnline(true);
            if (data.latest && data.latest.created_at) {
                const now = new Date();
                const lastUpdate = new Date(
                    data.latest.created_at.replace(" ", "T"),
                );
                const diffSeconds = (now - lastUpdate) / 1000;
                setOnline(diffSeconds < 360); // 6 menit
            } else {
                setOnline(false);
            }
            updateStatCards(data.latest);
            updateCharts(data.chart);
            updateAlertPanel(data.alerts);
            updateAlertStats(data.alert_stats);
            refreshCount++;
            setText("refreshCount", refreshCount);
            const dot = el("refreshDot");
            if (dot) {
                dot.style.background = "#4ade80";
                setTimeout(() => {
                    dot.style.background = "";
                }, 400);
            }
        })
        .catch((err) => {
            console.error(err);
            setOnline(false);
        });
}

function setOnline(online) {
    state.isOnline = online;
    const dot = el("connDot"),
        txt = el("connText");
    if (dot) dot.className = "status-dot" + (online ? "" : " offline");
    if (txt) txt.textContent = online ? "Terhubung • Live" : "Koneksi terputus";
}

function startAutoRefresh() {
    fetchRealtime();
    state.refreshTimer = setInterval(fetchRealtime, TICK);
}

/* ════════════════════════════════════════════
   SEARCH DEBOUNCE
   ════════════════════════════════════════════ */
function debounce(fn, ms) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
}
const debouncedSearch = debounce((val) => {
    state.monTable.search = val;
    state.monTable.page = 1;
    fetchMonitoringHistory();
}, 400);

/* ════════════════════════════════════════════
   DATE UTILS
   ════════════════════════════════════════════ */
function formatTime(ts) {
    if (!ts) return "--";
    const d = new Date(ts);
    const pad = (n) => String(n).padStart(2, "0");
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}
function formatDateTime(ts) {
    if (!ts) return "--";
    const d = new Date(ts);
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}
function escHtml(s) {
    if (!s) return "";
    return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

/* ════════════════════════════════════════════
   EVENT BINDINGS (FIXED)
   ════════════════════════════════════════════ */
function bindEvents() {
    const sbBtn = el("sidebarToggle");
    if (sbBtn) sbBtn.addEventListener("click", toggleSidebar);

    const ov = el("sidebarOverlay");
    if (ov) ov.addEventListener("click", closeSidebar);

    _initSwipeGesture();

    const dt = el("darkToggle");
    if (dt) dt.addEventListener("click", toggleDark);

    $$(".nav-link-custom[data-section]").forEach((link) => {
        link.addEventListener("click", () => {
            const section = link.dataset.section;
            setActiveNav(section);
            showSection(section);
            closeSidebar();
        });
    });

    const searchInp = el("monSearch");
    if (searchInp)
        searchInp.addEventListener("input", (e) =>
            debouncedSearch(e.target.value.trim()),
        );

    const monLimitSel = el("monLimit");
    if (monLimitSel)
        monLimitSel.addEventListener("change", (e) => {
            state.monTable.limit = +e.target.value;
            state.monTable.page = 1;
            fetchMonitoringHistory();
        });

    const alertLimitSel = el("alertLimit");
    if (alertLimitSel)
        alertLimitSel.addEventListener("change", (e) => {
            state.alertTable.limit = +e.target.value;
            state.alertTable.page = 1;
            fetchAlertHistory();
        });

    $$(".filter-tab").forEach((tab) => {
        tab.addEventListener("click", () => {
            $$(".filter-tab").forEach((t) => t.classList.remove("active"));
            tab.classList.add("active");
            state.alertTable.level = tab.dataset.level || "";
            state.alertTable.page = 1;
            fetchAlertHistory();
        });
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeSidebar();
    });

    // RESIZE HANDLER: bersihkan overlay & sinkronkan state
    window.addEventListener("resize", () => {
        const isDesktop = window.innerWidth >= 992;
        const sb = el("sidebar");
        const ov = el("sidebarOverlay");
        const btn = el("sidebarToggle");
        if (isDesktop) {
            state.sidebarOpen = true;
            if (sb) sb.classList.add("open");
            if (ov) ov.classList.remove("show");
            document.body.style.overflow = "";
            if (btn) btn.classList.remove("active");
        } else {
            // mobile: jika sidebar terbuka, overlay harus sesuai
            if (state.sidebarOpen) {
                if (sb) sb.classList.add("open");
                if (ov) ov.classList.add("show");
                document.body.style.overflow = "hidden";
                if (btn) btn.classList.add("active");
            } else {
                if (sb) sb.classList.remove("open");
                if (ov) ov.classList.remove("show");
                document.body.style.overflow = "";
                if (btn) btn.classList.remove("active");
            }
        }
    });
}

function showSection(section) {
    $$("[data-section-content]").forEach((s) => {
        s.style.display = s.dataset.sectionContent === section ? "" : "none";
    });
}

/* ════════════════════════════════════════════
   INIT
   ════════════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", () => {
    initDarkMode();
    startClock();
    initCharts();
    bindEvents();

    // Set sidebar awal berdasarkan ukuran layar
    const isDesktop = window.innerWidth >= 992;
    if (isDesktop) {
        state.sidebarOpen = true;
        el("sidebar")?.classList.add("open");
        el("sidebarToggle")?.classList.remove("active");
        el("sidebarOverlay")?.classList.remove("show");
        document.body.style.overflow = "";
    } else {
        state.sidebarOpen = false;
        el("sidebar")?.classList.remove("open");
        el("sidebarOverlay")?.classList.remove("show");
        document.body.style.overflow = "";
    }

    setActiveNav("realtime");
    showSection("realtime");

    fetchMonitoringHistory();
    fetchAlertHistory();
    startAutoRefresh();
});
