<?php

/**
 * Standalone boot probe run as a fresh subprocess (see PackageService::verifyBoot()) so a package
 * that only crashes during Laravel's service provider register()/boot() phase can be detected
 * without taking down the process that's driving the Packages UI. Must be invoked with the
 * froxlor application's base path as the current working directory.
 */

require getcwd() . '/vendor/autoload.php';

try {
    /** @var \Illuminate\Foundation\Application $app */
    $app = require getcwd() . '/bootstrap/app.php';

    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    fwrite(STDOUT, json_encode(['status' => 'ok']));
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, json_encode([
        'status' => 'error',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'message' => $e->getMessage(),
    ]));
    exit(1);
}
