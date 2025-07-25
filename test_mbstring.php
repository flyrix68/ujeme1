<?php
// Test mbstring functionality
echo "&lt;pre&gt;";
echo "mbstring extension loaded: " . (extension_loaded('mbstring') ? 'YES' : 'NO') . "\n";

if(extension_loaded('mbstring')) {
    echo "mbstring functions available? " . (function_exists('mb_convert_encoding') ? 'YES' : 'NO') . "\n";
    $test = @mb_convert_encoding('test', 'UTF-8', 'auto');
    echo "mb_convert_encoding test: " . ($test !== false ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "mbstring not available\n";
}

// Output PHP info
echo "\nPHP Configuration:\n";
phpinfo();
echo "&lt;/pre&gt;";
