$file = "semak-status.php"
$content = Get-Content $file
$newContent = @()

for ($i = 0; $i -lt $content.Length; $i++) {
    # Skip lines 206-226 (1-indexed in editor, so 205-225 in 0-indexed array)
    if ($i -ge 205 -and $i -le 225) {
        continue
    }
    $newContent += $content[$i]
}

$newContent | Set-Content $file -Encoding UTF8
Write-Host "Debug section removed successfully"
