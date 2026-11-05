param(
    [string]$CloudflaredPath
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$environmentFile = Join-Path $projectRoot '.env'
$projectFolder = Split-Path -Leaf $projectRoot

if (-not (Test-Path -LiteralPath $environmentFile -PathType Leaf)) {
    throw 'The project .env file is missing. Create it from .env.example before starting the tunnel.'
}

if ([string]::IsNullOrWhiteSpace($CloudflaredPath)) {
    $cloudflaredCommand = Get-Command 'cloudflared.exe' -ErrorAction SilentlyContinue
    if (-not $cloudflaredCommand) {
        $cloudflaredCommand = Get-Command 'cloudflared' -ErrorAction SilentlyContinue
    }

    if ($cloudflaredCommand) {
        $CloudflaredPath = $cloudflaredCommand.Source
    } else {
        throw 'cloudflared was not found. Install it or pass -CloudflaredPath with the full executable path.'
    }
}

if (-not (Test-Path -LiteralPath $CloudflaredPath -PathType Leaf)) {
    throw "cloudflared executable not found: $CloudflaredPath"
}

$baseUrlPattern = '(?m)^DEVELOPMENT_WEBSITE_BASE_URL=.*$'
$envContent = [System.IO.File]::ReadAllText($environmentFile)
if ([System.Text.RegularExpressions.Regex]::Matches($envContent, $baseUrlPattern).Count -ne 1) {
    throw 'The .env file must contain exactly one DEVELOPMENT_WEBSITE_BASE_URL entry.'
}

Write-Host 'Starting a temporary Cloudflare Quick Tunnel to the local XAMPP Apache server.'
Write-Host 'Keep this PowerShell window open while students use the QR code.'
& $CloudflaredPath tunnel --url 'http://localhost:80' 2>&1 | ForEach-Object {
    $logLine = $_.ToString()
    Write-Host $logLine

    if ($logLine -match 'https://[a-z0-9-]+\.trycloudflare\.com') {
        $tunnelUrl = $Matches[0]
        $publicBaseUrl = $tunnelUrl + '/' + $projectFolder
        $newContent = [System.Text.RegularExpressions.Regex]::Replace(
            $envContent,
            $baseUrlPattern,
            [System.Text.RegularExpressions.MatchEvaluator]{
                param($match)
                'DEVELOPMENT_WEBSITE_BASE_URL=' + $publicBaseUrl
            }
        )
        [System.IO.File]::WriteAllText(
            $environmentFile,
            $newContent,
            (New-Object System.Text.UTF8Encoding($false))
        )

        Write-Host "Updated the local QR base URL to $publicBaseUrl"
        $projectUrl = $publicBaseUrl + '/'
        $reachable = $false
        for ($attempt = 0; $attempt -lt 10; $attempt++) {
            try {
                $response = Invoke-WebRequest -Uri $projectUrl -UseBasicParsing -TimeoutSec 8
                if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400) {
                    $reachable = $true
                    break
                }
            } catch {
                Start-Sleep -Seconds 2
            }
        }

        if ($reachable) {
            Write-Host 'Public HTTPS project check passed. Refresh the faculty QR page to generate a QR for this tunnel.'
        } else {
            Write-Warning 'The tunnel URL is configured, but the public project check did not succeed yet. QR generation will remain blocked until the URL responds.'
        }
    }
}
