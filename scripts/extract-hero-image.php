<?php

declare(strict_types=1);

$source = 'C:/Users/HP/.cursor/projects/c-xampp-htdocs-Cyra-Agro/assets/c__Users_HP_AppData_Roaming_Cursor_User_workspaceStorage_6bf947be7c21fbf4decbebb67b0fdf9d_images_image-f9ac6bcc-f00f-4d57-8534-c6fd4209fb9c.png';
$destDir = dirname(__DIR__) . '/public/images';
$destJpg = $destDir . '/hero-farmer.jpg';
$destPng = $destDir . '/hero-farmer.png';
$fullCopy = $destDir . '/ui-landing-reference.jpg';

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension not loaded\n");
    exit(1);
}

if (! is_dir($destDir)) {
    mkdir($destDir, 0775, true);
}

$info = getimagesize($source);
if ($info === false) {
    fwrite(STDERR, "Unable to read source image\n");
    exit(1);
}

[$width, $height] = $info;
echo "Source: {$width}x{$height}\n";

$src = imagecreatefromjpeg($source);
if ($src === false) {
    fwrite(STDERR, "Failed to open image\n");
    exit(1);
}

// Keep a full reference copy of the provided UI.
copy($source, $fullCopy);

/*
| Crop the hero photograph from the mockup.
| The farmer image sits on the right side of the hero band
| (below the navbar, above the stats card).
|
| Approximate proportions for a typical desktop mockup:
| - Navbar ~7% of height
| - Hero photo band ~38% of height starting after navbar
| - Horizontal: right ~48% of width
*/
$cropX = (int) round($width * 0.48);
$cropY = (int) round($height * 0.07);
$cropW = (int) round($width * 0.50);
$cropH = (int) round($height * 0.38);

// Clamp bounds.
$cropW = min($cropW, $width - $cropX);
$cropH = min($cropH, $height - $cropY);

echo "Crop: x={$cropX} y={$cropY} w={$cropW} h={$cropH}\n";

$crop = imagecrop($src, [
    'x' => $cropX,
    'y' => $cropY,
    'width' => $cropW,
    'height' => $cropH,
]);

if ($crop === false) {
    fwrite(STDERR, "Crop failed\n");
    imagedestroy($src);
    exit(1);
}

imagepng($crop, $destPng, 6);
imagejpeg($crop, $destJpg, 90);

imagedestroy($crop);
imagedestroy($src);

echo "Wrote {$destPng}\n";
echo "Wrote {$destJpg}\n";
echo "Wrote {$fullCopy}\n";
