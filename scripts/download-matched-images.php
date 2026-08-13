<?php

declare(strict_types=1);

/**
 * Download product/course images that visually match their filenames.
 */
$batches = [
    dirname(__DIR__).'/public/images/consumer' => [
        // Ofada is a brownish short-grain Nigerian rice.
        'rice-ofada.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Brown_rice.jpg/1280px-Brown_rice.jpg',
        'honey-raw.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/34/Dipper_stick_and_honey_in_a_jar.jpg/1280px-Dipper_stick_and_honey_in_a_jar.jpg',
        'palm-oil.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Epo_%28red_palm_oil%29.jpg/1280px-Epo_%28red_palm_oil%29.jpg',
        'yam-flour.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9b/Weizenmehl_40kg_S%C3%A4cke.jpg/1280px-Weizenmehl_40kg_S%C3%A4cke.jpg',
    ],
    dirname(__DIR__).'/public/images/academy' => [
        'maize-farming.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=1200&h=800&q=85',
        'irrigation.jpg' => 'https://images.unsplash.com/photo-1743742566156-f1745850281a?fm=jpg&fit=crop&w=1200&h=800&q=85',
        'pest-disease.jpg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/Aphids.jpg/1280px-Aphids.jpg',
    ],
];

foreach ($batches as $public => $assets) {
    if (! is_dir($public)) {
        mkdir($public, 0775, true);
    }

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
            fwrite(STDERR, "FAIL {$file} code={$code} type={$type} bytes=".strlen((string) $body)."\n");
            continue;
        }

        file_put_contents($dest, $body);
        echo 'OK '.$file.' ('.strlen($body)." bytes)\n";
    }
}
