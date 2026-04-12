<?php
echo "Loaded Configuration File: " . php_ini_loaded_file() . "\n";
echo "curl.cainfo: " . ini_get('curl.cainfo') . "\n";
echo "openssl.cafile: " . ini_get('openssl.cafile') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
?>
