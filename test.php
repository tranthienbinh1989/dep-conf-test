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

$csv = <<<DATA
public_before_private	public_is_canonical	private_is_canonical	constraint
FALSE	FALSE	FALSE	^1.0.0
FALSE	FALSE	TRUE	^1.0.0
FALSE	TRUE	FALSE	^1.0.0
FALSE	TRUE	TRUE	^1.0.0
TRUE	FALSE	FALSE	^1.0.0
TRUE	FALSE	TRUE	^1.0.0
TRUE	TRUE	FALSE	^1.0.0
TRUE	TRUE	TRUE	^1.0.0
FALSE	FALSE	FALSE	1.0.*
FALSE	FALSE	TRUE	1.0.*
FALSE	TRUE	FALSE	1.0.*
FALSE	TRUE	TRUE	1.0.*
TRUE	FALSE	FALSE	1.0.*
TRUE	FALSE	TRUE	1.0.*
TRUE	TRUE	FALSE	1.0.*
TRUE	TRUE	TRUE	1.0.*
DATA;
$fh = tmpfile();
fwrite($fh,$csv);
fseek($fh, 0);

$headers = array_flip(fgetcsv($fh, null, "\t"));
$config = [];
while ($row = fgetcsv($fh, null, "\t")) {
    $c = [];
    foreach ($headers as $header => $index) {
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
    return json_decode(file_get_contents(__DIR__ . \DIRECTORY_SEPARATOR . 'composer.json'), true);
}
function writeComposer(array $composer): void {
    file_put_contents(__DIR__ . \DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));
}
function getInstalledPackageVersion(string $packageName): string
{
    $result = execShell('composer show ' . escapeshellarg($packageName) . ' 2>&1');
    preg_match('/versions\s+:\s+(?P<version>.*)/', $result, $matches);
    if (!isset($matches['version'])) {
        return '';
    }
    return $matches['version'];
}
function composerRequire(string $packageName, string $constraint): string {
    return execShell('composer require ' . escapeshellarg($packageName) . ($constraint ? ' ' . escapeshellarg($constraint): '') . ' 2>&1');
}
function composerRemove(string $packageName): void {
    if (getInstalledPackageVersion($packageName)) {
        execShell('composer remove ' . escapeshellarg($packageName));
    }
}

info('Checking if package is already installed before starting');
composerRemove($packageName);

foreach ($config as &$configItem) {
    info('public_before_private:' . ($configItem['public_before_private'] ? 'yes' : 'no'));
    info('public_is_canonical:' . ($configItem['public_is_canonical'] ? 'yes' : 'no'));
    info('private_is_canonical:' . ($configItem['private_is_canonical'] ? 'yes' : 'no'));
    info('constraint:' . $configItem['constraint']);

    info('Modifying composer');
    $composer = readComposer();
    unset($composer['repositories']['public'],$composer['repositories']['private']);

    if ($configItem['public_before_private']) {
        $composer['repositories']['public'] = $publicRepo + [
            'canonical' => $configItem['public_is_canonical']
        ];
        $composer['repositories']['private'] = $privateRepo + [
            'canonical' => $configItem['private_is_canonical']
        ];
    } else {
        $composer['repositories']['private'] = $privateRepo + [
            'canonical' => $configItem['private_is_canonical']
        ];
        $composer['repositories']['public'] = $publicRepo + [
            'canonical' => $configItem['public_is_canonical']
        ];
    }
    writeComposer($composer);

    info('Requiring Package');
    $result = composerRequire($packageName, $configItem['constraint']);
    $hadAuditErrorMessage = strpos($result, 'might\'ve') !== false;
    $hadComposerErrorMessage = strpos($result, 'higher repository priority') !== false;
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
$fh = fopen(__DIR__ . \DIRECTORY_SEPARATOR . 'results' . \DIRECTORY_SEPARATOR . 'results-'. time() . '.csv', 'wb');
fputcsv($fh, array_keys($config[0]));
foreach ($config as $configItem) {
    fputcsv($fh, $configItem);
}
fclose($fh);

