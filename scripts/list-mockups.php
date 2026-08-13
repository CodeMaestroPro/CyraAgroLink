<?php

declare(strict_types=1);

$assets = 'C:/Users/HP/.cursor/projects/c-xampp-htdocs-Cyra-Agro/assets';

foreach (glob($assets.'/*.png') as $file) {
    $info = getimagesize($file);
    echo basename($file)."\t{$info[0]}x{$info[1]}\t{$info['mime']}\n";
}
