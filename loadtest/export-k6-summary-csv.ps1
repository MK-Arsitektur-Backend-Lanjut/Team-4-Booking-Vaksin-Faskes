param(
    [Parameter(Mandatory = $false)]
    [string]$InputPath = "loadtest/results/summary.json",

    [Parameter(Mandatory = $false)]
    [string]$OutputPath = "loadtest/results/summary.csv"
)

if (-not (Test-Path -Path $InputPath)) {
    throw "Input file not found: $InputPath"
}

$inputAbsolute = (Resolve-Path -Path $InputPath).Path
$outputDirectory = Split-Path -Path $OutputPath -Parent

if (-not [string]::IsNullOrWhiteSpace($outputDirectory) -and -not (Test-Path -Path $outputDirectory)) {
    New-Item -Path $outputDirectory -ItemType Directory -Force | Out-Null
}

$summary = Get-Content -Path $inputAbsolute -Raw | ConvertFrom-Json
$rows = @()

if ($null -eq $summary.metrics) {
    throw "Invalid k6 summary format: property 'metrics' not found."
}

foreach ($metricProperty in $summary.metrics.PSObject.Properties) {
    $metricName = $metricProperty.Name
    $metric = $metricProperty.Value

    $values = $metric.values
    $rows += [PSCustomObject]@{
        metric = $metricName
        type = $metric.type
        contains = $metric.contains
        count = if ($null -ne $values.count) { $values.count } else { $null }
        rate = if ($null -ne $values.rate) { $values.rate } else { $null }
        avg = if ($null -ne $values.avg) { $values.avg } else { $null }
        min = if ($null -ne $values.min) { $values.min } else { $null }
        med = if ($null -ne $values.med) { $values.med } else { $null }
        p90 = if ($null -ne $values.'p(90)') { $values.'p(90)' } else { $null }
        p95 = if ($null -ne $values.'p(95)') { $values.'p(95)' } else { $null }
        max = if ($null -ne $values.max) { $values.max } else { $null }
    }
}

$rows |
    Sort-Object -Property metric |
    Export-Csv -Path $OutputPath -NoTypeInformation -Encoding UTF8

Write-Host "CSV export created: $OutputPath"
