<#
@FileID: dev-server-ps1-1109
@Module: dev_server
@Author: AI Assistant
@LastModified: 2025-11-09
@SecurityTag: validated
#>

$ErrorActionPreference = 'Stop'

function Start-StaticServer {
    param(
        [string]$Root = (Join-Path $PSScriptRoot '..\public'),
        [string]$Prefix = 'http://127.0.0.1:8090/'
    )

    if (-not (Test-Path $Root)) {
        Write-Error "Root path not found: $Root"
    }

    $listener = [System.Net.HttpListener]::new()
    $listener.Prefixes.Add($Prefix)
    $listener.Start()
    Write-Host "Serving $Root at $Prefix" -ForegroundColor Green

    while ($true) {
        $context = $listener.GetContext()
        $request = $context.Request
        $response = $context.Response

        # Security headers
        $response.Headers.Add('X-Frame-Options','DENY')
        $response.Headers.Add('X-Content-Type-Options','nosniff')
        $response.Headers.Add('Referrer-Policy','no-referrer')
        $response.Headers.Add('Content-Security-Policy','default-src \"self\"; img-src \"self\" data:; style-src \"self\" \"unsafe-inline\"; script-src \"self\"')

        $path = $request.Url.AbsolutePath.TrimStart('/')
        if ([string]::IsNullOrWhiteSpace($path)) { $path = 'index.html' }

        $file = Join-Path $Root $path
        if (-not (Test-Path $file)) {
            $response.StatusCode = 404
            $buf = [System.Text.Encoding]::UTF8.GetBytes('Not found')
            $response.OutputStream.Write($buf,0,$buf.Length)
            $response.Close()
            continue
        }

        # Infer simple content type
        $ext = [System.IO.Path]::GetExtension($file).ToLowerInvariant()
        $ctype = switch ($ext) {
            '.html' { 'text/html; charset=utf-8' }
            '.htm'  { 'text/html; charset=utf-8' }
            '.css'  { 'text/css; charset=utf-8' }
            '.js'   { 'application/javascript; charset=utf-8' }
            '.png'  { 'image/png' }
            '.jpg'  { 'image/jpeg' }
            '.jpeg' { 'image/jpeg' }
            '.gif'  { 'image/gif' }
            '.svg'  { 'image/svg+xml' }
            default { 'application/octet-stream' }
        }

        $bytes = [System.IO.File]::ReadAllBytes($file)
        $response.ContentType = $ctype
        $response.ContentLength64 = $bytes.Length
        $response.OutputStream.Write($bytes,0,$bytes.Length)
        $response.Close()
    }
}

Start-StaticServer