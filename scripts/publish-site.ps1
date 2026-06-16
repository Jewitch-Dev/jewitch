param(
    [string]$CommitMessage = "Update site"
)

$siteDir = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $siteDir

if ([string]::IsNullOrWhiteSpace($CommitMessage)) {
    $CommitMessage = "Update site"
}

Write-Host "Building site..."
php neato.php
if ($LASTEXITCODE -ne 0) {
    Write-Error "Build failed. Nothing was committed or pushed."
    exit $LASTEXITCODE
}

Write-Host "Staging changes..."
git add .
if ($LASTEXITCODE -ne 0) {
    Write-Error "git add failed."
    exit $LASTEXITCODE
}

$status = git status --porcelain
if ([string]::IsNullOrWhiteSpace($status)) {
    Write-Host "No changes to commit."
    exit 0
}

Write-Host "Committing changes..."
git commit -m $CommitMessage
if ($LASTEXITCODE -ne 0) {
    Write-Error "git commit failed."
    exit $LASTEXITCODE
}

Write-Host "Pushing changes..."
git push
if ($LASTEXITCODE -ne 0) {
    Write-Error "git push failed."
    exit $LASTEXITCODE
}

Write-Host "Published successfully."
