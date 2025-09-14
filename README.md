# YieldQuest (PHP + Bootstrap + Chart.js + DataTables)

A lightweight, farmer‑friendly dashboard to explore crop yield vs rainfall, sunshine and temperature — directly from CSV files (no database).

## Features
- Three vertically stacked charts: **Yield vs Rainfall**, **Yield vs Sunshine**, **Yield vs Temperature**
  - Yield (kg/ha) plotted as separate lines per **treatment/fertilizer_code**.
  - Weather on the **secondary Y‑axis** (mm / hours / °C).
  - When the selected year range spans **< 5 years**, weather shows **monthly** values; yields appear as **scatter/line points positioned at harvest month**. For longer ranges (≥ 5 years), weather is aggregated **annually**.
- Top filters: **Year range**, **Fertilizer/Treatment**, **Plot**, **Cultivar**.
- Yield records table with **DataTables** (search/sort/pagination).
- Rothamsted Research brand colours for the navigation bar.
- Reads from CSV files: `data/rainfall.csv`, `data/sunshine.csv`, `data/temperature.csv`, `data/yield.csv`.

## File structure
```
YieldQuest/
  index.php
  config.php
  api/
    helpers.php
    data.php
  assets/
    styles.css
    rothamsted_logo_1.png (if provided)
    rothamsted_logo_2.png (if provided)
  data/
    rainfall.csv
    sunshine.csv
    temperature.csv
    yield.csv
```

## Install on IIS (PHP 8.2+)
1. Copy the `YieldQuest` folder to your IIS web root, e.g. `C:\inetpub\wwwroot\YieldQuest`.
2. Ensure PHP 8.2+ is installed and mapped in IIS (FastCGI).
3. Give read permission on the `data` folder to the IIS App Pool identity.
4. Browse to `http://localhost/YieldQuest/`.

## Update CSVs
Replace the CSV files in `data/` with your own. The app tries to auto‑detect headers using common names:
- **Rainfall**: detects `year`, optional `month`, and value columns such as `rain`, `rainfall`, `mm`, `total`, etc.
- **Sunshine**: similar, looking for `hours`, `sunshine`, `total`.
- **Temperature**: looks for `temperature`, `temp`, `c`, etc.
- **Yield**: expects per‑plot/per‑treatment rows. It detects:
  - treatment: `fertilizer_code`/`treatment` (case‑insensitive)
  - plot: `plot`
  - cultivar: `cultivar`/`variety`
  - sowing date: `sowing_date`/`date_sown`
  - harvest date: `harvest_date`/`harvest`
  - yield value (kg/ha): any header containing `yield` & `kg` or `grain` & `kg`, or `kg_ha`

> If your header names differ, you can adjust the mapping in `api/data.php`.

## How the charts work
- **Primary Y‑axis**: Yield (kg/ha) — a line for each treatment (`fertilizer_code`).
- **Secondary Y‑axis**: Weather metric for that chart.
- **Short ranges (<5 years)**: weather monthly bars; yield points at harvest month (scatter/line).
- **Long ranges (≥5 years)**: weather is aggregated annually, plotted as a line.

## Notes
- Everything is client‑side except the PHP endpoint `api/data.php`, which reads CSVs and returns JSON based on the current filters.
- No database required.
- If your data uses different date formats, ensure they are parseable by PHP `strtotime`.

Enjoy exploring! 🌱
