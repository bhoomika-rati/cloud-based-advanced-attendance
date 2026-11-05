<?php

function attendanceWebsiteBaseUrl(): string
{
    $baseUrl = rtrim(trim((string)getenv('WEBSITE_BASE_URL')), '/');
    $parts = parse_url($baseUrl);
    if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)
        || $parts === false || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment'])) {
        throw new RuntimeException('Set WEBSITE_BASE_URL to the reachable address of this project.');
    }

    $host = strtolower(trim($parts['host'], '[]'));
    $scheme = strtolower($parts['scheme']);
    $ip = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? ip2long($host) : false;
    $isPrivateIpv4 = $ip !== false && (
        (($ip & 0xff000000) === 0x0a000000)
        || (($ip & 0xfff00000) === 0xac100000)
        || (($ip & 0xffff0000) === 0xc0a80000)
    );

    if ($scheme === 'http' && !$isPrivateIpv4) {
        throw new RuntimeException('HTTP is allowed only for a private IPv4 LAN address; use HTTPS for public websites.');
    }

    if ($scheme === 'https' && ($host === 'localhost' || substr($host, -10) === '.localhost'
        || substr($host, -6) === '.local' || substr($host, -5) === '.test'
        || (filter_var($host, FILTER_VALIDATE_IP)
            && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)))) {
        throw new RuntimeException('WEBSITE_BASE_URL must use a public HTTPS host, not a local or private address.');
    }

    return $baseUrl;
}
