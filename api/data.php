<?php
require_once __DIR__ . '/helpers.php';

/**
 * Query params (all optional):
 *  - start_year, end_year (ints)
 *  - fertilizer_code, plot, cultivar (strings, '*' or empty for all)
 */
$start_year = isset($_GET['start_year']) ? intval($_GET['start_year']) : null;
$end_year   = isset($_GET['end_year']) ? intval($_GET['end_year']) : null;

$fert_filter = isset($_GET['fertilizer_code']) ? strtolower(trim($_GET['fertilizer_code'])) : '';
$plot_filter = isset($_GET['plot']) ? strtolower(trim($_GET['plot'])) : '';
$cult_filter = isset($_GET['cultivar']) ? strtolower(trim($_GET['cultivar'])) : '';

$rain = yq_read_csv(YQ_FILE_RAINFALL);
$sun  = yq_read_csv(YQ_FILE_SUNSHINE);
$temp = yq_read_csv(YQ_FILE_TEMPERATURE);
$ylds = yq_read_csv(YQ_FILE_YIELD);

// Normalise weather rows to [year, month(1-12 optional), value]
function map_weather($rows, $value_keys = ['rain','rain_mm','precipitation','total','value','rainfall','mm','hours','sunshine','temperature','temp','c']) {
    $out = [];
    $month_names = ['jan','january','feb','february','mar','march','apr','april','may','jun','june','jul','july','aug','august','sep','sept','september','oct','october','nov','november','dec','december'];
    foreach ($rows as $r) {
        // year
        $year = null;
        foreach (['year','yyyy','harvest_year'] as $yk) if (isset($r[$yk]) && is_numeric($r[$yk])) { $year = intval($r[$yk]); break; }
        if (!$year && isset($r['date'])) { $ts = yq_parse_date($r['date']); if ($ts) $year = intval(gmdate('Y', $ts)); }
        if (!$year) continue;

        // Detect wide monthly columns (e.g., Jan, Feb, ...)
        $has_month_cols = false;
        foreach ($r as $k=>$v) {
            $kk = strtolower(preg_replace('/[^a-z]/', '', $k));
            if (in_array($kk, $month_names, true)) { $has_month_cols = true; break; }
        }
        if ($has_month_cols) {
            $month_index = ['jan'=>1,'january'=>1,'feb'=>2,'february'=>2,'mar'=>3,'march'=>3,'apr'=>4,'april'=>4,'may'=>5,'jun'=>6,'june'=>6,'jul'=>7,'july'=>7,'aug'=>8,'august'=>8,'sep'=>9,'sept'=>9,'september'=>9,'oct'=>10,'october'=>10,'nov'=>11,'november'=>11,'dec'=>12,'december'=>12];
            foreach ($r as $k=>$v) {
                $kk = strtolower(preg_replace('/[^a-z]/', '', $k));
                if (isset($month_index[$kk])) {
                    $m = $month_index[$kk];
                    $val = yq_guess_number($v);
                    if ($val !== null) $out[] = ['year'=>$year, 'month'=>$m, 'value'=>$val];
                } elseif (in_array($kk, ['annual','total','sum'])) {
                    $val = yq_guess_number($v);
                    if ($val !== null) $out[] = ['year'=>$year, 'month'=>null, 'value'=>$val];
                }
            }
            continue;
        }

        // long/tidy form
        // month (optional)
        $month = null;
        foreach (['month','mm','mon'] as $mk) if (isset($r[$mk]) && $r[$mk] !== '') {
            $m = $r[$mk];
            if (is_numeric($m)) $month = intval($m);
            else {
                $mnum = date('n', strtotime("1 $m 2000"));
                if ($mnum) $month = intval($mnum);
            }
            break;
        }
        // value
        $val = null;
        foreach ($value_keys as $vk) {
            if (isset($r[$vk])) { $val = yq_guess_number($r[$vk]); if ($val !== null) break; }
        }
        if ($val === null) {
            // Try any numeric column if value not found
            foreach ($r as $k=>$v) {
                if (is_numeric($v)) { $val = floatval($v); break; }
                $vv = yq_guess_number($v);
                if ($vv !== null) { $val = $vv; break; }
            }
        }
        if ($val !== null) {
            $out[] = ['year'=>$year, 'month'=>$month, 'value'=>$val];
        }
    }
    return $out;
}
$rain_n = map_weather($rain);
$sun_n  = map_weather($sun);
$temp_n = map_weather($temp);

// Yields: normalise
$yield_out = [];
$fert_values = [];
$plot_values = [];
$cult_values = [];
$year_values = [];

foreach ($ylds as $r) {
    // treatment / fertilizer code
    $fert = '';
    foreach (['fertilizer_code','fertiliser_code','treatment','treatment_code','n_treatment'] as $fk) { if (isset($r[$fk])) { $fert = trim($r[$fk]); break; } }
    $plot = '';
    foreach (['plot','plot_id'] as $pk) { if (isset($r[$pk])) { $plot = trim($r[$pk]); break; } }
    $cult = '';
    foreach (['cultivar','variety','cv'] as $ck) { if (isset($r[$ck])) { $cult = trim($r[$ck]); break; } }
    // yield
    $yield_val = null;
    $unit_multiplier = 1.0; // default kg/ha
    foreach ($r as $k=>$v) {
        $k_l = strtolower($k);
        if (preg_match('/(yield|grain).*?(kg\/?ha|kg_ha|kgperha)/i', $k)) { $yield_val = yq_guess_number($v); $unit_multiplier = 1.0; break; }
        if (preg_match('/(yield|grain).*?(t\/?ha|tonnes?\/?ha|t_ha)/i', $k)) { $yield_val = yq_guess_number($v); $unit_multiplier = 1000.0; break; }
        if (preg_match('/kg_ha|kgperha/i', $k)) { $yield_val = yq_guess_number($v); $unit_multiplier = 1.0; break; }
        if (preg_match('/t_ha|tperha/i', $k)) { $yield_val = yq_guess_number($v); $unit_multiplier = 1000.0; break; }
    }
    if ($yield_val === null && isset($r['yield'])) $yield_val = yq_guess_number($r['yield']);
    if ($yield_val !== null) $yield_val = $yield_val * $unit_multiplier;
    // direct 'grain' column without units -> assume t/ha and convert
    if ($yield_val === null) {
        if (isset($r['grain'])) { $yield_val = yq_guess_number($r['grain']); if ($yield_val !== null) $yield_val *= 1000.0; }
        elseif (isset($r['grain_t_ha'])) { $yield_val = yq_guess_number($r['grain_t_ha']); if ($yield_val !== null) $yield_val *= 1000.0; }
        elseif (isset($r['grain_kg_ha'])) { $yield_val = yq_guess_number($r['grain_kg_ha']); }
    }

    // dates
    $h_ts = null; foreach (['harvest_date','harvest','date_harvest'] as $hk) if (isset($r[$hk])) { $h_ts = yq_parse_date($r[$hk]); if ($h_ts) break; }
    $s_ts = null; foreach (['sowing_date','sown','date_sown','planting_date'] as $sk) if (isset($r[$sk])) { $s_ts = yq_parse_date($r[$sk]); if ($s_ts) break; }
    // year
    $year = null;
    foreach (['year','yyyy','harvest_year'] as $yk) if (isset($r[$yk]) && is_numeric($r[$yk])) { $year = intval($r[$yk]); break; }
    if (!$year && $h_ts) $year = intval(gmdate('Y',$h_ts));
    if (!$year && $s_ts) $year = intval(gmdate('Y',$s_ts));

    // Apply filters if present
    if ($fert_filter && $fert_filter !== '*' && strtolower($fert) !== $fert_filter) continue;
    if ($plot_filter && $plot_filter !== '*' && strtolower($plot) !== $plot_filter) continue;
    if ($cult_filter && $cult_filter !== '*' && strtolower($cult) !== $cult_filter) continue;
    if ($start_year && $year && $year < $start_year) continue;
    if ($end_year && $year && $year > $end_year) continue;

    $yield_out[] = [
        'fertilizer_code' => $fert,
        'plot' => $plot,
        'cultivar' => $cult,
        'yield_kg_ha' => $yield_val,
        'year' => $year,
        'harvest_date' => $h_ts ? gmdate('Y-m-d',$h_ts) : null,
        'sowing_date' => $s_ts ? gmdate('Y-m-d',$s_ts) : null,
        'harvest_month' => $h_ts ? intval(gmdate('n',$h_ts)) : null
    ];

    $fert_values[] = $fert;
    $plot_values[] = $plot;
    $cult_values[] = $cult;
    if ($year) $year_values[] = $year;
}

$distinct = [
    'fertilizer_codes' => yq_unique_sorted($fert_values),
    'plots' => yq_unique_sorted($plot_values),
    'cultivars' => yq_unique_sorted($cult_values),
    'years' => yq_unique_sorted($year_values)
];

$data = [
    'weather' => [
        'rainfall' => $rain_n,
        'sunshine' => $sun_n,
        'temperature' => $temp_n
    ],
    'yields' => $yield_out,
    'distinct' => $distinct
];

yq_http_json($data);
?>
