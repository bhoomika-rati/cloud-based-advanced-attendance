<?php

function attendanceLoadEnvironment(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (is_file($envFile)) {
        if (!is_readable($envFile)) {
            throw new RuntimeException('The project .env file exists but cannot be read.');
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException('Unable to read the project .env file.');
        }

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (!preg_match('/\A(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\z/', $line, $matches)) {
                throw new RuntimeException('Invalid .env entry on line ' . ($index + 1) . '.');
            }

            $name = $matches[1];
            if (getenv($name) !== false) {
                continue;
            }

            $value = trim($matches[2]);
            if (strlen($value) >= 2
                && (($value[0] === '"' && substr($value, -1) === '"')
                    || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    $loaded = true;
}

attendanceLoadEnvironment();
