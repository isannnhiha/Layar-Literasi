Add-Type -AssemblyName System.Drawing

function Generate-Avatar([string]$file, [string]$bgHtml) {
    $bmp = New-Object System.Drawing.Bitmap 128, 128
    $gfx = [System.Drawing.Graphics]::FromImage($bmp)
    $gfx.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    
    $color = [System.Drawing.ColorTranslator]::FromHtml($bgHtml)
    $brush = New-Object System.Drawing.SolidBrush $color
    $gfx.FillEllipse($brush, 4, 4, 120, 120)
    
    $pen = New-Object System.Drawing.Pen ([System.Drawing.Color]::White), 4
    $gfx.DrawEllipse($pen, 4, 4, 120, 120)
    
    $bmp.Save($file, [System.Drawing.Imaging.ImageFormat]::Png)
    $gfx.Dispose()
    $bmp.Dispose()
}

Generate-Avatar "assets/images/default-avatar.png" "#64748b"
Generate-Avatar "assets/images/admin-avatar.png" "#0f172a"
Generate-Avatar "assets/images/member-avatar.png" "#2563eb"

Copy-Item "assets/images/default-avatar.png" "uploads/avatars/"
Copy-Item "assets/images/admin-avatar.png" "uploads/avatars/"
Copy-Item "assets/images/member-avatar.png" "uploads/avatars/"
Write-Host "Avatars generated successfully."
