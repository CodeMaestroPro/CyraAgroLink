<?php

declare(strict_types=1);

$public = dirname(__DIR__).'/public/images/academy';

if (! is_dir($public)) {
    mkdir($public, 0775, true);
}

$assets = [
    'maize-farming.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=1200&h=800&q=85',
    'irrigation.jpg' => 'https://images.unsplash.com/photo-1743742566156-f1745850281a?fm=jpg&fit=crop&w=1200&h=800&q=85',
    'pest-disease.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/Aphids.jpg/1280px-Aphids.jpg',
];

foreach ($assets as $file => $url) {
    $dest = $public.'/'.$file;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'CyraAgroLinkAssetBot/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($body === false || $code >= 400 || strlen($body) < 5000 || ! str_contains($type, 'image')) {
        fwrite(STDERR, "FAIL {$file} code={$code} type={$type}\n");
        continue;
    }

    file_put_contents($dest, $body);
    echo "OK {$file}\n";
}
