Add-Type -AssemblyName System.Drawing
$ErrorActionPreference = "Stop"

$siteDir = Resolve-Path (Join-Path $PSScriptRoot "..")
$postsDir = Join-Path $siteDir "content\posts"
$outputDir = Join-Path $siteDir "content\assets\social"
$profilePath = Join-Path $siteDir "content\assets\profile.png"

New-Item -ItemType Directory -Force -Path $outputDir | Out-Null

function Get-FrontMatter {
    param([string]$Path)

    $text = Get-Content -LiteralPath $Path -Raw
    $match = [regex]::Match($text, '(?s)\A---\s*\r?\n(.*?)\r?\n---')
    $metadata = @{}

    if (-not $match.Success) {
        return $metadata
    }

    foreach ($line in ($match.Groups[1].Value -split "\r?\n")) {
        if ($line -match '^\s*([^:#]+):\s*(.*?)\s*$') {
            $key = $matches[1].Trim().ToLowerInvariant()
            $value = $matches[2].Trim().Trim('"').Trim("'")
            $metadata[$key] = $value
        }
    }

    return $metadata
}

function Measure-TextWidth {
    param(
        [System.Drawing.Graphics]$Graphics,
        [string]$Text,
        [System.Drawing.Font]$Font
    )

    return $Graphics.MeasureString($Text, $Font).Width
}

function Split-TextLines {
    param(
        [System.Drawing.Graphics]$Graphics,
        [string]$Text,
        [System.Drawing.Font]$Font,
        [int]$MaxWidth,
        [int]$MaxLines
    )

    $words = $Text -split '\s+'
    $lines = New-Object System.Collections.Generic.List[string]
    $current = ""

    foreach ($word in $words) {
        $candidate = if ($current.Length -eq 0) { $word } else { "$current $word" }

        if ((Measure-TextWidth $Graphics $candidate $Font) -le $MaxWidth) {
            $current = $candidate
            continue
        }

        if ($current.Length -gt 0) {
            $lines.Add($current)
            $current = $word
        }

        if ($lines.Count -ge $MaxLines) {
            break
        }
    }

    if ($lines.Count -lt $MaxLines -and $current.Length -gt 0) {
        $lines.Add($current)
    }

    if ($lines.Count -gt 0 -and $lines.Count -eq $MaxLines) {
        $lastIndex = $lines.Count - 1
        $last = $lines[$lastIndex]
        while ((Measure-TextWidth $Graphics "$last..." $Font) -gt $MaxWidth -and $last.Length -gt 1) {
            $last = $last.Substring(0, $last.Length - 1).TrimEnd()
        }
        if ($last -ne $lines[$lastIndex]) {
            $lines[$lastIndex] = "$last..."
        }
    }

    return $lines
}

function New-SocialImage {
    param(
        [string]$Title,
        [string]$Description,
        [string]$OutputPath
    )

    $width = 1200
    $height = 630
    $bitmap = New-Object System.Drawing.Bitmap $width, $height
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)

    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    $graphics.Clear([System.Drawing.Color]::FromArgb(248, 241, 232))

    $titleFont = New-Object System.Drawing.Font "Georgia", 64, ([System.Drawing.FontStyle]::Bold)
    $siteFont = New-Object System.Drawing.Font "Georgia", 42, ([System.Drawing.FontStyle]::Bold)
    $descriptionFont = New-Object System.Drawing.Font "Segoe UI", 24, ([System.Drawing.FontStyle]::Regular)
    $domainFont = New-Object System.Drawing.Font "Segoe UI", 22, ([System.Drawing.FontStyle]::Regular)

    $inkBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(34, 34, 34))
    $accentBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(173, 74, 79))
    $mutedBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(93, 88, 82))

    $titleLines = Split-TextLines $graphics $Title $titleFont 820 3
    $y = 150
    foreach ($line in $titleLines) {
        $graphics.DrawString($line, $titleFont, $inkBrush, 120, $y)
        $y += 78
    }

    $graphics.DrawString("Jewitch", $siteFont, $accentBrush, 120, ($y + 22))

    if (-not [string]::IsNullOrWhiteSpace($Description)) {
        $descriptionLines = Split-TextLines $graphics $Description $descriptionFont 780 2
        $descriptionY = $y + 94
        foreach ($line in $descriptionLines) {
            $graphics.DrawString($line, $descriptionFont, $mutedBrush, 120, $descriptionY)
            $descriptionY += 36
        }
    }

    $graphics.DrawString("jewit.ch", $domainFont, $mutedBrush, 120, 548)

    $profileBytes = if (Test-Path -LiteralPath $profilePath) {
        $bytes = [System.IO.File]::ReadAllBytes($profilePath)
        $bytes[0..([Math]::Min(11, $bytes.Length - 1))]
    } else {
        @()
    }

    $isPng = $profileBytes.Count -ge 8 -and $profileBytes[0] -eq 0x89 -and $profileBytes[1] -eq 0x50 -and $profileBytes[2] -eq 0x4E -and $profileBytes[3] -eq 0x47
    $isJpeg = $profileBytes.Count -ge 3 -and $profileBytes[0] -eq 0xFF -and $profileBytes[1] -eq 0xD8 -and $profileBytes[2] -eq 0xFF

    if ((Test-Path -LiteralPath $profilePath) -and ($isPng -or $isJpeg)) {
        $profile = [System.Drawing.Image]::FromFile($profilePath)
        $profileSize = 96
        $profileX = 984
        $profileY = 438
        $clip = New-Object System.Drawing.Drawing2D.GraphicsPath
        $clip.AddEllipse($profileX, $profileY, $profileSize, $profileSize)
        $oldClip = $graphics.Clip
        $graphics.SetClip($clip)
        $graphics.DrawImage($profile, $profileX, $profileY, $profileSize, $profileSize)
        $graphics.Clip = $oldClip
        $profile.Dispose()
        $clip.Dispose()
    } else {
        $markBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(35, 28, 20))
        $markTextBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(213, 170, 74))
        $markFont = New-Object System.Drawing.Font "Georgia", 46, ([System.Drawing.FontStyle]::Bold)
        $graphics.FillEllipse($markBrush, 984, 438, 96, 96)
        $graphics.DrawString("J", $markFont, $markTextBrush, 1016, 452)
        $markBrush.Dispose()
        $markTextBrush.Dispose()
        $markFont.Dispose()
    }

    $bitmap.Save($OutputPath, [System.Drawing.Imaging.ImageFormat]::Png)

    $titleFont.Dispose()
    $siteFont.Dispose()
    $descriptionFont.Dispose()
    $domainFont.Dispose()
    $inkBrush.Dispose()
    $accentBrush.Dispose()
    $mutedBrush.Dispose()
    $graphics.Dispose()
    $bitmap.Dispose()
}

Get-ChildItem -LiteralPath $postsDir -Filter "*.md" | ForEach-Object {
    $metadata = Get-FrontMatter $_.FullName

    if (-not $metadata.ContainsKey("uuid") -or -not $metadata.ContainsKey("title")) {
        Write-Warning "Skipping $($_.Name): missing title or uuid."
        return
    }

    $description = if ($metadata.ContainsKey("description")) { $metadata["description"] } else { "" }
    $outputPath = Join-Path $outputDir "$($metadata["uuid"]).png"

    New-SocialImage -Title $metadata["title"] -Description $description -OutputPath $outputPath
    Write-Host "Generated social image: $outputPath"
}
