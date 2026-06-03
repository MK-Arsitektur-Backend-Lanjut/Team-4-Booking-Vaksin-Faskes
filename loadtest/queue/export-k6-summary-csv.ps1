param(
    [string]$InputPath = ".\loadtest\queue\results\summary.json",
    [string]$OutputPath = ".\loadtest\queue\results\summary.csv"
)

if (-Not (Test-Path $InputPath)) {
    Write-Error "File not found: $InputPath. Pastikan sudah menjalankan K6 dengan argumen --summary-export=..."
    exit
}

# Baca file JSON
$jsonContent = Get-Content -Raw -Path $InputPath
$json = $jsonContent | ConvertFrom-Json

$metrics = $json.metrics

# Buat Object Custom untuk CSV
$data = [PSCustomObject]@{
    TotalRequests      = $metrics.'http_reqs'.values.count
    FailedRequestsRate = $metrics.'http_req_failed'.values.rate
    DurationAvg        = $metrics.'http_req_duration'.values.avg
    DurationMed        = $metrics.'http_req_duration'.values.med
    DurationP90        = $metrics.'http_req_duration'.values.'p(90)'
    DurationP95        = $metrics.'http_req_duration'.values.'p(95)'
    VUsMax             = $metrics.'vus_max'.values.value
    DataSentBytes      = $metrics.'data_sent'.values.count
    DataReceivedBytes  = $metrics.'data_received'.values.count
}

# Export ke CSV
$data | Export-Csv -Path $OutputPath -NoTypeInformation

Write-Host "K6 Summary JSON berhasil dikonversi ke CSV di: $OutputPath"
