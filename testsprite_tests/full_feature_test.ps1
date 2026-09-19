# TestSprite-style full feature test against live backend (PRD-driven) v2
$base = 'http://127.0.0.1:8000/api'
$pass = 0; $fail = 0

function Req($method, $path, $token, $body) {
    $headers = @{ Accept = 'application/json' }
    if ($token) { $headers['Authorization'] = "Bearer $token" }
    $params = @{ Uri = "$base$path"; Method = $method; Headers = $headers; ContentType = 'application/json'; UseBasicParsing = $true; TimeoutSec = 15; MaximumRedirection = 0 }
    if ($body) { $params['Body'] = ($body | ConvertTo-Json -Depth 10) }
    try {
        $r = Invoke-WebRequest @params -ErrorAction Stop
        return @{ code = [int]$r.StatusCode; data = ($r.Content | ConvertFrom-Json) }
    } catch {
        $resp = $_.Exception.Response
        $code = 0
        $data = $null
        if ($resp) {
            $code = [int]$resp.StatusCode
            try {
                $sr = New-Object System.IO.StreamReader($resp.GetResponseStream())
                $raw = $sr.ReadToEnd()
                $data = ($raw | ConvertFrom-Json)
            } catch { }
        }
        return @{ code = $code; data = $data }
    }
}

# Multipart POST helper (attendance needs photo upload)
function MultipartPost($path, $token, $fields) {
    try {
        Add-Type -AssemblyName System.Net.Http -ErrorAction SilentlyContinue
        $pngBytes = [byte[]]@(0x89,0x50,0x4E,0x47,0x0D,0x0A,0x1A,0x0A,0x00,0x00,0x00,0x0D,0x49,0x48,0x44,0x52,0x00,0x00,0x00,0x01,0x00,0x00,0x00,0x01,0x08,0x06,0x00,0x00,0x00,0x1F,0x15,0xC4,0x89,0x00,0x00,0x00,0x0D,0x49,0x44,0x41,0x54,0x78,0x9C,0x62,0x00,0x01,0x00,0x00,0x05,0x00,0x01,0x0D,0x0A,0x2D,0xB4,0x00,0x00,0x00,0x00,0x49,0x45,0x4E,0x44,0xAE,0x42,0x60,0x82)
        $mp = New-Object System.Net.Http.MultipartFormDataContent
        foreach ($k in $fields.Keys) {
            $mp.Add((New-Object System.Net.Http.StringContent([string]$fields[$k])), $k)
        }
        $imgContent = New-Object System.Net.Http.ByteArrayContent(,$pngBytes)
        $imgContent.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse('image/png')
        $mp.Add($imgContent, 'photo', 'photo.png')
        $client = New-Object System.Net.Http.HttpClient
        $client.Timeout = [TimeSpan]::FromSeconds(15)
        $client.DefaultRequestHeaders.Add('Accept', 'application/json')
        if ($token) { $client.DefaultRequestHeaders.Add('Authorization', "Bearer $token") }
        $resp = $client.PostAsync("$base$path", $mp).Result
        $raw = $resp.Content.ReadAsStringAsync().Result
        $data = $null
        try { $data = ($raw | ConvertFrom-Json) } catch { }
        return @{ code = [int]$resp.StatusCode; data = $data }
    } catch {
        return @{ code = 0; data = $null }
    }
}

function Check($name, $cond) {
    if ($cond) { $script:pass++; Write-Host "PASS  $name" } else { $script:fail++; Write-Host "FAIL  $name" }
}

# --- Auth ---
$login = Req 'POST' '/login' $null @{ email = 'admin@example.com'; password = 'Rinasasecurevps*5912' }
Check 'login admin 200 + token' ($login.code -eq 200 -and $login.data.access_token)
$admin = $login.data.access_token

$bad = Req 'POST' '/login' $null @{ email = 'admin@example.com'; password = 'wrongpass' }
Check 'login invalid 401' ($bad.code -eq 401)

foreach ($role in @('chef', 'employee', 'driver', 'courier')) {
    $r = Req 'POST' '/login' $null @{ email = "$role@example.com"; password = 'password' }
    Check "login $role 200" ($r.code -eq 200 -and $r.data.access_token)
    Set-Variable -Name "tok_$role" -Value $r.data.access_token
}

$noauth = Req 'GET' '/items' $null $null
Check 'items no token 401' ($noauth.code -eq 401)

$prof = Req 'GET' '/profile' $admin $null
Check 'profile admin' ($prof.code -eq 200 -and $prof.data.role -eq 'admin')

# register + logout (random email so reruns don't collide)
$tmpEmail = "testuser_$(Get-Random)@example.com"
$reg = Req 'POST' '/register' $null @{ name = 'Test User'; email = $tmpEmail; password = 'password123'; password_confirmation = 'password123'; role = 'employee'; phone = '0812345000' }
Check 'register 200/201' ($reg.code -eq 200 -or $reg.code -eq 201)
$tlogin = Req 'POST' '/login' $null @{ email = $tmpEmail; password = 'password123' }
Check 'temp login' ($tlogin.code -eq 200)
$tout = Req 'POST' '/logout' $tlogin.data.access_token $null
Check 'logout 200' ($tout.code -eq 200)

# --- Items ---
$items = Req 'GET' '/items' $admin $null
Check 'GET items' ($items.code -eq 200)
$newItem = Req 'POST' '/items' $admin @{ name = 'Test Bahan TS'; sku = "TS-$(Get-Random)"; unit = 'kg'; stock_system = 50; min_stock = 5; price = 12000 }
Check 'POST item (admin)' ($newItem.code -eq 200 -or $newItem.code -eq 201)
$itemId = $newItem.data.item.id; if (-not $itemId) { $itemId = $newItem.data.id }
$empItem = Req 'POST' '/items' $tok_employee @{ name = 'X'; sku = 'X1'; unit = 'kg'; stock_system = 1; min_stock = 1; price = 1 }
Check 'POST item employee 403' ($empItem.code -eq 403)
$badItem = Req 'POST' '/items' $admin @{ name = 'NoSku' }
Check 'POST item invalid 422' ($badItem.code -eq 422)
if ($itemId) {
    $upd = Req 'PUT' "/items/$itemId" $admin @{ stock_system = 45 }
    Check 'PUT item' ($upd.code -eq 200)
}

# --- Recipes (created before orders so order items can reference a recipe) ---
$rec = Req 'GET' '/recipes' $tok_chef $null
Check 'GET recipes' ($rec.code -eq 200)
$newRec = Req 'POST' '/recipes' $tok_chef @{ name = 'Resep TS'; target_portions = 10 }
Check 'POST recipe' ($newRec.code -eq 200 -or $newRec.code -eq 201)
$recId = $newRec.data.recipe.id; if (-not $recId) { $recId = $newRec.data.id }
if ($recId -and $itemId) {
    $addIng = Req 'POST' "/recipes/$recId/ingredients" $tok_chef @{ item_id = $itemId; quantity_per_serving = 0.5 }
    Check 'POST recipe ingredient' ($addIng.code -eq 200 -or $addIng.code -eq 201)
    $calc = Req 'POST' '/chef/calculate-materials' $tok_chef @{ recipes = @(@{ recipe_id = $recId; portions = 20 }) }
    Check 'POST calculate-materials' ($calc.code -eq 200)
}

# --- Orders (items array must contain recipe_id + portions) ---
$orders = Req 'GET' '/orders' $admin $null
Check 'GET orders' ($orders.code -eq 200)
$orderItems = @()
if ($recId) { $orderItems = @(@{ recipe_id = $recId; portions = 5 }) }
$order = Req 'POST' '/orders' $admin @{ title = 'Nasi Box TS'; customer_name = 'Klien TS'; customer_phone = '0811'; customer_address = 'Jl. Test'; delivery_date = (Get-Date).AddDays(1).ToString('yyyy-MM-dd'); total_revenue = 500000; total_cogs = 200000; items = $orderItems }
Check 'POST order' ($order.code -eq 200 -or $order.code -eq 201)
$orderId = $order.data.order.id; if (-not $orderId) { $orderId = $order.data.id }
if ($orderId) {
    $updO = Req 'PUT' "/orders/$orderId" $admin @{ total_revenue = 600000; total_cogs = 250000 }
    Check 'PUT order (edit laba)' ($updO.code -eq 200)
    $stO = Req 'PUT' "/orders/$orderId/status" $tok_chef @{ status = 'cooking' }
    Check 'PUT order status (chef)' ($stO.code -eq 200)
}

# --- Deliveries ---
$del = Req 'GET' '/deliveries' $admin $null
Check 'GET deliveries' ($del.code -eq 200)
$my = Req 'GET' '/deliveries/my' $tok_driver $null
Check 'GET deliveries/my (driver)' ($my.code -eq 200)

# --- V4: wastes / suppliers / tasks / leaves / users ---
$w = Req 'POST' '/v4/wastes' $tok_employee @{ item_id = $itemId; qty = 1; reason = 'Spoiled' }
Check 'POST waste' ($w.code -eq 200 -or $w.code -eq 201)
$wl = Req 'GET' '/v4/wastes' $tok_employee $null
Check 'GET wastes' ($wl.code -eq 200)

$sup = Req 'POST' '/v4/suppliers' $admin @{ name = 'Supplier TS'; contact = '0811'; address = 'Jl. S' }
Check 'POST supplier' ($sup.code -eq 200 -or $sup.code -eq 201)
$supl = Req 'GET' '/v4/suppliers' $admin $null
Check 'GET suppliers' ($supl.code -eq 200)

$task = Req 'POST' '/v4/tasks' $admin @{ title = 'Task TS'; points = 5; due_date = (Get-Date).AddDays(1).ToString('yyyy-MM-dd') }
Check 'POST task' ($task.code -eq 200 -or $task.code -eq 201)
$taskId = $task.data.task.id; if (-not $taskId) { $taskId = $task.data.id }
if ($taskId) {
    $comp = Req 'POST' "/v4/tasks/$taskId/complete" $tok_employee $null
    Check 'POST task complete' ($comp.code -eq 200)
    $updT = Req 'PUT' "/v4/tasks/$taskId" $admin @{ is_completed = $false }
    Check 'PUT task' ($updT.code -eq 200)
}
$tl = Req 'GET' '/v4/tasks' $tok_employee $null
Check 'GET tasks' ($tl.code -eq 200)

$leave = Req 'POST' '/v4/leaves' $tok_employee @{ type = 'sick'; start_date = (Get-Date).AddDays(2).ToString('yyyy-MM-dd'); end_date = (Get-Date).AddDays(3).ToString('yyyy-MM-dd'); reason = 'Test' }
Check 'POST leave' ($leave.code -eq 200 -or $leave.code -eq 201)
$leaveId = $leave.data.leave.id; if (-not $leaveId) { $leaveId = $leave.data.id }
if ($leaveId) {
    $appr = Req 'PUT' "/v4/leaves/$leaveId/status" $admin @{ status = 'approved' }
    Check 'PUT leave status (admin approve)' ($appr.code -eq 200)
}
$ll = Req 'GET' '/v4/leaves' $tok_employee $null
Check 'GET leaves' ($ll.code -eq 200)

$users = Req 'GET' '/v4/users' $admin $null
Check 'GET users (admin)' ($users.code -eq 200)
$usersEmp = Req 'GET' '/v4/users' $tok_employee $null
Check 'GET users employee 403' ($usersEmp.code -eq 403)

# --- Attendance (multipart + photo required) ---
$att = MultipartPost '/attendance' $tok_employee @{ latitude = '-6.2'; longitude = '106.8' }
Check 'POST attendance clock-in' ($att.code -eq 200 -or $att.code -eq 201)
$attH = Req 'GET' '/attendance' $tok_employee $null
Check 'GET attendance history' ($attH.code -eq 200)
$att2 = MultipartPost '/attendance' $tok_employee @{ latitude = '-6.2'; longitude = '106.8' }
Check 'POST attendance clock-out' ($att2.code -eq 200 -or $att2.code -eq 201)

# --- Inventory opname + report ---
if ($itemId) {
    $op = Req 'POST' '/inventory/opname' $admin @{ item_id = $itemId; physical_qty = 44; notes = 'ts' }
    Check 'POST inventory/opname' ($op.code -eq 200 -or $op.code -eq 201)
}
$rep = Req 'POST' '/inventory/report' $tok_employee @{ item_id = $itemId; qty = 2; report_type = 'waste'; notes = 'ts report' }
Check 'POST inventory/report' ($rep.code -eq 200 -or $rep.code -eq 201)

# --- Excel exports (admin) ---
$exp1 = Req 'GET' '/admin/export-attendances' $admin $null
Check 'GET export-attendances' ($exp1.code -eq 200)
$exp2 = Req 'GET' '/admin/export-opnames' $admin $null
Check 'GET export-opnames' ($exp2.code -eq 200)

# --- Cleanup: delete order + item ---
if ($orderId) {
    $delO = Req 'DELETE' "/orders/$orderId" $admin $null
    Check 'DELETE order' ($delO.code -eq 200)
}
if ($itemId) {
    $delI = Req 'DELETE' "/items/$itemId" $admin $null
    Check 'DELETE item' ($delI.code -eq 200)
}

Write-Host ""
Write-Host ("TOTAL PASS: $pass  FAIL: $fail")
