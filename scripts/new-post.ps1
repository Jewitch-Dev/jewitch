param(
    [string]$Title,
    [string]$Description = ""
)

if ([string]::IsNullOrWhiteSpace($Title)) {
    $Title = Read-Host "Post title"
}

if ([string]::IsNullOrWhiteSpace($Title)) {
    Write-Error "A post title is required."
    exit 1
}

if ([string]::IsNullOrWhiteSpace($Description)) {
    $Description = Read-Host "Post description"
}

$slug = $Title.ToLowerInvariant()
$slug = $slug -replace "&", " and "
$slug = $slug -replace "[^a-z0-9\s-]", ""
$slug = $slug -replace "\s+", "-"
$slug = $slug -replace "-+", "-"
$slug = $slug.Trim("-")

if ([string]::IsNullOrWhiteSpace($slug)) {
    Write-Error "The title did not produce a usable file name."
    exit 1
}

$postsDir = Join-Path $PSScriptRoot "..\content\posts"
$path = Join-Path $postsDir "$slug.md"

if (Test-Path -LiteralPath $path) {
    Write-Error "A post already exists at $path"
    exit 1
}

$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$uuid = [guid]::NewGuid().ToString()

$post = @"
---
title: $Title
description: $Description
created: "$timestamp"
modified: "$timestamp"
template: post.html
uuid: $uuid
---
# $Title

"@

New-Item -ItemType Directory -Force -Path $postsDir | Out-Null
Set-Content -LiteralPath $path -Value $post -Encoding UTF8

Write-Host "Created $path"
