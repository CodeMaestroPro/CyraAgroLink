<?php

declare(strict_types=1);

$public = dirname(__DIR__).'/public/images/consumer';

if (! is_dir($public)) {
    mkdir($public, 0775, true);
}

$assets = [
    'rice-ofada.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Brown_rice.jpg/1280px-Brown_rice.jpg',
    'honey-raw.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/34/Dipper_stick_and_honey_in_a_jar.jpg/1280px-Dipper_stick_and_honey_in_a_jar.jpg',
    'palm-oil.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Epo_%28red_palm_oil%29.jpg/1280px-Epo_%28red_palm_oil%29.jpg',
    'yam-flour.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9b/Weizenmehl_40kg_S%C3%A4cke.jpg/1280px-Weizenmehl_40kg_S%C3%A4cke.jpg',
    'tomatoes.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/Tomato_je.jpg/1280px-Tomato_je.jpg',
    'oranges.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/Ambersweet_oranges.jpg/1280px-Ambersweet_oranges.jpg',
    'plantain.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/Plantain.jpg/1280px-Plantain.jpg',
    'groundnut-oil.jpg' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?fm=jpg&fit=crop&w=1200&h=900&q=85',
];

$fallbacks = [
    'plantain.jpg' => dirname(__DIR__).'/public/images/marketplace/plantain.jpg',
    'tomatoes.jpg' => dirname(__DIR__).'/public/images/marketplace/tomato.jpg',
    'groundnut-oil.jpg' => dirname(__DIR__).'/public/images/marketplace/groundnut.jpg',
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

    if ($body !== false && $code < 400 && strlen($body) >= 5000 && str_contains($type, 'image')) {
        file_put_contents($dest, $body);
        echo "OK {$file}\n";
        continue;
    }

    if (isset($fallbacks[$file]) && is_file($fallbacks[$file])) {
        copy($fallbacks[$file], $dest);
        echo "OK {$file} (fallback)\n";
        continue;
    }

    fwrite(STDERR, "FAIL {$file} code={$code} type={$type}\n");
}
