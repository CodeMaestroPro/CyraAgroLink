<?php

declare(strict_types=1);

$dir = dirname(__DIR__).'/public/images/_candidates';
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$map = [
    'cassava-a' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=1200&h=1200&q=90',
    'cassava-b' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=1200&h=1200&q=90',
    'cassava-c' => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?auto=format&fit=crop&w=1200&h=1200&q=90',
    'cocoa-a' => 'https://images.unsplash.com/photo-1548907040-4baa42d10919?auto=format&fit=crop&w=1200&h=1200&q=90',
    'cocoa-b' => 'https://images.unsplash.com/photo-1511381939415-e44015466834?auto=format&fit=crop&w=1200&h=1200&q=90',
    'cocoa-c' => 'https://images.unsplash.com/photo-1481391319762-47dff72954d9?auto=format&fit=crop&w=1200&h=1200&q=90',
    'ricefield-a' => 'https://images.unsplash.com/photo-1560493676-04071c5f750f?auto=format&fit=crop&w=1600&h=1000&q=90',
    'ricefield-c' => 'https://images.unsplash.com/photo-1592982537447-7440770cbfc9?auto=format&fit=crop&w=1600&h=1000&q=90',
    'avatar-a' => 'https://images.unsplash.com/photo-1506277886164-e25c7e2f0980?auto=format&fit=crop&w=600&h=600&q=90',
    'avatar-b' => 'https://images.unsplash.com/photo-1531384441138-2736e62e0919?auto=format&fit=crop&w=600&h=600&q=90',
    'avatar-c' => 'https://images.unsplash.com/photo-1522529599102-193c0d76b5b6?auto=format&fit=crop&w=600&h=600&q=90',
    'tractor' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=1600&h=1000&q=90',
    'corn-close' => 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?auto=format&fit=crop&w=1200&h=1200&q=90',
    'yuca' => 'https://images.unsplash.com/photo-1607305387299-a3d9611cd469?auto=format&fit=crop&w=1200&h=1200&q=90',
];

foreach ($map as $name => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'CyraAgroLink/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && is_string($body) && strlen($body) > 5000) {
        file_put_contents("{$dir}/{$name}.jpg", $body);
        echo "OK {$name} ".strlen($body)."\n";
    } else {
        echo "FAIL {$name} {$code}\n";
    }
}
