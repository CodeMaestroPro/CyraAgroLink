<?php

declare(strict_types=1);

$public = dirname(__DIR__).'/public/images';
$dir = $public.'/_hero_candidates';
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$candidates = [
    'f1' => 'https://images.pexels.com/photos/5721187/pexels-photo-5721187.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f2' => 'https://images.pexels.com/photos/4503273/pexels-photo-4503273.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f3' => 'https://images.pexels.com/photos/6213455/pexels-photo-6213455.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f4' => 'https://images.pexels.com/photos/2886937/pexels-photo-2886937.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f5' => 'https://images.pexels.com/photos/2382663/pexels-photo-2382663.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f6' => 'https://images.pexels.com/photos/1092875/pexels-photo-1092875.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f7' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?fm=jpg&fit=crop&w=1600&h=1600&q=90',
    'f8' => 'https://images.unsplash.com/photo-1589156280159-27698aabc5b6?fm=jpg&fit=crop&w=1600&h=1600&q=90',
    'f9' => 'https://images.pexels.com/photos/5560019/pexels-photo-5560019.jpeg?auto=compress&cs=tinysrgb&w=2000',
    'f10' => 'https://images.pexels.com/photos/4505161/pexels-photo-4505161.jpeg?auto=compress&cs=tinysrgb&w=2000',
];

foreach ($candidates as $name => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CyraAgroLinkAssetBot/1.0)',
        CURLOPT_HTTPHEADER => ['Accept: image/jpeg,image/jpg,*/*;q=0.5'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && is_string($body) && strlen($body) > 20000) {
        $path = "{$dir}/{$name}.jpg";
        file_put_contents($path, $body);
        $info = getimagesize($path);
        echo "OK {$name} {$info[0]}x{$info[1]} ".number_format(strlen($body) / 1024, 1)."KB\n";
    } else {
        echo "FAIL {$name} {$code} size=".strlen((string) $body)."\n";
    }
}
