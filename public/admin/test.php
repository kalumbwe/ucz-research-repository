<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: PHP works<br>";

require_once __DIR__ . '/../../app/includes/auth.php';
echo "Step 2: auth.php loaded<br>";

require_once __DIR__ . '/../../app/includes/settings.php';
echo "Step 3: settings.php loaded<br>";

echo "Step 4: All good!";