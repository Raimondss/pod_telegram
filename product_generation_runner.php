<?php

while (true) {
    echo "[" . date('H:i:s') . "] Running command...\n";

    // Call your Laravel command
    exec('php artisan product:generate-products');

    // Wait for 1 second
    usleep(50000);
}
