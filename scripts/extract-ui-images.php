<?php

declare(strict_types=1);

/**
 * @deprecated Mockup crops are low-resolution and become blurry when enlarged.
 * Use scripts/download-hq-images.php for native high-resolution photography.
 */

fwrite(STDERR, "This script is deprecated because mockup crops are blurry.\n");
fwrite(STDERR, "Run instead: php scripts/download-hq-images.php\n");
exit(1);
