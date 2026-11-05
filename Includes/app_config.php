<?php

require_once __DIR__ . '/env.php';

function attendanceAppEnvironment(): string
{
    $environment = strtolower(trim((string)(getenv('APP_ENV') ?: 'production')));
    if (!in_array($environment, ['development', 'staging', 'production'], true)) {
        throw new RuntimeException('APP_ENV must be development, staging, or production.');
    }

    return $environment;
}

function attendanceWebsiteBaseUrl(): string
{
    $environment = attendanceAppEnvironment();
    $baseUrlVariable = $environment === 'development'
        ? 'DEVELOPMENT_WEBSITE_BASE_URL'
        : ($environment === 'staging' ? 'STAGING_WEBSITE_BASE_URL' : 'WEBSITE_BASE_URL');
    $baseUrl = rtrim(trim((string)getenv($baseUrlVariable)), '/');
    $parts = parse_url($baseUrl);
    if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)
        || $parts === false || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment'])) {
        throw new RuntimeException('Set WEBSITE_BASE_URL to the reachable address of this project.');
    }
    $host = strtolower(trim($parts['host'], '[]'));
    $scheme = strtolower($parts['scheme']);
    $scheme = strtolower($parts['scheme']);
    $ip = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? ip2long($host) : false;
    $isPrivateIpv4 = $ip !== false && (
        (($ip & 0xff000000) === 0x0a000000)
        || (($ip & 0xfff00000) === 0xac100000)
        || (($ip & 0xffff0000) === 0xc0a80000)
    );
    $isLoopbackIpv4 = $ip !== false && (($ip & 0xff000000) === 0x7f000000);
    $isLoopbackIpv6 = $host === '::1';
    $isLocalHostname = $host === 'localhost' || substr($host, -10) === '.localhost'
        || substr($host, -6) === '.local' || substr($host, -5) === '.test';
    $isNonPublicIp = filter_var($host, FILTER_VALIDATE_IP)
        && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

    if ($environment === 'development') {
        if ($scheme === 'http' && !$isLocalHostname && !$isPrivateIpv4 && !$isLoopbackIpv4 && !$isLoopbackIpv6) {
            throw new RuntimeException('Development HTTP is allowed only for localhost, loopback, or a private LAN address.');
        }
    } elseif ($scheme !== 'https') {
        throw new RuntimeException('Staging and production websites must use HTTPS.');
    }

    if ($environment !== 'development' && ($isLocalHostname || $isNonPublicIp)) {
        throw new RuntimeException('WEBSITE_BASE_URL must use a public HTTPS host, not a local or private address.');
    }

    return $baseUrl;
}

function attendancePublicWebsiteBaseUrl(): string
{
    $baseUrl = attendanceWebsiteBaseUrl();
    $parts = parse_url($baseUrl);
    $host = strtolower(trim((string)($parts['host'] ?? ''), '[]'));
    $isLocalHost = $host === 'localhost' || substr($host, -10) === '.localhost'
        || substr($host, -6) === '.local' || substr($host, -5) === '.test';

    if (strtolower((string)($parts['scheme'] ?? '')) !== 'https'
        || $isLocalHost || filter_var($host, FILTER_VALIDATE_IP)) {
        throw new RuntimeException(
            'QR attendance requires a public HTTPS hostname. Start the Cloudflare Tunnel and configure its URL.'
        );
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is required to verify that the public attendance tunnel is available.');
    }

    $curl = curl_init($baseUrl . '/');
    if ($curl === false) {
        throw new RuntimeException('Unable to verify the configured public attendance URL.');
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
    ]);
    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false || $status < 200 || $status >= 400) {
        error_log('Public attendance URL verification failed: ' . $curlError . ' (HTTP ' . $status . ')');
        throw new RuntimeException(
            'The configured public attendance URL is unavailable. Restart the Cloudflare Tunnel and refresh this page before generating a QR code.'
        );
    }

    return $baseUrl;
}
