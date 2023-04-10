<?php
$packageName = 'nathanjosiah/dep-conf-test-package-a';
$publicRepo = [
    'type' => 'composer',
    'url' => 'https://repo.packagist.org/'
];
$privateRepo = [
    'type' => 'composer',
    'url' => 'https://flamboyant-haibt-5db8f9.netlify.app/'
];

$fh = fopen(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'test-config.csv', 'rb');

$headers = array_flip(fgetcsv($fh, null, "\t"));
$config = [];
while ($row = fgetcsv($fh, null, "\t")) {
    $c = [];
    foreach ($headers as $header => $index) {
        if (!isset($row[$index])) {
            $c[$header] = null;
            continue;
        }
        $c[$header] = $row[$index];
        if ($c[$header] === 'FALSE') {
            $c[$header] = false;
        } elseif ($c[$header] === 'TRUE') {
            $c[$header] = true;
        }
    }
    $config[] = $c;
}

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
function readComposer(): array {
    return json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'composer.json'), true);
}
function writeComposer(array $composer): void {
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));
}
function getInstalledPackageVersion(string $packageName): string
{
    $composerDirectoryArg = '-d ' . escapeshellarg(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..')) . ' ';
    $result = execShell('composer show ' . $composerDirectoryArg . escapeshellarg($packageName) . ' 2>&1');
    preg_match('/versions\s+:\s+(?P<version>.*)/', $result, $matches);
    if (!isset($matches['version'])) {
        return '';
    }
    return $matches['version'];
}
function composerRequire(string $packageName, string $constraint): string {
    $constraint = ($constraint === '*' ? null : $constraint);
    $composerDirectoryArg = '-d ' . escapeshellarg(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..')) . ' ';
    return execShell('COMPOSER_MEMORY_LIMIT=-1 composer require ' . $composerDirectoryArg . escapeshellarg($packageName) . ($constraint ? ':' . escapeshellarg($constraint): '') . ' 2>&1');
}
function composerRemove(string $packageName): void {
    if (getInstalledPackageVersion($packageName)) {
        $composerDirectoryArg = '-d ' . escapeshellarg(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..')) . ' ';
        execShell('COMPOSER_MEMORY_LIMIT=-1 composer remove ' . $composerDirectoryArg . escapeshellarg($packageName));
    }
}

info('Checking if package is already installed before starting');
composerRemove($packageName);

preg_match('/version (?P<major>\d)/', execShell('composer -V'), $currentComposerVersion);
$lastComposerVersion = (int)$currentComposerVersion['major'];

foreach ($config as $index => &$configItem) {
    info('Scenario ' . ($index + 1) . '/' . count($config));
    info('public_before_private: ' . ($configItem['public_before_private'] ? 'yes' : 'no'));
    info('public_canonical_defined: ' . ($configItem['public_canonical_defined'] ? 'yes' : 'no'));
    info('private_canonical_defined: ' . ($configItem['private_canonical_defined'] ? 'yes' : 'no'));
    if ($configItem['public_canonical_defined']) {
        info('public_is_canonical: ' . ($configItem['public_is_canonical'] ? 'yes' : 'no'));
    }
    if ($configItem['private_canonical_defined']) {
        info('private_is_canonical: ' . ($configItem['private_is_canonical'] ? 'yes' : 'no'));
    }
    info('constraint: ' . $configItem['constraint']);
    info('composer_version: ' . $configItem['composer_version']);

    if ($lastComposerVersion !== (int)$configItem['composer_version']) {
        info('Switching composer version');
        execShell('composer self-update --' . $configItem['composer_version']);
        $lastComposerVersion = (int)$configItem['composer_version'];
    }

    info('Modifying composer');
    $composer = readComposer();
    unset($composer['repositories']['public'],$composer['repositories']['private']);
    $order = ($configItem['public_before_private'] ? ['public', 'private'] : ['private','public']);
    $composer['repositories'][$order[0]] = ${$order[0] . 'Repo'};
    $composer['repositories'][$order[1]] = ${$order[1] . 'Repo'};
    if ($configItem['public_canonical_defined']) {
        $composer['repositories']['public']['canonical'] = $configItem['public_is_canonical'];
    }
    if ($configItem['private_canonical_defined']) {
        $composer['repositories']['private']['canonical'] = $configItem['private_is_canonical'];
    }
    writeComposer($composer);

    info('Requiring Package');
    $result = composerRequire($packageName, $configItem['constraint']);
    $hadAuditErrorMessage = strpos($result, 'might\'ve') !== false;
    $hadComposerErrorMessage = strpos($result, 'packages from the higher priority') !== false;
    $installedPackageVersion = getInstalledPackageVersion($packageName);

    $configItem['version_installed'] = $installedPackageVersion;
    $configItem['had_audit_message'] = $hadAuditErrorMessage;
    $configItem['had_composer_error_message'] = $hadComposerErrorMessage;

    if ($installedPackageVersion) {
        info('Removing package');
        composerRemove($packageName);
    }
}
unset($configItem);
@mkdir(__DIR__ . \DIRECTORY_SEPARATOR . '..' . \DIRECTORY_SEPARATOR . 'results');
$fh = fopen(__DIR__ . \DIRECTORY_SEPARATOR . 'results' . \DIRECTORY_SEPARATOR . 'results-'. time() . '.csv', 'wb');
fputcsv($fh, array_keys($config[0]));
foreach ($config as $configItem) {
    foreach ($configItem as $key => $item) {
        if ($item === true) {
            $configItem[$key] = 'TRUE';
        } elseif ($item === false) {
            $configItem[$key] = 'FALSE';
        }
    }
    fputcsv($fh, $configItem);
}
fclose($fh);

