<?php
/**
 * Autoloader sederhana untuk class-class aplikasi.
 * Memetakan nama class ke file di classes/.
 */

spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
