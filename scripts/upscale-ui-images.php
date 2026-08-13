<?php

declare(strict_types=1);

/**
 * Do NOT upscale UI photos — that creates blur.
 * Use scripts/download-hq-images.php for native high-resolution assets.
 */

fwrite(STDERR, "Upscaling is disabled. Run: php scripts/download-hq-images.php\n");
exit(1);
