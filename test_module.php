<?php
/**
 * Basic test file for AerPoints module
 * Run this from the module directory to test basic functionality
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

echo "=== AerPoints Module Test ===\n\n";

// Test 1: Check if module file loads without errors
echo "Test 1: Loading module file...\n";
try {
    require_once(dirname(__FILE__).'/aerpoints.php');
    echo "✓ Module file loaded successfully\n\n";
} catch (Exception $e) {
    echo "✗ Error loading module: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Check if classes load without errors
echo "Test 2: Loading module classes...\n";
$classes = array(
    'AerpointsCustomer',
    'AerpointsProduct', 
    'AerpointsPending',
    'AerpointsHistory'
);

foreach ($classes as $class) {
    try {
        require_once(dirname(__FILE__).'/classes/'.$class.'.php');
        echo "✓ {$class} loaded successfully\n";
    } catch (Exception $e) {
        echo "✗ Error loading {$class}: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 3: Check if module is instantiable
echo "Test 3: Creating module instance...\n";
try {
    $module = new Aerpoints();
    echo "✓ Module instance created successfully\n";
    echo "  - Name: " . $module->name . "\n";
    echo "  - Version: " . $module->version . "\n";
    echo "  - Display Name: " . $module->displayName . "\n";
} catch (Exception $e) {
    echo "✗ Error creating module instance: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check database tables (if module is installed)
echo "Test 4: Checking database structure...\n";
$tables = array(
    'aerpoints_customer',
    'aerpoints_product',
    'aerpoints_pending', 
    'aerpoints_history'
);

foreach ($tables as $table) {
    $result = Db::getInstance()->executeS('SHOW TABLES LIKE "' . _DB_PREFIX_ . $table . '"');
    if ($result) {
        echo "✓ Table {$table} exists\n";
    } else {
        echo "? Table {$table} not found (module may not be installed)\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "If all tests pass, the module is ready for installation in PrestaShop.\n";
echo "To install: Go to Modules > Add New Module and install aerpoints.zip\n";
