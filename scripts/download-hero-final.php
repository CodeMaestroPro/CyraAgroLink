<?php

declare(strict_types=1);

$public = dirname(__DIR__).'/public/images';
$dir = $public.'/_hero_candidates';
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$urls = [
    'p1' => 'https://cdn.pixabay.com/photo/2016/11/21/15/14/africa-1846051_1280.jpg',
    'p2' => 'https://cdn.pixabay.com/photo/2017/06/09/12/56/africa-2386550_1280.jpg',
    'p3' => 'https://cdn.pixabay.com/photo/2016/03/27/19/57/woman-1283869_1280.jpg',
    'p4' => 'https://cdn.pixabay.com/photo/2015/07/17/22/43/student-849825_1280.jpg',
    'p5' => 'https://cdn.pixabay.com/photo/2016/11/29/09/32/adult-1868750_1280.jpg',
    'p6' => 'https://images.pexels.com/photos/2132250/pexels-photo-2132250.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=2000',
    'p7' => 'https://images.pexels.com/photos/1112080/pexels-photo-1112080.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=2000',
    'p8' => 'https://images.pexels.com/photos/2382665/pexels-photo-2382665.jpeg?auto=compress&cs=tinysrgb&dpr=2&w=2000',
    'p9' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?fm=jpg&fit=crop&w=2000&h=1400&q=90',
];

foreach ($urls as $name => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; CyraAgroLinkAssetBot/1.0)',
        CURLOPT_HTTPHEADER => ['Accept: image/jpeg'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && is_string($body) && strlen($body) > 20000) {
        $path = "{$dir}/{$name}.jpg";
        file_put_contents($path, $body);
        $info = getimagesize($path) ?: [0, 0];
        echo "OK {$name} {$info[0]}x{$info[1]} ".number_format(strlen($body) / 1024, 1)."KB\n";
    } else {
        echo "FAIL {$name} {$code}\n";
    }
}

// Prefer sharp farm landscape for cassava investment card.
$hqFarm = $dir.'/cassava-farm-hq.jpg';
if (is_file($hqFarm)) {
    copy($hqFarm, $public.'/investments/cassava-farm.jpg');
    echo "Updated investments/cassava-farm.jpg\n";
}
