<?php

while (true) {
    echo "[" . date('H:i:s') . "] Running command...\n";

    // Call your Laravel command
    exec('php artisan telegram:process-command 0');

    // Wait for 1 second
    usleep(50000);
}
