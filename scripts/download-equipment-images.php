<?php

declare(strict_types=1);

/**
 * Download product-matching equipment photos from Wikimedia Commons.
 *
 * Usage: php scripts/download-equipment-images.php
 */

$public = dirname(__DIR__).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'equipment';

if (! is_dir($public)) {
    mkdir($public, 0775, true);
}

$assets = [
    'john-deere-5075e.jpg' => 'Maimarkt Mannheim 2015 - John Deere 5075E.JPG',
    'new-holland-tx66.jpg' => 'New_Holland_TX66.jpg',
    'case-ih-axial.jpg' => 'Case combine.jpg',
    'irrigation-pump.jpg' => 'Irrigation_pump.jpg',
    'massey-ferguson-375.jpg' => 'Massey Ferguson 165.jpg',
    'offset-disc-plough.jpg' => 'Kaddu vali harrow = disc plough 03.jpg',
    'knapsack-sprayer.jpg' => 'Knapsack sprayer.jpg',
    'mobile-grain-mill.jpg' => 'Hammer mill.jpg',
    'mechanic-tool-chest.jpg' => 'Toolbox.jpg',
    'solar-cold-room.jpg' => 'Cold storage.jpg',
    'drip-irrigation-kit.jpg' => 'Drip irrigation.jpg',
    'disc-harrow.jpg' => 'Disk harrow.jpg',
    'boom-sprayer.jpg' => 'Crop Sprayer - geograph.org.uk - 445527.jpg',
    'rice-mill-line.jpg' => 'Rice mill.jpg',
    'welding-plant.jpg' => 'Welding.jpg',
    'weighbridge.jpg' => 'Weighbridge.jpg',
    'tractor-filter-kit.jpg' => 'Engine oil filter cutaway.JPG',
    'harvester-belt-pack.jpg' => 'Timing belt.jpg',
    'pump-impeller-set.jpg' => 'Impeller.jpg',
    'plough-share-blades.jpg' => 'Kverneland AB 85 plough 3.jpg',
    'sprayer-nozzle-pack.jpg' => 'Garden hose nozzle.jpg',
    'grain-mill-stones.jpg' => 'Millstone.jpg',
    'hydraulic-seal-kit.jpg' => 'O-rings.jpg',
    'cold-room-fan-motor.jpg' => 'Axial fan.jpg',
];

$force = in_array('--force', $argv, true);
$ok = 0;
$fail = 0;
$skip = 0;

foreach ($assets as $file => $commonsName) {
    $dest = $public.DIRECTORY_SEPARATOR.$file;

    if (! $force && is_file($dest) && filesize($dest) > 4000) {
        echo "SKIP {$file}\n";
        $skip++;
        continue;
    }

    $url = 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($commonsName).'?width=1400';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'CyraAgroLinkAssetBot/1.0 (equipment marketplace)',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($body === false || $code >= 400 || strlen((string) $body) < 4000 || ! str_contains($type, 'image')) {
        fwrite(STDERR, "FAIL {$file} <- {$commonsName} code={$code} type={$type} bytes=".strlen((string) $body)."\n");
        $fail++;
        continue;
    }

    file_put_contents($dest, $body);
    echo "OK {$file} (".strlen($body)." bytes)\n";
    $ok++;
}

echo "Done. ok={$ok} skip={$skip} fail={$fail}\n";
exit($fail > 0 ? 1 : 0);
