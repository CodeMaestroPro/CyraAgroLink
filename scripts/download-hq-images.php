<?php

declare(strict_types=1);

/**
 * Download high-resolution photographic assets for CyraAgroLink.
 * Forces JPEG delivery and stores originals without blurry upscaling.
 */

$public = dirname(__DIR__).'/public/images';

$assets = [
    'marketplace/maize.jpg' => 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?fm=jpg&fit=crop&w=1600&h=1600&q=90',
    'marketplace/rice.jpg' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?fm=jpg&fit=crop&w=1600&h=1600&q=90',
    'marketplace/cassava.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/11/Balanghoy_(Manihot_esculenta)_tubers.jpg/1280px-Balanghoy_(Manihot_esculenta)_tubers.jpg',
    'marketplace/cocoa.jpg' => 'https://images.unsplash.com/photo-1705542116578-b6e7972479f1?fm=jpg&fit=crop&w=1600&h=1600&q=90',

    'marketplace/supplier-1.jpg' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'marketplace/supplier-2.jpg' => 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'marketplace/supplier-3.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'marketplace/supplier-4.jpg' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?fm=jpg&fit=crop&w=2000&h=1250&q=90',

    'investments/maize-expansion.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'investments/cassava-farm.jpg' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'investments/rice-production.jpg' => 'https://images.unsplash.com/photo-1548710332-3e03e8220a79?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'investments/hero-field.jpg' => 'https://images.unsplash.com/photo-1523348837708-15d4a09cfac2?fm=jpg&fit=crop&w=2200&h=1100&q=90',

    'farms/green-valley.jpg' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?fm=jpg&fit=crop&w=2000&h=1250&q=90',
    'farms/sunrise.jpg' => 'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?fm=jpg&fit=crop&w=2000&h=1250&q=90',

    'dashboard/ai-recommendation.jpg' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?fm=jpg&fit=crop&w=1600&h=1600&q=90',
    'avatars/adewale.jpg' => 'https://images.unsplash.com/photo-1522529599102-193c0d76b5b6?fm=jpg&fit=crop&w=800&h=800&q=90',

    // Landing hero (native high-res farm photography)
    'hero-farmer.jpg' => 'https://images.pexels.com/photos/2132250/pexels-photo-2132250.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=2000',

    'logistics/truck-10t.jpg' => 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?fm=jpg&fit=crop&w=800&h=500&q=90',
    'logistics/truck-20t.jpg' => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?fm=jpg&fit=crop&w=800&h=500&q=90',
    'logistics/truck-15t.jpg' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?fm=jpg&fit=crop&w=800&h=500&q=90',
];

function download(string $url, string $dest): void
{
    $dir = dirname($dest);
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CyraAgroLinkAssetBot/1.0)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: image/jpeg,image/jpg,image/png,*/*;q=0.5',
        ],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status >= 400 || strlen($body) < 8000) {
        throw new RuntimeException("HTTP {$status} {$error} ctype={$ctype} size=".strlen((string) $body));
    }

    file_put_contents($dest, $body);
    $info = @getimagesize($dest);

    if ($info === false) {
        // Some CDNs return webp despite Accept — convert if possible.
        if (function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($dest);
            if ($src !== false) {
                imagejpeg($src, $dest, 92);
                imagedestroy($src);
                $info = getimagesize($dest);
            }
        }
    }

    if ($info === false) {
        @unlink($dest);
        throw new RuntimeException("Not a decodable image (ctype={$ctype})");
    }

    // If PNG from Wikimedia, convert to JPEG for consistent delivery.
    if (($info['mime'] ?? '') === 'image/png') {
        $src = imagecreatefrompng($dest);
        $canvas = imagecreatetruecolor($info[0], $info[1]);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $info[0], $info[1], $white);
        imagecopy($canvas, $src, 0, 0, 0, 0, $info[0], $info[1]);
        imagejpeg($canvas, $dest, 92);
        imagedestroy($src);
        imagedestroy($canvas);
        $info = getimagesize($dest);
    }

    echo sprintf(
        "OK  %s  %dx%d  %s KB  %s\n",
        str_replace('\\', '/', substr($dest, strlen(dirname(__DIR__).'/public/images/'))),
        $info[0],
        $info[1],
        number_format(filesize($dest) / 1024, 1),
        $info['mime']
    );
}

$failed = 0;

foreach ($assets as $relative => $url) {
    try {
        download($url, $public.'/'.$relative);
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, 'FAIL '.$relative.': '.$e->getMessage().PHP_EOL);
    }
}

if ($failed > 0) {
    exit(1);
}

echo "All high-resolution assets ready.\n";
