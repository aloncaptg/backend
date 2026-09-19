try {
    $r = Invoke-WebRequest -Uri 'http://127.0.0.1:8000/api/login' -Method POST -ContentType 'application/json' -Body '{}' -Headers @{Accept='application/json'} -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    Write-Host "BACKEND UP: $($r.StatusCode)"
} catch {
    if ($_.Exception.Response) { Write-Host "BACKEND UP: $([int]$_.Exception.Response.StatusCode)" }
    else { Write-Host 'BACKEND DOWN' }
}
