<?php
$constraints = ['^1.0.0','~1.0.0','1.0.*','1.0.8','>1.0.0 <=2.0.0','1.0.0 - 2.0.0','^1.0.2-beta1','*'];
$filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'test-config.csv';


$fh = fopen($filePath, 'wb');
fputcsv($fh, ['public_before_private','public_canonical_defined','private_canonical_defined','public_is_canonical','private_is_canonical','constraint','composer_version'], "\t");
foreach ($constraints as $constraint) {
    foreach (['TRUE','FALSE'] as $publicBeforePrivate) {
        foreach (['TRUE','FALSE'] as $publicCanonicalDefined) {
            foreach (['TRUE','FALSE'] as $privateCanonicalDefined) {
                foreach (['TRUE','FALSE'] as $publicIsCanonical) {
                    foreach (['TRUE','FALSE'] as $privateIsCanonical) {
                        fputcsv($fh,[
                            $publicBeforePrivate,
                            $publicCanonicalDefined,
                            $privateCanonicalDefined,
                            $publicCanonicalDefined === 'TRUE' ? $publicIsCanonical : 'N/A',
                            $privateCanonicalDefined === 'TRUE' ? $privateIsCanonical : 'N/A',
                            $constraint,
                            '2'
                        ],"\t");
                    }
                }
            }
        }
    }
}

$publicCanonicalDefined = $privateCanonicalDefined = $publicIsCanonical = $privateIsCanonical = 'FALSE';
foreach ($constraints as $constraint) {
    foreach (['TRUE','FALSE'] as $publicBeforePrivate) {
        fputcsv($fh, [
            $publicBeforePrivate,
            $publicCanonicalDefined,
            $privateCanonicalDefined,
            'N/A',
            'N/A',
            $constraint,
            '1'
        ],"\t");
    }
}
fclose($fh);