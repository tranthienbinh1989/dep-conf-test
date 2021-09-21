<?php

$config = [
    'package-a' => [
        'public' => '1.0.3',
        'private' => '1.0.1',
        'composer' => '1.0.3',
        'private-remote' => 'origin',
        'public-remote' => 'public',
    ],
    'package-b' => [
        'public' => '1.0.1',
        'private' => '1.0.3',
        'composer' => '1.0.*',
        'private-remote' => 'origin',
        'public-remote' => 'public',
    ],
    'package-c' => [
        'public' => '1.0.1',
        'private' => '1.0.1',
        'composer' => '1.0.*',
        'private-remote' => 'origin',
        'public-remote' => 'public',
    ],
];
function info(string $text) {
    echo "\033[34m" . $text . "\033[0m" . \PHP_EOL;
}
function execShell(string $cmd) {
    echo "\033[32m" . 'Running: ' . "\033[33m". $cmd . "\033[0m" . \PHP_EOL;
    echo `$cmd` . \PHP_EOL;
}

foreach ($config as $package => $packageConfig) {
    var_dump($package, $packageConfig);
    $packagePath = __DIR__ . DIRECTORY_SEPARATOR . $package;
    $gitC = '-C ' . escapeshellarg($packagePath);

    info('PRIVATE-' . $package . ': Deleting existing tags remotely');
    execShell("git ${gitC} tag -l | xargs -n 1 git ${gitC} push --delete " . escapeshellarg($packageConfig['private-remote']));
    info('PRIVATE-' . $package . ': Deleting existing tags locally');
    execShell("git ${gitC} tag -l | xargs -n 1 git ${gitC} tag -d");

    info('PRIVATE-' . $package . ': Resetting to master');
    execShell("git ${gitC} reset --hard master");

    info('PRIVATE-' . $package . ': Writing composer.json version ' . $packageConfig['private']);
    $composer = json_decode(file_get_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json'), true);
    $composer['version'] = $packageConfig['private'];
    file_put_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));

    info('PRIVATE-' . $package . ': Adding composer');
    execShell("git ${gitC} add composer.json");
    info('PRIVATE-' . $package . ': Committing composer');
    execShell("git ${gitC} commit -m " . escapeshellarg('Private ' . $packageConfig['private']));
    info('PRIVATE-' . $package . ': Pushing private');
    execShell("git ${gitC} push " . escapeshellarg($packageConfig['private-remote']) . ' master --force');
    info('PRIVATE-' . $package . ': Tagging private');
    execShell("git ${gitC} tag " . escapeshellarg($packageConfig['private']));
    info('PRIVATE-' . $package . ': Pushing private tag');
    execShell("git ${gitC} push " . escapeshellarg($packageConfig['private-remote']) . ' ' . escapeshellarg($packageConfig['private']) . ' --force');
}

info('Building packages');
execShell("composer run-script build-satis");
$packageJsonPath = __DIR__ . \DIRECTORY_SEPARATOR . 'build' . \DIRECTORY_SEPARATOR . 'packages.json';
$packageJson = json_decode(file_get_contents($packageJsonPath), true);
$packageJson['metadata-url'] = '/p2/%package%.json';
file_put_contents($packageJsonPath, json_encode($packageJson, \JSON_PRETTY_PRINT));

foreach ($config as $package => $packageConfig) {
    $packagePath = __DIR__ . DIRECTORY_SEPARATOR . $package;
    $gitC = '-C ' . escapeshellarg($packagePath);

    info('PUBLIC-' . $package . ': Deleting existing tags remotely');
    execShell("git ${gitC} tag -l | xargs -n 1 git ${gitC} push --delete " . escapeshellarg($packageConfig['public-remote']));

    info('PUBLIC-' . $package . ': Resetting to master');
    execShell("git ${gitC} reset --hard master");

    info('PUBLIC-' . $package . ': Writing composer.json version ' . $packageConfig['public']);
    $composer = json_decode(file_get_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json'), true);
    $composer['version'] = $packageConfig['public'];
    file_put_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));

    info('PUBLIC-' . $package . ': Adding composer');
    execShell("git ${gitC} add composer.json");
    info('PUBLIC-' . $package . ': Committing composer');
    execShell("git ${gitC} commit -m " . escapeshellarg('Public ' . $packageConfig['public']));
    info('PUBLIC-' . $package . ': Pushing public');
    execShell("git ${gitC} push " . escapeshellarg($packageConfig['public-remote']) . ' master --force');
    info('PUBLIC-' . $package . ': Tagging private');
    execShell("git ${gitC} tag " . escapeshellarg($packageConfig['public']));
    info('PUBLIC-' . $package . ': Pushing private tag');
    execShell("git ${gitC} push " . escapeshellarg($packageConfig['public-remote']) . ' ' . escapeshellarg($packageConfig['public']) . ' --force');
}

