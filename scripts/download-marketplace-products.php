<?php

declare(strict_types=1);

/**
 * Download additional marketplace commodity images (name-matched).
 */
$public = dirname(__DIR__).'/public/images/marketplace';

if (! is_dir($public)) {
    mkdir($public, 0775, true);
}

$assets = [
    // Yam tubers
    'yam.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Yam_%28Dioscorea%29.jpg/1280px-Yam_%28Dioscorea%29.jpg',
    // Sorghum grain head
    'sorghum.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1c/Sorghum.jpg/1280px-Sorghum.jpg',
    // Groundnuts / peanuts
    'groundnut.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/PeanutButter.jpg/1280px-PeanutButter.jpg',
    // Pearl millet alternative
    'millet.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Pearl_millet_in_field.jpg/1280px-Pearl_millet_in_field.jpg',
    // Sesame seeds
    'sesame.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bf/Sesamum-indicum.jpg/1280px-Sesamum-indicum.jpg',
    // Plantain / bananas cooking type
    'plantain.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/Plantains.jpg/1280px-Plantains.jpg',
];

// Fallbacks if primary Wikimedia paths 404
$fallbacks = [
    'yam.jpg' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?fm=jpg&fit=crop&w=1200&h=900&q=85',
    'sorghum.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=1200&h=900&q=85',
    'groundnut.jpg' => 'https://images.unsplash.com/photo-1599599810769-bcde5a160d32?fm=jpg&fit=crop&w=1200&h=900&q=85',
    'millet.jpg' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?fm=jpg&fit=crop&w=1200&h=900&q=85',
    'sesame.jpg' => 'https://images.unsplash.com/photo-1606923829579-0cb981a83e2e?fm=jpg&fit=crop&w=1200&h=900&q=85',
    'plantain.jpg' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?fm=jpg&fit=crop&w=1200&h=900&q=85',
];

foreach ($assets as $file => $url) {
    $dest = $public.'/'.$file;
    $ok = downloadImage($url, $dest, $file);

    if (! $ok && isset($fallbacks[$file])) {
        $ok = downloadImage($fallbacks[$file], $dest, $file.' (fallback)');
    }

    if (! $ok) {
        fwrite(STDERR, "FAIL {$file}\n");
    }
}

function downloadImage(string $url, string $dest, string $label): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CyraAgroLinkAssetBot/1.0)',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($body === false || $code >= 400 || strlen($body) < 3000) {
        fwrite(STDERR, "TRY FAIL {$label} code={$code} type={$type} bytes=".strlen((string) $body)."\n");

        return false;
    }

    // Accept image/* or octet-stream from CDNs
    if ($type !== '' && ! str_contains($type, 'image') && ! str_contains($type, 'octet-stream')) {
        fwrite(STDERR, "TRY FAIL {$label} unexpected type={$type}\n");

        return false;
    }

    file_put_contents($dest, $body);
    echo 'OK '.$label.' ('.strlen($body)." bytes)\n";

    return true;
}
