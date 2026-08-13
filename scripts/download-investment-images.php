<?php

declare(strict_types=1);

/**
 * Download farm-type-specific investment gallery images (3 per enterprise).
 */

$root = dirname(__DIR__).'/public/images/investments';

if (! is_dir($root)) {
    mkdir($root, 0775, true);
}

$assets = [
    // Maize / corn
    'maize-1.jpg' => 'https://images.unsplash.com/photo-1695453200514-d9ee3c003772?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'maize-2.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'maize-3.jpg' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?fm=jpg&fit=crop&w=1400&h=900&q=85',
    // Cassava / root crops (tropical farm fields)
    'cassava-1.jpg' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'cassava-2.jpg' => 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'cassava-3.jpg' => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?fm=jpg&fit=crop&w=1400&h=900&q=85',
    // Rice paddies
    'rice-1.jpg' => 'https://images.unsplash.com/photo-1762098069270-66f50cdb1a84?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'rice-2.jpg' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'rice-3.jpg' => 'https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?fm=jpg&fit=crop&w=1400&h=900&q=85',
    // Layers / egg poultry
    'layers-1.jpg' => 'https://images.unsplash.com/photo-1538451825199-8605af521e85?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'layers-2.jpg' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'layers-3.jpg' => 'https://images.unsplash.com/photo-1569288052389-dac9b01c9c05?fm=jpg&fit=crop&w=1400&h=900&q=85',
    // Broilers / meat chickens
    'broilers-1.jpg' => 'https://images.unsplash.com/photo-1516467508483-a7212febe31a?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'broilers-2.jpg' => 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'broilers-3.jpg' => 'https://images.unsplash.com/photo-1612170153139-6f881ff067e0?fm=jpg&fit=crop&w=1400&h=900&q=85',
    // Fish farming / aquaculture
    'fish-1.jpg' => 'https://images.unsplash.com/photo-1758656911249-c0f1af7dcaec?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'fish-2.jpg' => 'https://images.unsplash.com/photo-1751568928453-bf6fee52dead?fm=jpg&fit=crop&w=1400&h=900&q=85',
    'fish-3.jpg' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?fm=jpg&fit=crop&w=1400&h=900&q=85',
];

foreach ($assets as $file => $url) {
    $dest = $root.'/'.$file;

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

    if ($body !== false && $code < 400 && strlen($body) >= 4000 && str_contains($type, 'image')) {
        file_put_contents($dest, $body);
        echo 'OK '.$file.' ('.strlen($body)." bytes)\n";
        continue;
    }

    fwrite(STDERR, "FAIL {$file} code={$code} type={$type} size=".($body === false ? 0 : strlen($body))."\n");
}
