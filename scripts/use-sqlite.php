<?php

/*
|--------------------------------------------------------------------------
| Switch the local environment to SQLite
|--------------------------------------------------------------------------
|
| Writes a .env from .env.example with the database settings replaced by
| SQLite, so the API can run on a native PHP install without a database
| server. The Docker (Sail) setup keeps MariaDB, which is what .env.example
| itself holds.
|
| Usage: composer sqlite [-- --force]
|
*/

$root = dirname(__DIR__);
$examplePath = $root.'/.env.example';
$envPath = $root.'/.env';
$databasePath = $root.'/database/database.sqlite';
$force = in_array('--force', $argv, true);

$fail = function (string $message): never {
    fwrite(STDERR, PHP_EOL.'  '.$message.PHP_EOL.PHP_EOL);
    exit(1);
};

if (! extension_loaded('pdo_sqlite')) {
    $fail(
        'The pdo_sqlite PHP extension is missing, SQLite cannot be used without it.'.PHP_EOL
        .'    Debian / Ubuntu: sudo apt install php-sqlite3'.PHP_EOL
        .'    Arch:            sudo pacman -S php-sqlite, then enable extension=pdo_sqlite in php.ini'.PHP_EOL
        .'    macOS (brew):    already bundled, check with php -m | grep sqlite'
    );
}

if (! is_file($examplePath)) {
    $fail('.env.example is missing, there is nothing to copy.');
}

if (is_file($envPath) && ! $force) {
    $fail('.env already exists. Run "composer sqlite -- --force" to overwrite it.');
}

// "php artisan serve" cannot bind port 80 as an unprivileged user, so the
// native setup lives on 8000 and APP_URL has to follow it.
$serveUrl = 'http://localhost:8000';

$sqliteSettings = <<<'ENV'
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
ENV;

// Drop every DB_* line of the example, commented out ones included, and put the
// SQLite settings where the first of them was.
$lines = [];
$replaced = false;

foreach (file($examplePath, FILE_IGNORE_NEW_LINES) as $line) {
    if (preg_match('/^\s*#?\s*APP_URL=/', $line)) {
        $lines[] = 'APP_URL='.$serveUrl;

        continue;
    }

    if (preg_match('/^\s*#?\s*DB_[A-Z_]+=/', $line)) {
        if (! $replaced) {
            $lines[] = $sqliteSettings;
            $replaced = true;
        }

        continue;
    }

    $lines[] = $line;
}

if (! $replaced) {
    $lines[] = '';
    $lines[] = $sqliteSettings;
}

if (file_put_contents($envPath, implode(PHP_EOL, $lines).PHP_EOL) === false) {
    $fail('Could not write .env');
}

echo '  .env written from .env.example, database set to SQLite'.PHP_EOL;
echo '  APP_URL set to '.$serveUrl.PHP_EOL;

if (! is_file($databasePath) && ! touch($databasePath)) {
    $fail('Could not create database/database.sqlite');
}

echo '  database/database.sqlite is ready'.PHP_EOL;
echo PHP_EOL.'  Next: php artisan key:generate && php artisan migrate --seed'.PHP_EOL.PHP_EOL;
