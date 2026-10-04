param(
    [string]$ServerUrl,
    [string]$ProjectKey
)

$scanner = Get-Command 'sonar-scanner.bat' -ErrorAction SilentlyContinue
if (-not $scanner) {
    $scanner = Get-Command 'sonar-scanner' -ErrorAction SilentlyContinue
}
$docker = Get-Command 'docker' -ErrorAction SilentlyContinue

if (-not $scanner -and -not $docker) {
    throw 'SonarScanner CLI dan Docker tidak ditemukan. Instal SonarScanner CLI atau Docker Desktop.'
}

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$localEnvPath = Join-Path $projectRoot '.env'
$localSonarSettings = @{}
if (Test-Path -LiteralPath $localEnvPath) {
    foreach ($line in Get-Content -LiteralPath $localEnvPath) {
        if ($line -match '^\s*(SONAR_HOST_URL|SONAR_PROJECT_KEY)\s*=\s*(.*?)\s*$') {
            $localSonarSettings[$Matches[1]] = $Matches[2].Trim('"', "'")
        }
    }
}

if (-not $ServerUrl) {
    $ServerUrl = if ($localSonarSettings['SONAR_HOST_URL']) {
        $localSonarSettings['SONAR_HOST_URL']
    } else {
        'http://localhost:9000'
    }
}
if (-not $ProjectKey) {
    $ProjectKey = if ($localSonarSettings['SONAR_PROJECT_KEY']) {
        $localSonarSettings['SONAR_PROJECT_KEY']
    } else {
        'inventory-order-management'
    }
}

$hasXdebug = $false
if (-not $docker) {
    $null = & php -r "exit(extension_loaded('xdebug') ? 0 : 1)"
    $hasXdebug = $LASTEXITCODE -eq 0
}
if ($docker) {
    Push-Location $projectRoot
    try {
        & $docker.Source compose run --build --rm sonar-test
        if ($LASTEXITCODE -ne 0) {
            throw "Pembuatan coverage PHPUnit gagal dengan exit code $LASTEXITCODE. Analisis SonarQube dibatalkan."
        }
    }
    finally {
        Pop-Location
    }
}
elseif ($hasXdebug) {
    $env:XDEBUG_MODE = 'coverage'
    Push-Location $projectRoot
    try {
        & composer test -- --coverage-clover=coverage.xml
        if ($LASTEXITCODE -ne 0) {
            throw "Pembuatan coverage PHPUnit gagal dengan exit code $LASTEXITCODE. Analisis SonarQube dibatalkan."
        }
    }
    finally {
        Pop-Location
        Remove-Item Env:XDEBUG_MODE -ErrorAction SilentlyContinue
    }
}
else {
    throw 'Coverage membutuhkan Docker atau Xdebug aktif di PHP CLI.'
}

$secureToken = Read-Host 'Masukkan token SonarQube' -AsSecureString
$tokenPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureToken)

try {
    $env:SONAR_TOKEN = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($tokenPointer)

    if ($scanner) {
        $env:SONAR_HOST_URL = $ServerUrl.TrimEnd('/')
        Push-Location $projectRoot
        try {
            & $scanner.Source "-Dsonar.projectKey=$ProjectKey" '-Dsonar.qualitygate.wait=true'
        }
        finally {
            Pop-Location
        }
    }
    else {
        $containerServerUrl = $ServerUrl.TrimEnd('/') `
            -replace '://localhost(?=:\d+|$)', '://host.docker.internal' `
            -replace '://127\.0\.0\.1(?=:\d+|$)', '://host.docker.internal'
        $env:SONAR_HOST_URL = $containerServerUrl

        & $docker.Source run --rm `
            -e SONAR_HOST_URL `
            -e SONAR_TOKEN `
            -v "${projectRoot}:/usr/src" `
            -w /usr/src `
            sonarsource/sonar-scanner-cli:12.1.0.3233_8.0.1 `
            "-Dsonar.projectKey=$ProjectKey" `
            '-Dsonar.qualitygate.wait=true'
    }

    if ($LASTEXITCODE -ne 0) {
        if ($LASTEXITCODE -eq 3) {
            try {
                $statusUri = '{0}/api/qualitygates/project_status?projectKey={1}' -f `
                    $ServerUrl.TrimEnd('/'),
                    [uri]::EscapeDataString($ProjectKey)
                $gateResult = Invoke-RestMethod -Method Get -Uri $statusUri -Headers @{
                    Authorization = "Bearer $env:SONAR_TOKEN"
                }
                $failedConditions = @(
                    $gateResult.projectStatus.conditions |
                    Where-Object { $_.status -eq 'ERROR' }
                )

                if ($failedConditions.Count -gt 0) {
                    Write-Host 'Quality Gate gagal pada kondisi berikut:' -ForegroundColor Red
                    foreach ($condition in $failedConditions) {
                        Write-Host ('- {0}: aktual {1}, ambang {2} {3}' -f `
                            $condition.metricKey,
                            $condition.actualValue,
                            $condition.comparator,
                            $condition.errorThreshold)
                    }
                }
            }
            catch {
                Write-Warning 'Detail Quality Gate tidak dapat diambil otomatis. Buka tautan dashboard pada log SonarQube dan pilih See details.'
            }
        }

        throw "Analisis SonarQube gagal dengan exit code $LASTEXITCODE. Periksa log di atas."
    }
}
finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($tokenPointer)
    Remove-Item Env:SONAR_TOKEN -ErrorAction SilentlyContinue
    Remove-Item Env:SONAR_HOST_URL -ErrorAction SilentlyContinue
}
