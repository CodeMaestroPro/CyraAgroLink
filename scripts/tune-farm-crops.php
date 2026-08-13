<?php

declare(strict_types=1);

$assets = 'C:/Users/HP/.cursor/projects/c-xampp-htdocs-Cyra-Agro/assets';
$public = dirname(__DIR__).'/public/images';
$dashPath = $assets.'/c__Users_HP_AppData_Roaming_Cursor_User_workspaceStorage_6bf947be7c21fbf4decbebb67b0fdf9d_images_1783009484802-33bc692b-b3ef-4ff2-bb2b-69820b4468d3.png';

$src = imagecreatefromjpeg($dashPath);
$w = imagesx($src);
$h = imagesy($src);
echo "Dashboard {$w}x{$h}\n";

function saveCrop($src, int $x, int $y, int $cw, int $ch, string $dest): void
{
    $crop = imagecrop($src, ['x' => $x, 'y' => $y, 'width' => $cw, 'height' => $ch]);
    imagejpeg($crop, $dest, 92);
    imagedestroy($crop);
    echo "Wrote {$dest} ({$cw}x{$ch})\n";
}

$dir = $public.'/_debug';
if (! is_dir($dir)) {
    mkdir($dir, 0775, true);
}

// Grid of candidate farm-image regions for visual tuning
$candidates = [
    'gv-a' => [155, 185, 48, 90],
    'gv-b' => [148, 178, 55, 100],
    'gv-c' => [160, 190, 42, 85],
    'sr-a' => [300, 185, 48, 90],
    'sr-b' => [292, 178, 55, 100],
    'sr-c' => [305, 190, 42, 85],
];

foreach ($candidates as $name => [$x, $y, $cw, $ch]) {
    saveCrop($src, $x, $y, $cw, $ch, "{$dir}/{$name}.jpg");
}

imagedestroy($src);
