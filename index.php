<?php
require_once __DIR__ . '/config.php';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo YQ_APP_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/2.0.5/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="assets/styles.css">
  
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/luxon@3/build/global/luxon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="#"><img src="img/source.gif" width="60px">
      <?php echo YQ_APP_NAME; ?>
    </a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">About Us</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Contact</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
	<center><div class="mx-4"> </div>
<h1 style="color: brown">Crop Yield vs Weather Explorer</h1></center>
	<div class="mx-4"> </div>
<div class="container my-4">
  <div class="row g-3 align-items-end">
    <div class="col-md-2">
      <label class="form-label">Start year</label>
      <select id="startYear" class="form-select"></select>
    </div>
    <div class="col-md-2">
      <label class="form-label">End year</label>
      <select id="endYear" class="form-select"></select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Fertilizer / Treatment</label>
      <select id="fertFilter" class="form-select">
        <option value="">All</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Plot</label>
      <select id="plotFilter" class="form-select">
        <option value="">All</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Cultivar</label>
      <select id="cultFilter" class="form-select">
        <option value="">All</option>
      </select>
    </div>
    <div class="col-md-2">
      <button id="applyBtn" class="btn btn-success w-100" style="background:#005f30;border-color:#005f30;">Apply</button>
    </div>
  </div>

  <hr class="my-4">

  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="card-title m-0">Yield vs Rainfall <small class="text-muted">(yield: kg/ha, rainfall: mm)</small></h5>
  <button id="dlRain" class="btn btn-sm btn-outline-secondary">Download JPG</button>
</div>
          <div class="chart-container"><canvas id="chartRain"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="card-title m-0">Yield vs Sunshine <small class="text-muted">(yield: kg/ha, sunshine: hours)</small></h5>
  <button id="dlSun" class="btn btn-sm btn-outline-secondary">Download JPG</button>
</div>
          <div class="chart-container"><canvas id="chartSun"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="card-title m-0">Yield vs Temperature <small class="text-muted">(yield: kg/ha, temperature: °C)</small></h5>
  <button id="dlTemp" class="btn btn-sm btn-outline-secondary">Download JPG</button>
</div>
          <div class="chart-container"><canvas id="chartTemp"></canvas></div>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="card-title m-0">Yield records</h5>
  <button id="downloadCsvBtn" class="btn btn-sm btn-success">Download CSV</button>
</div>
      <div class="table-responsive">
        <table id="yieldTable" class="table table-striped table-bordered w-100"></table>
      </div>
    </div>
  </div>

  <div class="footer my-4 text-center">
    Built with PHP 8 + Bootstrap 5 + Chart.js + DataTables. Colours inspired by Rothamsted Research.
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.bootstrap5.min.js"></script>
<script>
const YQ_GREEN = getComputedStyle(document.documentElement).getPropertyValue('--yq-green').trim();
const YQ_YELLOW = getComputedStyle(document.documentElement).getPropertyValue('--yq-yellow').trim();

const YQ_BLUE = '#0d6efd';   // rainfall
const YQ_RED  = '#dc3545';   // temperature
let charts = {rain:null, sun:null, temp:null};
let cacheData = null;

function fetchData(params={}){
  const q = new URLSearchParams(params).toString();
  return fetch('api/data.php' + (q ? ('?'+q) : '')).then(r=>r.json());
}

function groupByYear(data){
  const m = {};
  data.forEach(d=>{
    const y = d.year;
    if(!m[y]) m[y] = 0;
    m[y] += Number(d.value) || 0;
  });
  return Object.entries(m).sort((a,b)=>a[0]-b[0]).map(([y,v])=>({year:+y, value:v}));
}

function groupWeatherMonthlyInRange(data, startYear, endYear){
  // returns array of points with {t: Date, value}
  const pts = [];
  data.forEach(d=>{
    const y = d.year, m = d.month || 1;
    if(y>=startYear && y<=endYear){
      const dt = new Date(Date.UTC(y, (m-1), 1));
      pts.push({x: dt, y: d.value});
    }
  });
  pts.sort((a,b)=>a.t - b.t);
  return pts;
}

function groupWeatherAnnuallyInRange(data, startYear, endYear){
  const yearly = {};
  data.forEach(d=>{
    const y = d.year;
    if(y>=startYear && y<=endYear){
      yearly[y] = (yearly[y]||0) + Number(d.value||0);
    }
  });
  return Object.keys(yearly).sort().map(y=>({x: new Date(Date.UTC(+y,0,1)), y: yearly[y]}));
}

function seriesPerTreatment(yields, startYear, endYear){
  // dataset per fertilizer_code (treatment)
  const byT = {};
  yields.forEach(r=>{
    const y = +r.year;
    if(!y || y<startYear || y>endYear) return;
    const t = r.fertilizer_code || 'Unknown';
    if(!byT[t]) byT[t] = [];
    // Prefer harvest date month for the x-position; fall back to mid-year
    let dt = r.harvest_date ? new Date(r.harvest_date+'T00:00:00Z') : new Date(Date.UTC(y, 6, 1));
    byT[t].push({x: dt, y: Number(r.yield_kg_ha)||null});
  });
  // sort each series by time
  Object.values(byT).forEach(arr=>arr.sort((a,b)=>a.x-b.x));
  return byT;
}

function distinctYears(allYears){
  return Array.from(new Set(allYears)).sort((a,b)=>a-b);
}

function initFilters(distinct){
  const years = distinctYears(distinct.years);
  const sY = document.getElementById('startYear');
  const eY = document.getElementById('endYear');
  [sY,eY].forEach(sel=>{ sel.innerHTML=''; years.forEach(y=>{
    const opt = document.createElement('option'); opt.value=y; opt.textContent=y; sel.appendChild(opt);
  }); });
  if(years.length){
    sY.value = years[0];
    eY.value = years[years.length-1];
  }
  const fillSel = (id, arr)=>{
    const sel = document.getElementById(id);
    arr.forEach(v=>{ const opt=document.createElement('option'); opt.value=v; opt.textContent=v; sel.appendChild(opt); });
  };
  fillSel('fertFilter', distinct.fertilizer_codes);
  fillSel('plotFilter', distinct.plots);
  fillSel('cultFilter', distinct.cultivars);
}

function mkLineScatterChart(ctx, yieldSeries, weatherSeries, labelWeather, weatherUnit, startYear, endYear, isMonthly, timeUnit, weatherColor){
  const ds = [];
  const palette = [
    YQ_GREEN, '#0d6efd', '#6f42c1', '#d63384', '#fd7e14',
    '#20c997', '#198754', '#dc3545', '#6610f2', '#6c757d'
  ];
  let i=0;

  // Yield per treatment: scatter (no line) for monthly view; line otherwise
  Object.keys(yieldSeries).forEach((t)=>{
    const color = palette[i++ % palette.length];
ds.push({
  label: `Yield (${t})`,
  data: yieldSeries[t],           // [{x: Date, y: kg/ha}]
  parsing: false,
  type: isMonthly ? 'scatter' : 'line',
  showLine: isMonthly ? false : true,
  pointRadius: isMonthly ? 5 : 3,
  pointStyle: isMonthly ? 'cross' : 'circle',
  borderWidth: 2,
  borderColor: color,
  backgroundColor: color,
  yAxisID: 'y'
});
  });

  // Weather on secondary axis: bar for monthly; line for annual
ds.push({
  label: labelWeather,
  data: weatherSeries,            // [{x: Date, y: value}]
  parsing: false,
  type: isMonthly ? 'bar' : 'line',
  borderWidth: 2,
  borderColor: weatherColor,
  backgroundColor: weatherColor,
  yAxisID: 'y1'
});

  return new Chart(ctx, {
    type: 'line', // base (datasets can override)
    data: { datasets: ds },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: {
          type: 'time',
          time: { unit: timeUnit }, // 'month' or 'year'
          // keep the axis locked to the selected year window
          suggestedMin: new Date(Date.UTC(startYear, 0, 1)),
          suggestedMax: new Date(Date.UTC(endYear, 11, 31)),
          title: { display: true, text: 'Time' }
        },
        y:  { position: 'left',  title: { display: true, text: 'Yield (kg/ha)' } },
        y1: { position: 'right', title: { display: true, text: `${labelWeather} (${weatherUnit})` }, grid: { drawOnChartArea: false } }
      },
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: (ctx) => ctx.dataset.yAxisID === 'y'
              ? `${ctx.dataset.label}: ${ctx.parsed.y?.toLocaleString()} kg/ha`
              : `${ctx.dataset.label}: ${ctx.parsed.y?.toLocaleString()} ${weatherUnit}`
          }
        }
      }
    }
  });
}


function renderCharts(allData){
  const sY = +document.getElementById('startYear').value;
  const eY = +document.getElementById('endYear').value;

const spanYears  = eY - sY + 1;
const smallRange = spanYears <= 5;            // <= 5 years → monthly + scatter
const timeUnit   = smallRange ? 'month' : 'year';
  const weatherAgg = (rows, label) => smallRange ? groupWeatherMonthlyInRange(rows, sY, eY) : groupWeatherAnnuallyInRange(rows, sY, eY);
  const ySeries = seriesPerTreatment(allData.yields, sY, eY);

  // dispose old charts
  ['rain','sun','temp'].forEach(k=>{ if(charts[k]) { charts[k].destroy(); charts[k]=null; } });

charts.rain = mkLineScatterChart(
  document.getElementById('chartRain'),
  ySeries,
  weatherAgg(allData.weather.rainfall),
  'Rainfall', 'mm', sY, eY, smallRange, timeUnit, YQ_BLUE
);

charts.sun = mkLineScatterChart(
  document.getElementById('chartSun'),
  ySeries,
  weatherAgg(allData.weather.sunshine),
  'Sunshine', 'hours', sY, eY, smallRange, timeUnit, YQ_YELLOW
);

charts.temp = mkLineScatterChart(
  document.getElementById('chartTemp'),
  ySeries,
  weatherAgg(allData.weather.temperature),
  'Temperature', '°C', sY, eY, smallRange, timeUnit, YQ_RED
);
}

function renderTable(rows){
  // flatten columns and prepare table
  const columns = [
    {title:'Year', data:'year'},
    {title:'Fertilizer/Treatment', data:'fertilizer_code'},
    {title:'Plot', data:'plot'},
    {title:'Cultivar', data:'cultivar'},
    {title:'Sowing date', data:'sowing_date'},
    {title:'Harvest date', data:'harvest_date'},
    {title:'Yield (kg/ha)', data:'yield_kg_ha', render:(d)=> d? Number(d).toLocaleString():'' }
  ];
  const tableEl = document.getElementById('yieldTable');
  if($.fn.dataTable.isDataTable(tableEl)){
    $(tableEl).DataTable().clear().rows.add(rows).draw();
  }else{
    $(tableEl).DataTable({
      data: rows,
      columns: columns,
      order:[[0,'asc']],
      pageLength: 10,
      responsive: true
    });
  }
}

function applyFilters(){
  const params = {
    start_year: document.getElementById('startYear').value,
    end_year: document.getElementById('endYear').value,
    fertilizer_code: document.getElementById('fertFilter').value,
    plot: document.getElementById('plotFilter').value,
    cultivar: document.getElementById('cultFilter').value
  };
  fetchData(params).then(data => {
    cacheData = data;
    renderCharts(data);
    renderTable(data.yields);
document.getElementById('dlRain').onclick = () => saveChartAsJPG(charts.rain, 'Yield_vs_Rainfall.jpg');
document.getElementById('dlSun').onclick  = () => saveChartAsJPG(charts.sun,  'Yield_vs_Sunshine.jpg');
document.getElementById('dlTemp').onclick = () => saveChartAsJPG(charts.temp, 'Yield_vs_Temperature.jpg');
document.getElementById('downloadCsvBtn').onclick = () => downloadYieldsCSV(data.yields);
  });
}
function saveChartAsJPG(chart, filename){
  if(!chart) return;
  const link = document.createElement('a');
  link.href = chart.toBase64Image('image/jpeg', 1.0);
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
}

function downloadYieldsCSV(rows){
  if(!rows || !rows.length) return;
  const headers = ['year','fertilizer_code','plot','cultivar','sowing_date','harvest_date','yield_kg_ha'];
  const title = ['Year','Fertilizer/Treatment','Plot','Cultivar','Sowing date','Harvest date','Yield (kg/ha)'];
  const csv = [title.join(',')]
    .concat(rows.map(r => headers.map(h => `"${(r[h]??'').toString().replace(/"/g,'""')}"`).join(',')))
    .join('\r\n');
  const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = 'yield_records.csv';
  document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(url);
}

document.getElementById('applyBtn').addEventListener('click', applyFilters);

fetchData().then(data=>{
  cacheData = data;
  initFilters(data.distinct);
  applyFilters();
});
</script>
	<div class="mt-5 p-4 bg-dark text-white text-center">
  <p>Copyright © 2025 - Rothamsted Data Hackathon</p>
</div>
</body>
</html>
