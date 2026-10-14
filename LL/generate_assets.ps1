Add-Type -AssemblyName System.Drawing

$covers = @(
    @{ Name = "cover_filosofi_teras.jpg"; Title = "FILOSOFI TERAS"; Subtitle = "Menemukan Kedamaian Mental"; Author = "A. Henry"; Bg1 = "#1e3a5f"; Bg2 = "#0f172a"; Accent = "#f97316" },
    @{ Name = "cover_php_mysql.jpg"; Title = "PEMROGRAMAN WEB"; Subtitle = "PHP 8 & MySQL Modern"; Author = "Pratama Kusuma"; Bg1 = "#1e293b"; Bg2 = "#3b82f6"; Accent = "#60a5fa" },
    @{ Name = "cover_clean_code.jpg"; Title = "CLEAN CODE"; Subtitle = "A Handbook of Agile"; Author = "Robert C. Martin"; Bg1 = "#097969"; Bg2 = "#0f172a"; Accent = "#34d399" },
    @{ Name = "cover_atomic_habits.jpg"; Title = "ATOMIC HABITS"; Subtitle = "Perubahan Kecil Hasil Besar"; Author = "James Clear"; Bg1 = "#b45309"; Bg2 = "#78350f"; Accent = "#fbbf24" },
    @{ Name = "cover_laskar_pelangi.jpg"; Title = "LASKAR PELANGI"; Subtitle = "Kisah Semangat Belajar"; Author = "Andrea Hirata"; Bg1 = "#0369a1"; Bg2 = "#0c4a6e"; Accent = "#38bdf8" },
    @{ Name = "cover_sapiens.jpg"; Title = "SAPIENS"; Subtitle = "Riwayat Singkat Manusia"; Author = "Yuval Noah Harari"; Bg1 = "#4c1d95"; Bg2 = "#1e1b4b"; Accent = "#a78bfa" },
    @{ Name = "cover_bumi_manusia.jpg"; Title = "BUMI MANUSIA"; Subtitle = "Roman Tetralogi Buru"; Author = "Pramoedya A. Toer"; Bg1 = "#831843"; Bg2 = "#4c0519"; Accent = "#f472b6" },
    @{ Name = "cover_psychology_money.jpg"; Title = "PSYCHOLOGY OF MONEY"; Subtitle = "Kebijaksanaan Finansial"; Author = "Morgan Housel"; Bg1 = "#14532d"; Bg2 = "#052e16"; Accent = "#4ade80" },
    @{ Name = "cover_kosmos.jpg"; Title = "KOSMOS"; Subtitle = "Menjelajahi Alam Semesta"; Author = "Carl Sagan"; Bg1 = "#312e81"; Bg2 = "#0f172a"; Accent = "#818cf8" },
    @{ Name = "cover_lean_startup.jpg"; Title = "THE LEAN STARTUP"; Subtitle = "Inovasi Bisnis Berkelanjutan"; Author = "Eric Ries"; Bg1 = "#c2410c"; Bg2 = "#431407"; Accent = "#fb923c" },
    @{ Name = "default-book.png"; Title = "LAYAR LITERASI"; Subtitle = "Buku Digital Indonesia"; Author = "Perpustakaan"; Bg1 = "#1e293b"; Bg2 = "#0f172a"; Accent = "#f97316" }
)

$width = 400
$height = 600

foreach ($c in $covers) {
    $bmp = New-Object System.Drawing.Bitmap $width, $height
    $gfx = [System.Drawing.Graphics]::FromImage($bmp)
    $gfx.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $gfx.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit

    $color1 = [System.Drawing.ColorTranslator]::FromHtml($c.Bg1)
    $color2 = [System.Drawing.ColorTranslator]::FromHtml($c.Bg2)
    $accentColor = [System.Drawing.ColorTranslator]::FromHtml($c.Accent)
    $rect = New-Object System.Drawing.Rectangle 0, 0, $width, $height

    $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush $rect, $color1, $color2, 45.0
    $gfx.FillRectangle($brush, $rect)

    # Decorative Border
    $pen = New-Object System.Drawing.Pen $accentColor, 3
    $gfx.DrawRectangle($pen, 20, 20, ($width - 40), ($height - 40))

    # Inner pattern or line
    $thinPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(60, 255, 255, 255)), 1
    $gfx.DrawLine($thinPen, 40, 160, ($width - 40), 160)
    $gfx.DrawLine($thinPen, 40, 480, ($width - 40), 480)

    # Accent badge
    $accentBrush = New-Object System.Drawing.SolidBrush $accentColor
    $gfx.FillRectangle($accentBrush, 40, 50, 40, 6)

    # Title
    $fontTitle = New-Object System.Drawing.Font "Arial", [float]22, [System.Drawing.FontStyle]::Bold
    $titleBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    $formatTitle = New-Object System.Drawing.StringFormat
    $formatTitle.Alignment = [System.Drawing.StringAlignment]::Near
    $formatTitle.LineAlignment = [System.Drawing.StringAlignment]::Near

    $titleRect = New-Object System.Drawing.RectangleF 40, 75, ($width - 80), 120
    $gfx.DrawString($c.Title, $fontTitle, $titleBrush, $titleRect, $formatTitle)

    # Subtitle
    $fontSub = New-Object System.Drawing.Font "Arial", [float]13, [System.Drawing.FontStyle]::Regular
    $subBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(220, 255, 255, 255))
    $subRect = New-Object System.Drawing.RectangleF 40, 200, ($width - 80), 80
    $gfx.DrawString($c.Subtitle, $fontSub, $subBrush, $subRect, $formatTitle)

    # Author
    $fontAuthor = New-Object System.Drawing.Font "Arial", [float]14, [System.Drawing.FontStyle]::Bold
    $authorBrush = New-Object System.Drawing.SolidBrush $accentColor
    $authorRect = New-Object System.Drawing.RectangleF 40, 510, ($width - 80), 40
    $gfx.DrawString($c.Author, $fontAuthor, $authorBrush, $authorRect, $formatTitle)

    $outPath = "uploads/covers/" + $c.Name
    $bmp.Save($outPath, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    
    # Also save to assets/images
    $assetPath = "assets/images/" + $c.Name
    $bmp.Save($assetPath, [System.Drawing.Imaging.ImageFormat]::Jpeg)

    $gfx.Dispose()
    $bmp.Dispose()
}

Write-Host "Generated book covers successfully."
