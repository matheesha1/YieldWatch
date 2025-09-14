<?php
// Basic configuration for YieldQuest
define('YQ_APP_NAME', 'YieldWatch®');
define('YQ_DATA_DIR', __DIR__ . '/data'); // path to CSV data directory

// Rothamsted Research brand colours (approx.)
define('YQ_GREEN', '#005f30');
define('YQ_YELLOW', '#ffcc00');

// CSV filenames (you can change these if needed)
define('YQ_FILE_RAINFALL', YQ_DATA_DIR . '/rainfall.csv');
define('YQ_FILE_SUNSHINE', YQ_DATA_DIR . '/sunshine.csv');
define('YQ_FILE_TEMPERATURE', YQ_DATA_DIR . '/temperature.csv');
define('YQ_FILE_YIELD', YQ_DATA_DIR . '/yield.csv');

// Locale/timezone
date_default_timezone_set('UTC');
?>
