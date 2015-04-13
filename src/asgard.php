<?php
/**
 * This script is the entry point for all ASGard commands.
 */
define('ASGARD_ROOT', __DIR__);
$homedir = isset($_SERVER['HOME'])
    ? $_SERVER['HOME']
    : \posix_getpwuid(posix_getuid())['dir'];
define('ASGARD_USER_HOME', $homedir);
define('ASGARD_LOCAL_CONFIG', ASGARD_USER_HOME.'/.asgard');

if (!\is_dir(ASGARD_LOCAL_CONFIG)) {
    \mkdir(ASGARD_LOCAL_CONFIG, 0700);
}

/**
 * 1. Register an autoloader for all the classes we use
 */
require __DIR__."/autoload.php";

/**
 * 2. Load the configuration
 */
if (\is_readable(__DIR__."/data/config.json")) {
    // Allow people to edit the JSON config and define their own locations
    $config = \json_decode(
        \file_get_contents(__DIR__."/data/config.json"),
        true
    );
} else {
    // Sane defaults
    $config = [
        'storage' => [
            'blockchain' => [
                'driver' => 'sqlite',
                'path' => 'blockchain.etilqs'
            ],
            'settings' => [
                'driver' => 'sqlite',
                'path' => 'asgard.etilqs'
            ]
        ]
    ];
}

if (!\class_exists('Sodium')) {
    // Don't disable this. Our BLAKE2b source is Sodium::crypto_generichash()
    die(
        "Please install libsodium and the libsodium-php extension from PECL\n\n".
        "\thttp://doc.libsodium.org/installation/README.html\n".
        "\thttp://pecl.php.net/package/libsodium\n"
    );
}

// Let's load our storage adapters
$store = \ParagonIE\AsgardClient\Utilities::getStorageAdapter('settings', $config);
$bchain = \ParagonIE\AsgardClient\Utilities::getStorageAdapter('blockchain', $config);
$notary = \ParagonIE\AsgardClient\Utilities::getStorageAdapter('notary', $config);

/**
 * 3. Process the CLI parameters
 */
$showAll = true;
if ($argc < 2) {
    // Default behavior: Display the help menu
    $argv[1] = 'help';
    $showAll = false;
    $argc = 2;
}

// Create a little cache for the Help command, if applicable. Doesn't contain objects.
$commands = [];

foreach (\glob(__DIR__.'/Client/Commands/*.php') as $file) {
    // Let's build a queue of all the file names
    
    // Grab the filename from the Commands directory:
    $classname = \preg_replace('#.*/([A-Za-z0-9_]+)\.php$#', '$1', $file);
    $index = \strtolower($classname);
    
    // Append to $commands array
    $commands[$index] = $classname;

    if ($argv[1] !== 'help') {
        // If this is the command the user passed...
        if ($index === $argv[1]) {
            // Instantiate this object
            $exec = \ParagonIE\AsgardClient\Command::getCommandStatic($classname);
            // Store the relevant storage devices in the command, in case they're needed
            $exec->setStorage($store, $bchain);
            // Execute it, passing the extra parameters to the command's fire() method
            $exec->fire(
                \array_values(
                    \array_slice($argv, 2)
                )
            );
            exit;
        }
    }
}

/**
 * 4. If all else fails, fall back to the help class...
 */
$help = new \ParagonIE\AsgardClient\Commands\Help($commands);
$help->showAll = $showAll;
$help->fire(
    \array_values(
        \array_slice($argv, 2)
    )
);
exit;
