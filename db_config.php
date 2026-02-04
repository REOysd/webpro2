<?php
// DB config for mailorder
// Priority: explicit env overrides -> selected profile (local/remote).
// Set DB_ENV=remote to use remote settings.

$dbEnv = getenv('DB_ENV') ?: 'local';

if ($dbEnv === 'remote') {
    $defaultHost = 'mysql326.phy.lolipop.lan';
    $defaultPort = 3306;
    $defaultUser = 'LAA1666836';
    $defaultPass = '49494649Yr';
    $defaultName = 'LAA1666836-webpro';
} else {
    $inDocker = file_exists('/.dockerenv');
    $defaultHost = $inDocker ? 'mysql' : '127.0.0.1';
    // When running PHP on the host and MySQL in docker-compose, the published port is 13306.
    $defaultPort = $inDocker ? 3306 : 13306;
    $defaultUser = 'root';
    $defaultPass = 'rootpassword';
    $defaultName = 'webshop';
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: $defaultHost);
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int)(getenv('DB_PORT') ?: $defaultPort));
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: $defaultUser);
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: $defaultPass);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: $defaultName);
}
