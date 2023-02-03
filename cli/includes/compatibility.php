<?php

// Allow bypassing these checks if using Valet in a non-CLI app
if (php_sapi_name() !== 'cli') {
    return;
}

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

// if (PHP_OS !== 'Darwin' && ! $inTestingEnvironment) {
//     echo 'Valet only supports the Mac operating system.'.PHP_EOL;

//     exit(1);
// }

if (version_compare(PHP_VERSION, '8.0', '<')) {
    echo 'Valet requires PHP 8.0 or later.';

    exit(1);
}

if (PHP_OS === 'Darwin') {
    if (exec('which brew') == '' && ! $inTestingEnvironment) {
        echo 'Valet requires Homebrew to be installed on your Mac.';

        exit(1);
    }
} elseif (PHP_OS === 'Linux') {
    // @todo: Check for Linux dependencies
} else {
    echo 'Valet can only be run on macOS or Linux. Your system is reporting as '.PHP_OS;

    exit(1);
}
