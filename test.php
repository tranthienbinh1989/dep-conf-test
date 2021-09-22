<?php
$packageName = 'nathanjosiah/dep-conf-test-package-a';

$config = [
    [
        'constraint' => '1.0.*',
        'result-type' => 'error'
    ],
    [
        'constraint' => '1.0.3',
        'result-type' => 'warning'
    ],
    [
        'constraint' => '^1.0.0',
        'result-type' => 'error'
    ],
    [
        'constraint' => '',
        'result-type' => 'error'
    ],
];

function info(string $text) {
    echo "\033[34m" . $text . "\033[0m" . \PHP_EOL;
}
function error(string $text) {
    echo "\033[31m" . $text . "\033[0m" . \PHP_EOL;
}

function execShell(string $cmd) {
    echo "\033[32m" . 'Running: ' . "\033[33m". $cmd . "\033[0m" . \PHP_EOL;
    $result = `$cmd`;
    echo $result . \PHP_EOL;

    return $result;
}

$errors = [];

foreach ($config as $configItem) {
    $escapedPackageName = escapeshellarg($packageName);
    $escapedConstraint = escapeshellarg($configItem['constraint']);
    info('Composer require for ' . $configItem['constraint']);
    $result = execShell('composer require ' . $escapedPackageName . ($configItem['constraint'] ? ' ' . $escapedConstraint: '') . ' 2>&1');
    $composer = json_decode(file_get_contents(__DIR__ . \DIRECTORY_SEPARATOR . 'composer.json'), true);

    $hasMessage = strpos($result, 'might\'ve been taken over by a malicious entity,') !== false;
    $result = execShell('composer show ' . $escapedPackageName . ' 2>&1');
    $packageInstalled = strpos($result, 'Package ' . $packageName . ' not found') === false;
    if (!$hasMessage && $configItem['result-type'] !== 'success') {
        $errors[] = [
            'config' => $configItem,
            'type' => 'composer require',
            'message' => 'no warning/error message detected'
        ];
    }
    if ($packageInstalled && $configItem['result-type'] === 'error') {
        $errors[] = [
            'config' => $configItem,
            'type' => 'composer require',
            'message' => 'installed anyway'
        ];
    } elseif (!$packageInstalled && $configItem['result-type'] !== 'error') {
        $errors[] = [
            'config' => $configItem,
            'type' => 'composer require',
            'message' => 'not installed'
        ];
    }

    if ($packageInstalled) {
        info('Removing package');
        execShell('composer remove ' . $escapedPackageName);
    }

    $composer = json_decode(file_get_contents(__DIR__ . \DIRECTORY_SEPARATOR . 'composer.json'), true);
    info('Composer update for ' . $configItem['constraint']);
    $composer['require'][$packageName] = ($configItem['constraint'] ? $configItem['constraint'] : '*');
    file_put_contents(__DIR__ . \DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));


    $result = execShell('composer update 2>&1');
    $hasMessage = strpos($result, 'might\'ve been taken over by a malicious entity,') !== false;
    $result = execShell('composer show ' . $escapedPackageName . ' 2>&1');
    $packageInstalled = strpos($result, 'Package ' . $packageName . ' not found') === false;
    if (!$hasMessage && $configItem['result-type'] !== 'success') {
        $errors[] = [
            'config' => $configItem,
            'type' => 'composer require',
            'message' => 'no warning/error message detected'
        ];
    }
    if ($packageInstalled && $configItem['result-type'] === 'error') {
        $errors[] = [
            'config' => $configItem,
            'type' => 'composer require',
            'message' => 'installed anyway'
        ];
    } elseif (!$packageInstalled && $configItem['result-type'] !== 'error') {
        $errors[] = [
            'config' => $configItem,
            'type' => 'composer require',
            'message' => 'not installed'
        ];
    }
    if ($packageInstalled) {
        info('Removing package');
        execShell('composer remove ' . $escapedPackageName);
    }
}

foreach ($errors as $error) {
    error('For constraint "' . $error['config']['constraint'] . '" using "' . $error['type'] . '" there was an error: "' . $error['message'] . '"');
}

if (empty($errors)) {
    info('No detected errors. Done');
}
