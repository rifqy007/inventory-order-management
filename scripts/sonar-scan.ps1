param(
    [string]$ServerUrl,
    [string]$ProjectKey
)

$ErrorActionPreference = 'Stop'

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
    }
    else {
        'http://localhost:9000'
    }
}

if (-not $ProjectKey) {
    $ProjectKey = if ($localSonarSettings['SONAR_PROJECT_KEY']) {
        $localSonarSettings['SONAR_PROJECT_KEY']
    }
    else {
        'Inventory-Order-Management'
    }
}

$ServerUrl = $ServerUrl.TrimEnd('/')

Write-Host ''
Write-Host '=== SonarQube Analysis ===' -ForegroundColor Cyan
Write-Host "Server    : $ServerUrl"
Write-Host "Project   : $ProjectKey"
Write-Host "ProjectDir: $projectRoot"
Write-Host ''

# ---------------------------------------------------------------------------
# 1. Generate PHPUnit coverage
# ---------------------------------------------------------------------------

$hasXdebug = $false

if (-not $docker) {
    $null = & php -r "exit(extension_loaded('xdebug') ? 0 : 1)"
    $hasXdebug = $LASTEXITCODE -eq 0
}

if ($docker) {
    Push-Location $projectRoot

    try {
        Write-Host 'Menjalankan PHPUnit coverage melalui Docker...' -ForegroundColor Cyan

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
        Write-Host 'Menjalankan PHPUnit coverage melalui PHP CLI...' -ForegroundColor Cyan

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

# ---------------------------------------------------------------------------
# 2. Read SonarQube token
# ---------------------------------------------------------------------------

$secureToken = Read-Host 'Masukkan token SonarQube' -AsSecureString
$tokenPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureToken)

try {
    $env:SONAR_TOKEN = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($tokenPointer)

    # -----------------------------------------------------------------------
    # 3. Run SonarScanner
    # -----------------------------------------------------------------------

    if ($scanner) {
        Write-Host ''
        Write-Host 'Menjalankan SonarScanner CLI lokal...' -ForegroundColor Cyan

        $env:SONAR_HOST_URL = $ServerUrl

        Push-Location $projectRoot

        try {
            & $scanner.Source `
                "-Dsonar.projectKey=$ProjectKey" `
                '-Dsonar.qualitygate.wait=true'
        }
        finally {
            Pop-Location
        }
    }
    else {
        Write-Host ''
        Write-Host 'Menjalankan SonarScanner melalui Docker...' -ForegroundColor Cyan

        $containerServerUrl = $ServerUrl `
            -replace '://localhost(?=:\d+|$)', '://host.docker.internal' `
            -replace '://127\.0\.0\.1(?=:\d+|$)', '://host.docker.internal'

        $env:SONAR_HOST_URL = $containerServerUrl

        & $docker.Source run --rm `
            --entrypoint /bin/sh `
            -e SONAR_HOST_URL `
            -e SONAR_TOKEN `
            -e GIT_CONFIG_COUNT=1 `
            -e GIT_CONFIG_KEY_0=safe.directory `
            -e GIT_CONFIG_VALUE_0=/usr/src `
            -v "${projectRoot}:/usr/src" `
            -w /usr/src `
            sonarsource/sonar-scanner-cli:12.1.0.3233_8.0.1 `
            /usr/src/scripts/sonar-scan-container.sh `
            "-Dsonar.projectKey=$ProjectKey" `
            '-Dsonar.qualitygate.wait=true'
    }

    $scannerExitCode = $LASTEXITCODE

    # -----------------------------------------------------------------------
    # 4. Quality Gate failed
    # -----------------------------------------------------------------------

    if ($scannerExitCode -ne 0) {

        if ($scannerExitCode -eq 3) {

            Write-Host ''
            Write-Host 'Quality Gate gagal. Mengambil detail dari SonarQube...' -ForegroundColor Yellow

            try {

                # -------------------------------------------------------------------
                # 4a. Get Quality Gate status
                # -------------------------------------------------------------------

                $statusUri = '{0}/api/qualitygates/project_status?projectKey={1}' -f `
                    $ServerUrl,
                    [uri]::EscapeDataString($ProjectKey)

                $gateResult = Invoke-RestMethod `
                    -Method Get `
                    -Uri $statusUri `
                    -Headers @{
                        Authorization = "Bearer $env:SONAR_TOKEN"
                    }

                $failedConditions = @(
                    $gateResult.projectStatus.conditions |
                    Where-Object {
                        $_.status -eq 'ERROR'
                    }
                )

                Write-Host ''
                Write-Host '=== Quality Gate ===' -ForegroundColor Yellow

                if ($failedConditions.Count -gt 0) {

                    foreach ($condition in $failedConditions) {

                        Write-Host (
                            '- {0}: aktual {1}, ambang {2} {3}' -f `
                            $condition.metricKey,
                            $condition.actualValue,
                            $condition.comparator,
                            $condition.errorThreshold
                        ) -ForegroundColor Red
                    }
                }
                else {
                    Write-Host 'Tidak ditemukan kondisi ERROR pada response Quality Gate.' -ForegroundColor Yellow
                }

                # -------------------------------------------------------------------
                # 4b. Get project coverage summary
                #
                # Endpoint ini sudah terbukti berhasil dengan token yang sama:
                # /api/measures/component
                # -------------------------------------------------------------------

                $encodedProjectKey = [uri]::EscapeDataString($ProjectKey)

                $projectMeasureUri = '{0}/api/measures/component?component={1}&metricKeys=coverage,new_coverage,new_lines_to_cover,new_uncovered_lines' -f `
                    $ServerUrl,
                    $encodedProjectKey

                $projectMeasureResult = Invoke-RestMethod `
                    -Method Get `
                    -Uri $projectMeasureUri `
                    -Headers @{
                        Authorization = "Bearer $env:SONAR_TOKEN"
                    }

                Write-Host ''
                Write-Host '=== Coverage Project ===' -ForegroundColor Cyan

                $projectMeasures = @{}

                foreach ($measure in $projectMeasureResult.component.measures) {

                    if ($measure.period) {
                        $projectMeasures[$measure.metric] = $measure.period.value
                    }
                    elseif ($null -ne $measure.value) {
                        $projectMeasures[$measure.metric] = $measure.value
                    }
                }

                if ($projectMeasures.ContainsKey('coverage')) {
                    Write-Host (
                        'Coverage keseluruhan : {0}%' -f
                        $projectMeasures['coverage']
                    )
                }

                if ($projectMeasures.ContainsKey('new_coverage')) {
                    Write-Host (
                        'New coverage         : {0}%' -f
                        $projectMeasures['new_coverage']
                    )
                }

                if ($projectMeasures.ContainsKey('new_lines_to_cover')) {
                    Write-Host (
                        'New lines to cover   : {0}' -f
                        $projectMeasures['new_lines_to_cover']
                    )
                }

                if ($projectMeasures.ContainsKey('new_uncovered_lines')) {
                    Write-Host (
                        'New uncovered lines  : {0}' -f
                        $projectMeasures['new_uncovered_lines']
                    )
                }

                # -------------------------------------------------------------------
                # 4c. Get project files
                #
                # component_tree menghasilkan 403 pada instance ini.
                # Gunakan components/tree.
                # -------------------------------------------------------------------

                Write-Host ''
                Write-Host 'Mengambil daftar file dari SonarQube...' -ForegroundColor Cyan

                $allComponents = @()
                $page = 1
                $pageSize = 500

                do {

                    $componentsUri = '{0}/api/components/tree?component={1}&qualifiers=FIL&ps={2}&p={3}' -f `
                        $ServerUrl,
                        $encodedProjectKey,
                        $pageSize,
                        $page

                    $componentsResult = Invoke-RestMethod `
                        -Method Get `
                        -Uri $componentsUri `
                        -Headers @{
                            Authorization = "Bearer $env:SONAR_TOKEN"
                        }

                    if ($componentsResult.components) {
                        $allComponents += @(
                            $componentsResult.components
                        )
                    }

                    $totalComponents = [int]$componentsResult.paging.total

                    $page++

                }
                while (
                    $allComponents.Count -lt $totalComponents -and
                    $componentsResult.components.Count -gt 0
                )

                Write-Host (
                    'Jumlah file SonarQube: {0}' -f
                    $allComponents.Count
                )

                # -------------------------------------------------------------------
                # 4d. Get coverage per file
                # -------------------------------------------------------------------

                $coverageRows = foreach ($component in $allComponents) {

                    try {

                        $encodedComponent = [uri]::EscapeDataString($component.key)

                        $measureUri = '{0}/api/measures/component?component={1}&metricKeys=new_coverage,new_lines_to_cover,new_uncovered_lines' -f `
                            $ServerUrl,
                            $encodedComponent

                        $measureResult = Invoke-RestMethod `
                            -Method Get `
                            -Uri $measureUri `
                            -Headers @{
                                Authorization = "Bearer $env:SONAR_TOKEN"
                            }

                        $measureValues = @{}

                        foreach ($measure in $measureResult.component.measures) {

                            if ($measure.period) {
                                $measureValues[$measure.metric] = $measure.period.value
                            }
                            elseif ($null -ne $measure.value) {
                                $measureValues[$measure.metric] = $measure.value
                            }
                        }

                        [pscustomobject]@{
                            File               = $component.path
                            NewCoveragePercent = $measureValues['new_coverage']
                            NewLinesToCover    = $measureValues['new_lines_to_cover']
                            NewUncoveredLines  = $measureValues['new_uncovered_lines']
                        }
                    }
                    catch {

                        Write-Warning (
                            "Coverage untuk file '{0}' gagal diambil: {1}" -f `
                            $component.path,
                            $_.Exception.Message
                        )
                    }
                }

                # -------------------------------------------------------------------
                # 4e. Export CSV
                # -------------------------------------------------------------------

                $coverageReportPath = Join-Path `
                    $projectRoot `
                    'sonarqube-new-coverage.csv'

                $validCoverageRows = @(
                    $coverageRows |
                        Where-Object {
                            $null -ne $_.NewLinesToCover -and
                            $_.NewLinesToCover -ne ''
                        }
                )

                if ($validCoverageRows.Count -gt 0) {

                    $validCoverageRows |
                        Sort-Object {
                            if ($null -eq $_.NewUncoveredLines -or $_.NewUncoveredLines -eq '') {
                                0
                            }
                            else {
                                [double]$_.NewUncoveredLines
                            }
                        } -Descending |
                        Export-Csv `
                            -LiteralPath $coverageReportPath `
                            -NoTypeInformation `
                            -Encoding UTF8
                }
                else {

                    '"File","NewCoveragePercent","NewLinesToCover","NewUncoveredLines"' |
                        Set-Content `
                            -LiteralPath $coverageReportPath `
                            -Encoding UTF8
                }

                Write-Host ''
                Write-Host (
                    "Rincian coverage per file disimpan ke: {0}" -f
                    $coverageReportPath
                ) -ForegroundColor Yellow

                # -------------------------------------------------------------------
                # 4f. Print worst files
                # -------------------------------------------------------------------

                if ($validCoverageRows.Count -gt 0) {

                    Write-Host ''
                    Write-Host '=== File dengan uncovered lines terbanyak ===' -ForegroundColor Yellow

                    $validCoverageRows |
                        Sort-Object {
                            if ($null -eq $_.NewUncoveredLines -or $_.NewUncoveredLines -eq '') {
                                0
                            }
                            else {
                                [double]$_.NewUncoveredLines
                            }
                        } -Descending |
                        Select-Object -First 10 |
                        Format-Table `
                            File,
                            NewCoveragePercent,
                            NewLinesToCover,
                            NewUncoveredLines `
                            -AutoSize
                }
            }
            catch {

                Write-Warning (
                    "Detail Quality Gate / coverage gagal diambil dari SonarQube: {0}" -f
                    $_.Exception.Message
                )
            }
        }

        throw "Analisis SonarQube gagal dengan exit code $scannerExitCode. Periksa log di atas."
    }

    # -----------------------------------------------------------------------
    # 5. Success
    # -----------------------------------------------------------------------

    Write-Host ''
    Write-Host '=============================================' -ForegroundColor Green
    Write-Host 'SonarQube analysis berhasil.' -ForegroundColor Green
    Write-Host 'Quality Gate: PASSED' -ForegroundColor Green
    Write-Host '=============================================' -ForegroundColor Green
}
finally {

    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($tokenPointer)

    Remove-Item Env:SONAR_TOKEN -ErrorAction SilentlyContinue
    Remove-Item Env:SONAR_HOST_URL -ErrorAction SilentlyContinue
}