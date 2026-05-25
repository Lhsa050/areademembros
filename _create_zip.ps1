$src = 'e:\Downloads\membros-metodogo-main\membros-metodogo-main'
$tmp = Join-Path $src '_update_temp'
if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }

New-Item -Path (Join-Path $tmp 'app\Models') -ItemType Directory -Force | Out-Null
New-Item -Path (Join-Path $tmp 'app\Controllers') -ItemType Directory -Force | Out-Null
New-Item -Path (Join-Path $tmp 'views\admin\funnels\products') -ItemType Directory -Force | Out-Null
New-Item -Path (Join-Path $tmp 'views\admin\products') -ItemType Directory -Force | Out-Null

Copy-Item (Join-Path $src 'app\Models\Funnel.php') (Join-Path $tmp 'app\Models\Funnel.php')
Copy-Item (Join-Path $src 'app\Models\Product.php') (Join-Path $tmp 'app\Models\Product.php')
Copy-Item (Join-Path $src 'app\Controllers\WebhookController.php') (Join-Path $tmp 'app\Controllers\WebhookController.php')
Copy-Item (Join-Path $src 'app\Controllers\FunnelController.php') (Join-Path $tmp 'app\Controllers\FunnelController.php')
Copy-Item (Join-Path $src 'app\Controllers\ProductController.php') (Join-Path $tmp 'app\Controllers\ProductController.php')
Copy-Item (Join-Path $src 'views\admin\funnels\show.php') (Join-Path $tmp 'views\admin\funnels\show.php')
Copy-Item (Join-Path $src 'views\admin\funnels\products\create.php') (Join-Path $tmp 'views\admin\funnels\products\create.php')
Copy-Item (Join-Path $src 'views\admin\funnels\products\edit.php') (Join-Path $tmp 'views\admin\funnels\products\edit.php')
Copy-Item (Join-Path $src 'views\admin\funnels\products\index.php') (Join-Path $tmp 'views\admin\funnels\products\index.php')
Copy-Item (Join-Path $src 'views\admin\products\create.php') (Join-Path $tmp 'views\admin\products\create.php')

$zipPath = Join-Path $src 'update_webhook_v2.zip'
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Compress-Archive -Path (Join-Path $tmp '*') -DestinationPath $zipPath -Force

Remove-Item $tmp -Recurse -Force
Write-Host 'ZIP criado com sucesso!'
