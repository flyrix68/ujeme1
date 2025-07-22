&lt;?php
header('Content-Type: text/plain');
echo "MINIMAL TEST WORKING\n";
echo "PHP Version: ".phpversion()."\n";
echo "Extensions: ".implode(", ", get_loaded_extensions())."\n";
?&gt;
