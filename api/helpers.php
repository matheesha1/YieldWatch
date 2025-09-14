<?php
require_once __DIR__ . '/../config.php';

/**
 * Read a CSV file into an array of associative rows with header mapping.
 * Returns [] if file missing.
 */
function yq_read_csv($filepath) {
    if (!file_exists($filepath)) return [];
    $rows = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        $header = fgetcsv($handle);
        if (!$header) return [];
        // Normalize headers to lowercase simple keys
        $keys = array_map(function($h){
            $h = trim($h);
            $h = preg_replace('/[^a-zA-Z0-9_]+/', '_', strtolower($h));
            return $h;
        }, $header);
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($keys as $i => $k) {
                $row[$k] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);
    }
    return $rows;
}

function yq_guess_number($val) {
    if (is_null($val)) return null;
    // Remove commas and units if present
    $v = trim($val);
    $v = preg_replace('/[^0-9\.\-\+eE]/', '', $v);
    if ($v === '' || $v === '-' ) return null;
    return is_numeric($v) ? floatval($v) : null;
}

function yq_parse_date($val) {
    if (!$val) return null;
    $ts = strtotime($val);
    if ($ts !== false) return $ts;
    return null;
}

function yq_unique_sorted($arr) {
    $arr = array_values(array_unique(array_filter($arr, function($x){ return $x !== '' && $x !== null; })));
    sort($arr);
    return $arr;
}

function yq_http_json($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
