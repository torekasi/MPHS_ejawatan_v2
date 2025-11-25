Param(
  [string]$Branch = $(git rev-parse --abbrev-ref HEAD)
)

$ErrorActionPreference = "Stop"

Write-Output "[push] Branch: $Branch"

$versionPath = Join-Path $PSScriptRoot "..\version.json"
if (!(Test-Path $versionPath)) {
  $init = @{ major = 2; minor = 0; patch = 1 } | ConvertTo-Json -Depth 3
  Set-Content -Path $versionPath -Value $init -Encoding UTF8
}

try {
  $json = Get-Content -Path $versionPath -Raw | ConvertFrom-Json
  $major = [int]$json.major
  $minor = [int]$json.minor
  $patch = [int]$json.patch
  $patch += 1
  if ($patch -gt 30) {
    $minor += 1
    $patch = 1
  }
  $json.major = $major
  $json.minor = $minor
  $json.patch = $patch
  ($json | ConvertTo-Json -Depth 3) | Set-Content -Path $versionPath -Encoding UTF8

  git add $versionPath
  $label = "ejawatan v$major.$minor.$patch"
  git commit -m "chore(version): bump to $label"
  git push -u origin $Branch
  Write-Output "[push] Pushed $label to origin/$Branch"
} catch {
  Write-Error "[push] Failed: $_"
  exit 1
}

