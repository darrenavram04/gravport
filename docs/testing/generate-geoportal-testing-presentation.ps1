param(
    [string]$OutputPptx = "docs/testing/geoportal-testing-capstone-presentation.pptx",
    [string]$OutputPdf = "docs/testing/geoportal-testing-capstone-presentation.pdf"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function New-Rgb {
    param([int]$R, [int]$G, [int]$B)
    return $R + ($G -shl 8) + ($B -shl 16)
}

function Color {
    param([string]$Hex)
    $clean = $Hex.TrimStart("#")
    return New-Rgb ([Convert]::ToInt32($clean.Substring(0, 2), 16)) ([Convert]::ToInt32($clean.Substring(2, 2), 16)) ([Convert]::ToInt32($clean.Substring(4, 2), 16))
}

function Join-Numbered {
    param([string[]]$Items)
    $index = 1
    $lines = foreach ($item in $Items) {
        "{0}. {1}" -f $index, $item
        $index++
    }
    return ($lines -join "`r`n")
}

function Join-Dashed {
    param([string[]]$Items)
    return (($Items | ForEach-Object { "- $_" }) -join "`r`n")
}

function Add-ShapeBox {
    param(
        $Slide,
        [int]$Type,
        [double]$Left,
        [double]$Top,
        [double]$Width,
        [double]$Height,
        [int]$FillColor,
        [double]$FillTransparency = 0,
        [Nullable[int]]$LineColor = $null,
        [double]$LineWeight = 0,
        [double]$LineTransparency = 0
    )

    $shape = $Slide.Shapes.AddShape($Type, $Left, $Top, $Width, $Height)
    $shape.Fill.Visible = -1
    $shape.Fill.ForeColor.RGB = $FillColor
    $shape.Fill.Transparency = $FillTransparency

    if ($LineColor -ne $null -and $LineWeight -gt 0) {
        $shape.Line.Visible = -1
        $shape.Line.ForeColor.RGB = $LineColor
        $shape.Line.Weight = $LineWeight
        $shape.Line.Transparency = $LineTransparency
    } else {
        $shape.Line.Visible = 0
    }

    return $shape
}

function Add-TextBlock {
    param(
        $Slide,
        [string]$Text,
        [double]$Left,
        [double]$Top,
        [double]$Width,
        [double]$Height,
        [string]$FontName = "Aptos",
        [double]$FontSize = 16,
        [int]$FontColor,
        [bool]$Bold = $false,
        [bool]$Italic = $false,
        [int]$Align = 1,
        [double]$MarginLeft = 0,
        [double]$MarginRight = 0,
        [double]$MarginTop = 0,
        [double]$MarginBottom = 0
    )

    $box = $Slide.Shapes.AddTextbox(1, $Left, $Top, $Width, $Height)
    $box.TextFrame2.TextRange.Text = $Text
    $box.TextFrame2.MarginLeft = $MarginLeft
    $box.TextFrame2.MarginRight = $MarginRight
    $box.TextFrame2.MarginTop = $MarginTop
    $box.TextFrame2.MarginBottom = $MarginBottom
    $box.TextFrame2.WordWrap = -1
    $box.TextFrame2.AutoSize = 0

    $range = $box.TextFrame2.TextRange
    $range.Font.Name = $FontName
    $range.Font.Size = $FontSize
    $range.Font.Bold = [int]$Bold
    $range.Font.Italic = [int]$Italic
    $range.Font.Fill.Visible = -1
    $range.Font.Fill.ForeColor.RGB = $FontColor
    $range.ParagraphFormat.Alignment = $Align

    return $box
}

function Add-Footer {
    param(
        $Slide,
        [int]$SlideNumber,
        [int]$LineColor,
        [int]$BodyColor,
        [double]$SlideWidth,
        [double]$SlideHeight
    )

    $line = $Slide.Shapes.AddLine(44, $SlideHeight - 28, $SlideWidth - 44, $SlideHeight - 28)
    $line.Line.ForeColor.RGB = $LineColor
    $line.Line.Transparency = 0.35
    $line.Line.Weight = 1

    [void](Add-TextBlock -Slide $Slide -Text "Geoportal testing presentation deck" -Left 44 -Top ($SlideHeight - 24) -Width 270 -Height 16 -FontName "Aptos" -FontSize 8.5 -FontColor $BodyColor)
    [void](Add-TextBlock -Slide $Slide -Text ("{0:00}" -f $SlideNumber) -Left ($SlideWidth - 78) -Top ($SlideHeight - 26) -Width 34 -Height 18 -FontName "Aptos Display" -FontSize 10 -FontColor $BodyColor -Bold $true -Align 3)
}

function Add-MethodFooter {
    param(
        $Slide,
        [int]$SlideNumber,
        [hashtable]$Colors,
        [double]$SlideWidth,
        [double]$SlideHeight
    )

    $line = $Slide.Shapes.AddLine(44, $SlideHeight - 28, $SlideWidth - 44, $SlideHeight - 28)
    $line.Line.ForeColor.RGB = $Colors.Line
    $line.Line.Transparency = 0.3
    $line.Line.Weight = 1

    [void](Add-TextBlock -Slide $Slide -Text "Geoportal testing presentation deck" -Left 44 -Top ($SlideHeight - 24) -Width 270 -Height 16 -FontName "Aptos" -FontSize 8.5 -FontColor $Colors.PaleGold)
    [void](Add-TextBlock -Slide $Slide -Text ("{0:00}" -f $SlideNumber) -Left ($SlideWidth - 78) -Top ($SlideHeight - 26) -Width 34 -Height 18 -FontName "Aptos Display" -FontSize 10 -FontColor $Colors.Slate -Bold $true -Align 3)
}

function Add-LightBackground {
    param(
        $Slide,
        [double]$W,
        [double]$H,
        [int]$BaseColor,
        [int]$AccentOne,
        [int]$AccentTwo
    )

    $bg = Add-ShapeBox -Slide $Slide -Type 1 -Left 0 -Top 0 -Width $W -Height $H -FillColor $BaseColor
    $bg.ZOrder(1)

    $blob1 = Add-ShapeBox -Slide $Slide -Type 9 -Left ($W - 250) -Top -120 -Width 320 -Height 320 -FillColor $AccentOne -FillTransparency 0.9
    $blob1.Line.Visible = 0
    $blob1.ZOrder(1)

    $blob2 = Add-ShapeBox -Slide $Slide -Type 9 -Left -110 -Top ($H - 180) -Width 220 -Height 220 -FillColor $AccentTwo -FillTransparency 0.92
    $blob2.Line.Visible = 0
    $blob2.ZOrder(1)

    $band = Add-ShapeBox -Slide $Slide -Type 1 -Left 0 -Top 0 -Width 18 -Height $H -FillColor $AccentOne -FillTransparency 0.18
    $band.ZOrder(1)
}

function Add-DarkBackground {
    param(
        $Slide,
        [double]$W,
        [double]$H,
        [int]$BaseColor,
        [int]$AccentOne,
        [int]$AccentTwo
    )

    $bg = Add-ShapeBox -Slide $Slide -Type 1 -Left 0 -Top 0 -Width $W -Height $H -FillColor $BaseColor
    $bg.ZOrder(1)

    $shape1 = Add-ShapeBox -Slide $Slide -Type 5 -Left ($W - 300) -Top -70 -Width 350 -Height 220 -FillColor $AccentOne -FillTransparency 0.78
    $shape1.Line.Visible = 0
    $shape1.Adjustments.Item(1) = 0.06
    $shape1.Rotation = 16
    $shape1.ZOrder(1)

    $shape2 = Add-ShapeBox -Slide $Slide -Type 5 -Left -70 -Top ($H - 150) -Width 260 -Height 180 -FillColor $AccentTwo -FillTransparency 0.8
    $shape2.Line.Visible = 0
    $shape2.Adjustments.Item(1) = 0.07
    $shape2.Rotation = -18
    $shape2.ZOrder(1)
}

function Add-EyebrowTitle {
    param(
        $Slide,
        [string]$Eyebrow,
        [string]$Title,
        [string]$Subtitle,
        [int]$EyebrowFill,
        [int]$EyebrowFont,
        [int]$TitleColor,
        [int]$BodyColor
    )

    $pill = Add-ShapeBox -Slide $Slide -Type 5 -Left 48 -Top 28 -Width 132 -Height 28 -FillColor $EyebrowFill
    $pill.Adjustments.Item(1) = 0.35
    [void](Add-TextBlock -Slide $Slide -Text $Eyebrow -Left 58 -Top 34 -Width 112 -Height 16 -FontName "Aptos" -FontSize 9.5 -FontColor $EyebrowFont -Bold $true -Align 2)
    [void](Add-TextBlock -Slide $Slide -Text $Title -Left 48 -Top 68 -Width 770 -Height 50 -FontName "Georgia" -FontSize 26 -FontColor $TitleColor -Bold $true)
    [void](Add-TextBlock -Slide $Slide -Text $Subtitle -Left 48 -Top 108 -Width 820 -Height 36 -FontName "Aptos" -FontSize 12.5 -FontColor $BodyColor)
}

function Add-Card {
    param(
        $Slide,
        [double]$Left,
        [double]$Top,
        [double]$Width,
        [double]$Height,
        [string]$Title,
        [string]$Body,
        [int]$FillColor,
        [int]$TitleColor,
        [int]$BodyColor,
        [Nullable[int]]$AccentColor = $null,
        [double]$BodyFontSize = 11.3
    )

    $card = Add-ShapeBox -Slide $Slide -Type 5 -Left $Left -Top $Top -Width $Width -Height $Height -FillColor $FillColor -LineColor (Color "#D9D5CD") -LineWeight 0.9 -LineTransparency 0.2
    $card.Adjustments.Item(1) = 0.06

    if ($AccentColor -ne $null) {
        [void](Add-ShapeBox -Slide $Slide -Type 1 -Left ($Left + 16) -Top ($Top + 16) -Width 38 -Height 5 -FillColor $AccentColor)
    }

    [void](Add-TextBlock -Slide $Slide -Text $Title -Left ($Left + 16) -Top ($Top + 26) -Width ($Width - 32) -Height 26 -FontName "Aptos Display" -FontSize 15.5 -FontColor $TitleColor -Bold $true)
    [void](Add-TextBlock -Slide $Slide -Text $Body -Left ($Left + 16) -Top ($Top + 56) -Width ($Width - 32) -Height ($Height - 68) -FontName "Aptos" -FontSize $BodyFontSize -FontColor $BodyColor)
}

function Add-DividerSlide {
    param(
        $Slide,
        [string]$Mini,
        [string]$Title,
        [string]$Subtitle,
        [string]$BigText,
        [hashtable]$Colors,
        [double]$SlideWidth,
        [double]$SlideHeight
    )

    Add-DarkBackground -Slide $Slide -W $SlideWidth -H $SlideHeight -BaseColor $Colors.Navy -AccentOne $Colors.Gold -AccentTwo $Colors.Teal
    [void](Add-TextBlock -Slide $Slide -Text $BigText -Left 620 -Top 108 -Width 280 -Height 180 -FontName "Georgia" -FontSize 88 -FontColor $Colors.PaleGold -Italic $true)
    [void](Add-TextBlock -Slide $Slide -Text $Mini -Left 58 -Top 62 -Width 160 -Height 20 -FontName "Aptos" -FontSize 10 -FontColor $Colors.PaleGold -Bold $true)
    [void](Add-TextBlock -Slide $Slide -Text $Title -Left 58 -Top 128 -Width 520 -Height 70 -FontName "Georgia" -FontSize 28 -FontColor $Colors.White -Bold $true)
    [void](Add-TextBlock -Slide $Slide -Text $Subtitle -Left 58 -Top 212 -Width 460 -Height 54 -FontName "Aptos" -FontSize 14 -FontColor $Colors.Sand)
}

function Add-MethodSlide {
    param(
        $Slide,
        [hashtable]$Method,
        [hashtable]$Colors,
        [double]$SlideWidth,
        [double]$SlideHeight,
        [int]$SlideNumber
    )

    Add-LightBackground -Slide $Slide -W $SlideWidth -H $SlideHeight -BaseColor $Colors.Cloud -AccentOne $Method.Accent -AccentTwo $Colors.TealSoft

    $leftPanel = Add-ShapeBox -Slide $Slide -Type 1 -Left 0 -Top 0 -Width 258 -Height $SlideHeight -FillColor $Colors.Navy
    [void](Add-TextBlock -Slide $Slide -Text $Method.Number -Left 42 -Top 50 -Width 160 -Height 72 -FontName "Georgia" -FontSize 38 -FontColor $Colors.PaleGold -Italic $true)
    [void](Add-TextBlock -Slide $Slide -Text $Method.Title -Left 42 -Top 138 -Width 182 -Height 96 -FontName "Georgia" -FontSize 21 -FontColor $Colors.White -Bold $true)
    [void](Add-TextBlock -Slide $Slide -Text $Method.Focus -Left 42 -Top 236 -Width 176 -Height 74 -FontName "Aptos" -FontSize 11.5 -FontColor $Colors.PaleGold)
    $mini = Add-ShapeBox -Slide $Slide -Type 5 -Left 42 -Top 322 -Width 162 -Height 28 -FillColor $Method.Accent
    $mini.Adjustments.Item(1) = 0.35
    [void](Add-TextBlock -Slide $Slide -Text $Method.Priority -Left 54 -Top 328 -Width 138 -Height 16 -FontName "Aptos" -FontSize 9 -FontColor $Colors.White -Bold $true -Align 2)
    [void](Add-TextBlock -Slide $Slide -Text "Metode ini sebaiknya dibahas sebagai bagian dari peta strategi pengujian, bukan berdiri sendiri tanpa konteks sistem." -Left 42 -Top 378 -Width 170 -Height 82 -FontName "Aptos" -FontSize 10 -FontColor $Colors.Sand)

    [void](Add-TextBlock -Slide $Slide -Text "Definisi" -Left 292 -Top 42 -Width 120 -Height 18 -FontName "Aptos" -FontSize 10 -FontColor $Colors.Slate -Bold $true)
    Add-Card -Slide $Slide -Left 286 -Top 58 -Width 626 -Height 92 -Title "Definisi metode" -Body $Method.Definition -FillColor $Colors.Card -TitleColor $Colors.Navy -BodyColor $Colors.Ink -AccentColor $Method.Accent -BodyFontSize 11.2

    Add-Card -Slide $Slide -Left 286 -Top 166 -Width 302 -Height 148 -Title "Alur pelaksanaan" -Body (Join-Numbered $Method.Flow) -FillColor $Colors.Card -TitleColor $Colors.Navy -BodyColor $Colors.Ink -AccentColor $Method.Accent -BodyFontSize 9.8
    Add-Card -Slide $Slide -Left 610 -Top 166 -Width 302 -Height 148 -Title "Pihak yang dibutuhkan" -Body (Join-Dashed $Method.Stakeholders) -FillColor $Colors.Card -TitleColor $Colors.Navy -BodyColor $Colors.Ink -AccentColor $Method.Accent -BodyFontSize 9.8

    $outputText = Join-Dashed $Method.Outputs
    $toolsText = Join-Dashed $Method.Tools
    Add-Card -Slide $Slide -Left 286 -Top 328 -Width 194 -Height 158 -Title "Output artefak" -Body $outputText -FillColor $Colors.Card -TitleColor $Colors.Navy -BodyColor $Colors.Ink -AccentColor $Method.Accent -BodyFontSize 9.8
    Add-Card -Slide $Slide -Left 501 -Top 328 -Width 194 -Height 158 -Title "Tools" -Body $toolsText -FillColor $Colors.Card -TitleColor $Colors.Navy -BodyColor $Colors.Ink -AccentColor $Method.Accent -BodyFontSize 9.8
    Add-Card -Slide $Slide -Left 716 -Top 328 -Width 196 -Height 158 -Title "Konteks penggunaan" -Body $Method.UseCase -FillColor $Colors.Card -TitleColor $Colors.Navy -BodyColor $Colors.Ink -AccentColor $Method.Accent -BodyFontSize 10.0

    Add-MethodFooter -Slide $Slide -SlideNumber $SlideNumber -Colors $Colors -SlideWidth $SlideWidth -SlideHeight $SlideHeight
}

$colors = @{
    Navy = Color "#13293D"
    Ink = Color "#243745"
    Slate = Color "#65727D"
    Muted = Color "#7A8790"
    White = Color "#FFFCF8"
    Cloud = Color "#F7F2EA"
    Card = Color "#FFFDF9"
    Sand = Color "#E9DED0"
    PaleGold = Color "#F0E2D1"
    Line = Color "#D8CFC3"
    Gold = Color "#B86A2D"
    Teal = Color "#1E7C74"
    TealSoft = Color "#D7ECE7"
    Terracotta = Color "#A45B42"
    Blush = Color "#F2DFD5"
    Olive = Color "#7A8450"
    OliveSoft = Color "#E5E9D8"
    Danger = Color "#A14A3B"
}

$methods = @(
    @{
        Number = "01"
        Title = "Black-box Functional Testing"
        Focus = "Menguji apakah fitur bekerja benar dari sudut pandang pengguna."
        Priority = "CORE METHOD FOR CAPSTONE"
        Accent = $colors.Gold
        Definition = "Black-box functional testing adalah pengujian yang memeriksa fungsi aplikasi berdasarkan input, proses, dan output yang terlihat oleh pengguna, tanpa perlu masuk ke detail implementasi kode internal."
        Flow = @(
            "Turunkan requirement menjadi test case per fitur utama.",
            "Siapkan akun, data uji, dan prasyarat tiap skenario.",
            "Jalankan langkah pengujian melalui UI atau endpoint yang relevan.",
            "Catat hasil pass atau fail beserta screenshot dan observasi."
        )
        Stakeholders = @(
            "Mahasiswa atau penguji utama.",
            "Akun admin dan user sebagai aktor uji.",
            "Pembimbing sebagai reviewer hasil, tidak wajib ikut saat eksekusi."
        )
        Outputs = @(
            "Matriks test case.",
            "Execution log.",
            "Screenshot evidence per skenario."
        )
        Tools = @(
            "Browser.",
            "Spreadsheet test case.",
            "Postman bila ada endpoint yang ingin diuji langsung."
        )
        UseCase = "Paling tepat untuk membuktikan requirement fitur seperti login, katalog, metadata, admin import, WebMap, dan download."
    },
    @{
        Number = "02"
        Title = "White-box / Unit Testing"
        Focus = "Menguji logika fungsi atau class backend secara terisolasi."
        Priority = "OPTIONAL TECHNICAL DEPTH"
        Accent = $colors.Teal
        Definition = "White-box atau unit testing adalah pengujian yang memeriksa unit logika program seperti function, class, atau method tertentu berdasarkan perilaku kode internal dan hasil yang diharapkan."
        Flow = @(
            "Pilih komponen kritis seperti importer, registry, atau exporter.",
            "Tentukan input, output, dan kondisi gagal yang perlu diuji.",
            "Tulis test otomatis dan jalankan lewat test runner.",
            "Analisis failure sebagai indikasi regresi logika."
        )
        Stakeholders = @(
            "Mahasiswa atau developer utama.",
            "Opsional reviewer teknis bila ada.",
            "Tidak membutuhkan user bisnis untuk eksekusi."
        )
        Outputs = @(
            "File test otomatis.",
            "Hasil runner dan error log.",
            "Opsional ringkasan coverage."
        )
        Tools = @(
            "PHPUnit atau framework test lain.",
            "Composer environment yang siap.",
            "Mock data atau test doubles bila perlu."
        )
        UseCase = "Cocok untuk menambah nilai engineering, terutama pada DatasetImportService, GeoportalDatasetRegistry, atau FilteredMetadataExporter. Pada checkout ini, phpunit masih perlu disiapkan terlebih dahulu."
    },
    @{
        Number = "03"
        Title = "Integration Testing"
        Focus = "Membuktikan data mengalir benar antar komponen sistem."
        Priority = "MOST IMPORTANT FOR THIS GEOportal"
        Accent = $colors.Terracotta
        Definition = "Integration testing adalah pengujian yang memeriksa hubungan antar modul atau layer sistem, misalnya controller, service, database, PostGIS, dan antarmuka frontend, untuk memastikan data yang diproses tetap konsisten dari awal sampai akhir."
        Flow = @(
            "Siapkan paket impor dan lingkungan database yang valid.",
            "Jalankan alur impor lewat UI atau CLI.",
            "Verifikasi isi tabel, endpoint, dan hasil di WebMap atau katalog.",
            "Bandingkan apakah output akhir sesuai dengan data sumber dan filter."
        )
        Stakeholders = @(
            "Mahasiswa atau penguji utama.",
            "Admin sistem atau akun admin uji.",
            "Opsional pihak teknis yang memahami database."
        )
        Outputs = @(
            "Output import CLI atau UI.",
            "Query validasi database.",
            "Screenshot katalog, WebMap, dan file hasil download."
        )
        Tools = @(
            "Browser.",
            "CLI php spark dataset:import.",
            "Query PostgreSQL atau pengecekan endpoint."
        )
        UseCase = "Ini metode paling penting untuk geoportal karena risiko utama sistem ada pada alur package import -> PostGIS -> catalog -> WebMap -> download."
    },
    @{
        Number = "04"
        Title = "System / End-to-End Testing"
        Focus = "Menguji satu perjalanan pengguna secara utuh dari awal sampai akhir."
        Priority = "GOOD FOR DEMO AND SIDANG"
        Accent = $colors.Olive
        Definition = "System atau end-to-end testing adalah pengujian yang menjalankan satu alur penggunaan lengkap seolah-olah sistem sudah dipakai nyata, sehingga yang diuji bukan lagi satu fitur tunggal, tetapi keseluruhan perjalanan pengguna."
        Flow = @(
            "Definisikan skenario bisnis lengkap yang ingin dibuktikan.",
            "Jalankan seluruh langkah tanpa memotong proses menjadi bagian kecil.",
            "Pastikan hasil akhir tercapai, misalnya data berhasil diimpor lalu bisa ditampilkan dan diunduh.",
            "Catat titik hambatan yang muncul selama perjalanan."
        )
        Stakeholders = @(
            "Mahasiswa atau penguji utama.",
            "Aktor uji seperti admin atau user.",
            "Sangat baik jika pembimbing ikut melihat sebagai observer."
        )
        Outputs = @(
            "Narasi skenario end-to-end.",
            "Screenshot atau video demo.",
            "Catatan hambatan antar langkah."
        )
        Tools = @(
            "Browser.",
            "Akun admin dan user.",
            "Script demo atau catatan langkah presentasi."
        )
        UseCase = "Paling cocok untuk membuktikan kesiapan sistem saat presentasi karena hasilnya mudah dipahami, misalnya admin login -> import -> buka WebMap -> preview -> unduh."
    },
    @{
        Number = "05"
        Title = "User Acceptance Testing"
        Focus = "Memastikan aplikasi diterima oleh calon pengguna."
        Priority = "ESSENTIAL FOR CAPSTONE ARGUMENT"
        Accent = $colors.Gold
        Definition = "User Acceptance Testing atau UAT adalah pengujian yang meminta calon pengguna atau evaluator mencoba sistem lalu menilai apakah fungsi, alur, dan tampilan aplikasi sudah sesuai dengan kebutuhan dan cukup mudah dipakai."
        Flow = @(
            "Tentukan evaluator yang mewakili calon pengguna.",
            "Siapkan skenario sederhana dan briefing singkat.",
            "Minta evaluator menjalankan tugas utama di sistem.",
            "Kumpulkan penilaian, komentar, dan saran perbaikan."
        )
        Stakeholders = @(
            "Calon admin data.",
            "Calon user non-admin.",
            "Pembimbing atau penguji bisa dilibatkan sebagai evaluator."
        )
        Outputs = @(
            "Form UAT yang terisi.",
            "Skor atau penilaian likert.",
            "Komentar kualitatif dari evaluator."
        )
        Tools = @(
            "Form UAT.",
            "Browser.",
            "Opsional spreadsheet rekap hasil."
        )
        UseCase = "Paling tepat dilakukan setelah fitur inti stabil, sehingga penilaian pengguna berfokus pada penerimaan sistem, bukan bug dasar yang belum selesai."
    },
    @{
        Number = "06"
        Title = "Performance Smoke Testing"
        Focus = "Mengukur respons dasar fungsi penting secara ringan."
        Priority = "SUPPORTING EVIDENCE"
        Accent = $colors.Teal
        Definition = "Performance smoke testing adalah pengukuran ringan terhadap waktu respons atau kecepatan eksekusi fungsi penting sistem tanpa membangun simulasi beban besar. Fokusnya adalah memastikan performa dasar masih masuk akal untuk kebutuhan presentasi dan penggunaan awal."
        Flow = @(
            "Pilih fitur yang sensitif terhadap performa.",
            "Tentukan indikator seperti waktu preview, waktu import, atau waktu download.",
            "Jalankan pengukuran beberapa kali pada kondisi serupa.",
            "Rekap hasil dan identifikasi bottleneck dasar."
        )
        Stakeholders = @(
            "Mahasiswa atau penguji utama.",
            "Opsional pihak teknis bila perlu verifikasi environment."
        )
        Outputs = @(
            "Tabel response time.",
            "Catatan threshold sederhana.",
            "Temuan bottleneck yang paling menonjol."
        )
        Tools = @(
            "Stopwatch atau timing manual.",
            "Browser dev tools.",
            "CLI timing untuk import package."
        )
        UseCase = "Cocok untuk fungsi preview WebMap, import package, download vector, dan download raster. Tidak perlu diperluas menjadi load test kecuali diminta pembimbing."
    },
    @{
        Number = "07"
        Title = "Basic Security Testing"
        Focus = "Memeriksa akses, validasi input, dan penyalahgunaan alur."
        Priority = "RECOMMENDED BASIC CHECK"
        Accent = $colors.Terracotta
        Definition = "Basic security testing adalah pengujian dasar yang fokus pada kontrol akses, validasi input, dan respons sistem terhadap parameter atau file yang tidak semestinya, tanpa harus masuk ke penetration testing penuh."
        Flow = @(
            "Petakan route dan fungsi yang sensitif seperti admin dan download.",
            "Uji guest, user, dan admin pada route yang berbeda.",
            "Uji input file atau parameter yang tidak valid.",
            "Catat apakah sistem memblokir, mengarahkan, atau gagal dengan aman."
        )
        Stakeholders = @(
            "Mahasiswa atau penguji utama.",
            "Akun guest, user, dan admin.",
            "Opsional reviewer teknis."
        )
        Outputs = @(
            "Daftar skenario akses tidak sah.",
            "Screenshot error handling.",
            "Rekomendasi perbaikan kontrol akses."
        )
        Tools = @(
            "Browser.",
            "Postman.",
            "Checklist route dan role."
        )
        UseCase = "Penting untuk memastikan route admin, endpoint download, upload AOI, dan validasi parameter tidak mudah disalahgunakan. Ini cukup dibawa sebagai security baseline, bukan audit penuh."
    }
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Resolve-Path (Join-Path $scriptDir "..\..")
$pptxPath = Join-Path $projectRoot $OutputPptx
$pdfPath = Join-Path $projectRoot $OutputPdf
$null = New-Item -ItemType Directory -Force -Path (Split-Path -Parent $pptxPath)
$null = New-Item -ItemType Directory -Force -Path (Split-Path -Parent $pdfPath)

$powerPoint = $null
$presentation = $null

try {
    $powerPoint = New-Object -ComObject PowerPoint.Application
    $powerPoint.Visible = -1
    $presentation = $powerPoint.Presentations.Add()
    # Use explicit 16:9 widescreen size in points to avoid enum mismatches across Office versions.
    $presentation.PageSetup.SlideWidth = 960
    $presentation.PageSetup.SlideHeight = 540

    $slideWidth = $presentation.PageSetup.SlideWidth
    $slideHeight = $presentation.PageSetup.SlideHeight
    $slideIndex = 1

    # 1. Cover
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-DarkBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Navy -AccentOne $colors.Gold -AccentTwo $colors.Teal
    [void](Add-TextBlock -Slide $slide -Text "Geoportal Capstone Presentation" -Left 56 -Top 46 -Width 220 -Height 18 -FontName "Aptos" -FontSize 10 -FontColor $colors.PaleGold -Bold $true)
    [void](Add-TextBlock -Slide $slide -Text "Skema Pengujian Geoportal" -Left 56 -Top 96 -Width 520 -Height 84 -FontName "Georgia" -FontSize 30 -FontColor $colors.White -Bold $true)
    [void](Add-TextBlock -Slide $slide -Text "Versi revisi dengan penjelasan metode yang lebih lengkap: definisi, alur pelaksanaan, pihak yang dibutuhkan, artefak, dan konteks penggunaan." -Left 58 -Top 194 -Width 460 -Height 62 -FontName "Aptos" -FontSize 15 -FontColor $colors.Sand)
    $coverCard = Add-ShapeBox -Slide $slide -Type 5 -Left 610 -Top 62 -Width 286 -Height 328 -FillColor $colors.Card
    $coverCard.Adjustments.Item(1) = 0.06
    [void](Add-TextBlock -Slide $slide -Text "Isi utama" -Left 638 -Top 92 -Width 140 -Height 24 -FontName "Aptos Display" -FontSize 18 -FontColor $colors.Navy -Bold $true)
    [void](Add-TextBlock -Slide $slide -Text "01  Konteks sistem`r`n02  Peta metode pengujian`r`n03  Detail tiap metode`r`n04  Strategi rekomendasi`r`n05  Alur eksekusi`r`n06  Pihak yang terlibat`r`n07  Data, dokumen, evidence`r`n08  Poin diskusi pembimbing" -Left 638 -Top 130 -Width 220 -Height 184 -FontName "Aptos" -FontSize 13 -FontColor $colors.Ink)
    [void](Add-TextBlock -Slide $slide -Text "Nama Mahasiswa`r`nNIM / Program Studi`r`nSidang atau bimbingan capstone" -Left 638 -Top 326 -Width 200 -Height 56 -FontName "Aptos" -FontSize 11.5 -FontColor $colors.Slate)
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Sand -BodyColor $colors.Sand -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 2. Why mixed testing
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Gold -AccentTwo $colors.TealSoft
    Add-EyebrowTitle -Slide $slide -Eyebrow "Why It Matters" -Title "Geoportal ini tidak cukup diuji dengan satu metode saja" -Subtitle "Sistem memadukan UI, route, service, PostGIS, metadata XML, raster, dan download sehingga pengujian harus mencakup lapisan teknis dan lapisan pengguna." -EyebrowFill $colors.Gold -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    Add-Card -Slide $slide -Left 48 -Top 160 -Width 204 -Height 116 -Title "Auth & role" -Body "Akses guest, user, dan admin harus konsisten di route dan fitur." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 268 -Top 160 -Width 204 -Height 116 -Title "Catalog" -Body "Dataset harus bisa dicari, dilihat, dan diunduh sesuai izin." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 488 -Top 160 -Width 204 -Height 116 -Title "Admin import" -Body "CSV, TIFF, dan XML harus masuk ke alur yang sama dan repeatable." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Card -Slide $slide -Left 708 -Top 160 -Width 204 -Height 116 -Title "WebMap & PostGIS" -Body "Preview, filter, grid, dan download harus tetap sinkron dengan data aktif." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Olive
    $risk = Add-ShapeBox -Slide $slide -Type 5 -Left 48 -Top 310 -Width 864 -Height 146 -FillColor $colors.Card -LineColor $colors.Line -LineWeight 0.9 -LineTransparency 0.22
    $risk.Adjustments.Item(1) = 0.06
    [void](Add-TextBlock -Slide $slide -Text "Empat risiko utama yang perlu dibuktikan" -Left 70 -Top 332 -Width 280 -Height 22 -FontName "Aptos Display" -FontSize 16 -FontColor $colors.Navy -Bold $true)
    [void](Add-TextBlock -Slide $slide -Text "1. Import berhasil tetapi hasil tidak tampil benar di katalog atau WebMap.`r`n2. Route dan role terlihat benar, tetapi download atau halaman admin masih bisa diakses tidak semestinya.`r`n3. Filter spasial tampak berjalan, tetapi data atau metadata yang keluar tidak sesuai area aktif.`r`n4. Presentasi hanya menunjukkan demo, bukan evidence pengujian yang terdokumentasi." -Left 70 -Top 364 -Width 812 -Height 72 -FontName "Aptos" -FontSize 12 -FontColor $colors.Ink)
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 3. Method map
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Teal -AccentTwo $colors.Gold
    Add-EyebrowTitle -Slide $slide -Eyebrow "Method Map" -Title "Peta metode yang bisa dibawa ke pembimbing" -Subtitle "Slide ini membantu menjelaskan bahwa tiap metode punya fungsi yang berbeda, bukan sekadar istilah pengujian yang mirip." -EyebrowFill $colors.Teal -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    Add-Card -Slide $slide -Left 48 -Top 154 -Width 272 -Height 92 -Title "Black-box functional" -Body "Validasi fitur utama dari sisi pengguna." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 344 -Top 154 -Width 272 -Height 92 -Title "Integration testing" -Body "Membuktikan data mengalir benar antar komponen." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Card -Slide $slide -Left 640 -Top 154 -Width 272 -Height 92 -Title "User Acceptance Testing" -Body "Menilai penerimaan dan kemudahan pakai." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 48 -Top 270 -Width 272 -Height 92 -Title "System / end-to-end" -Body "Menguji satu perjalanan pengguna secara utuh." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Olive
    Add-Card -Slide $slide -Left 344 -Top 270 -Width 272 -Height 92 -Title "White-box / unit" -Body "Menguji logika internal class dan method backend." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 640 -Top 270 -Width 272 -Height 92 -Title "Performance smoke" -Body "Mengukur respons dasar fungsi penting secara ringan." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 344 -Top 386 -Width 272 -Height 92 -Title "Basic security" -Body "Memeriksa kontrol akses dan validasi input." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    [void](Add-TextBlock -Slide $slide -Text "Pesan utama untuk pembimbing: metode yang direkomendasikan bukan satu metode tunggal, tetapi kombinasi yang dipilih sesuai karakter geoportal." -Left 52 -Top 490 -Width 860 -Height 20 -FontName "Aptos" -FontSize 11 -FontColor $colors.Slate)
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 4. Divider
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-DividerSlide -Slide $slide -Mini "Deep Dive" -Title "Detail setiap metode pengujian" -Subtitle "Bagian ini menjelaskan definisi, alur, pihak yang dibutuhkan, artefak, dan kapan metode dipakai." -BigText "01-07" -Colors $colors -SlideWidth $slideWidth -SlideHeight $slideHeight
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Sand -BodyColor $colors.Sand -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 5-11. Method slides
    foreach ($method in $methods) {
        $slide = $presentation.Slides.Add($slideIndex, 12)
        Add-MethodSlide -Slide $slide -Method $method -Colors $colors -SlideWidth $slideWidth -SlideHeight $slideHeight -SlideNumber $slideIndex
        $slideIndex++
    }

    # 12. Recommended combination
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Gold -AccentTwo $colors.TealSoft
    Add-EyebrowTitle -Slide $slide -Eyebrow "Recommendation" -Title "Kombinasi metode terbaik untuk capstone ini" -Subtitle "Tujuannya adalah tetap kuat secara akademis, tetapi tidak terlalu berat untuk dijalankan dalam waktu capstone." -EyebrowFill $colors.Gold -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    $hero = Add-ShapeBox -Slide $slide -Type 5 -Left 48 -Top 156 -Width 864 -Height 92 -FillColor $colors.Navy
    $hero.Adjustments.Item(1) = 0.06
    [void](Add-TextBlock -Slide $slide -Text "Black-box Functional + Integration Testing + UAT + Performance Smoke" -Left 76 -Top 186 -Width 810 -Height 30 -FontName "Georgia" -FontSize 23 -FontColor $colors.White -Bold $true -Align 2)
    Add-Card -Slide $slide -Left 48 -Top 280 -Width 272 -Height 146 -Title "Mengapa paling tepat" -Body "Kombinasi ini menutup tiga kebutuhan sekaligus: pembuktian fitur, pembuktian integrasi geoportal, dan pembuktian penerimaan pengguna." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 344 -Top 280 -Width 272 -Height 146 -Title "Kekuatan presentasi" -Body "Integration testing memberi kedalaman teknis, sementara UAT dan black-box membuat presentasi mudah dipahami pembimbing." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 640 -Top 280 -Width 272 -Height 146 -Title "Tambahan opsional" -Body "Jika pembimbing meminta nilai engineering lebih jauh, tambahkan white-box atau API regression secara terbatas sebagai lampiran." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 13. Overall execution flow
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Teal -AccentTwo $colors.Gold
    Add-EyebrowTitle -Slide $slide -Eyebrow "Execution Flow" -Title "Alur pengujian dari awal sampai akhir" -Subtitle "Urutan ini bisa langsung dipakai sebagai struktur bab pengujian di laporan." -EyebrowFill $colors.Teal -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    Add-Card -Slide $slide -Left 48 -Top 160 -Width 204 -Height 112 -Title "1. Persiapan" -Body "Siapkan akun, data uji, paket import, sample AOI, dan template evidence." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 268 -Top 160 -Width 204 -Height 112 -Title "2. Validasi lingkungan" -Body "Pastikan app, route, DB, dan command import aktif." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 488 -Top 160 -Width 204 -Height 112 -Title "3. Functional test" -Body "Jalankan skenario fitur inti berdasarkan test case." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Card -Slide $slide -Left 708 -Top 160 -Width 204 -Height 112 -Title "4. Integration test" -Body "Telusuri hasil import sampai WebMap dan download." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Olive
    Add-Card -Slide $slide -Left 158 -Top 312 -Width 204 -Height 112 -Title "5. UAT" -Body "Mintakan evaluator mencoba alur yang disiapkan." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 378 -Top 312 -Width 204 -Height 112 -Title "6. Performance smoke" -Body "Ukur preview, import, dan download secara sederhana." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 598 -Top 312 -Width 204 -Height 112 -Title "7. Rekap hasil" -Body "Simpulkan pass rate, defect, skor UAT, dan gap sistem." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 14. Stakeholder matrix
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Gold -AccentTwo $colors.TealSoft
    Add-EyebrowTitle -Slide $slide -Eyebrow "Stakeholders" -Title "Siapa saja yang dibutuhkan untuk menjalankan pengujian" -Subtitle "Tidak semua metode membutuhkan orang yang sama. Ini penting dijelaskan agar scope pengujian terlihat realistis." -EyebrowFill $colors.Gold -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    Add-Card -Slide $slide -Left 48 -Top 156 -Width 204 -Height 230 -Title "Mahasiswa / penguji utama" -Body "Peran inti hampir di semua metode: menyusun test case, mengeksekusi uji, merekam evidence, dan menyusun analisis hasil." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 268 -Top 156 -Width 204 -Height 230 -Title "Admin sistem atau akun admin" -Body "Diperlukan untuk pengujian import package, metadata, route admin, dan skenario akses khusus." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 488 -Top 156 -Width 204 -Height 230 -Title "User non-admin" -Body "Diperlukan untuk menguji katalog, download, akses terbatas, dan menilai kemudahan penggunaan pada UAT." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Card -Slide $slide -Left 708 -Top 156 -Width 204 -Height 230 -Title "Pembimbing / evaluator" -Body "Tidak harus menjalankan semua test, tetapi sangat berguna untuk menilai UAT, scope strategi, dan kelayakan hasil." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Olive
    [void](Add-TextBlock -Slide $slide -Text "Jika sumber daya terbatas, minimal libatkan: Anda sendiri sebagai penguji utama, satu akun admin, satu akun user, dan 2-3 evaluator UAT." -Left 52 -Top 414 -Width 860 -Height 20 -FontName "Aptos" -FontSize 11 -FontColor $colors.Slate)
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 15. Data and file requirements
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Teal -AccentTwo $colors.Gold
    Add-EyebrowTitle -Slide $slide -Eyebrow "Data Setup" -Title "Data, format file, dan prasyarat pengujian" -Subtitle "Bagian ini penting karena kualitas pengujian sangat bergantung pada data uji yang benar." -EyebrowFill $colors.Teal -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    $tree = Add-ShapeBox -Slide $slide -Type 5 -Left 48 -Top 154 -Width 364 -Height 272 -FillColor $colors.Navy
    $tree.Adjustments.Item(1) = 0.05
    [void](Add-TextBlock -Slide $slide -Text "Package import aktif`r`nwritable/imports/<package>/`r`n  level1/`r`n    Metadata_Gravimetri_Level_1.xml`r`n    faa/*.csv`r`n    cba/*.csv`r`n  level2/`r`n    Metadata_Gravimetri_Level_2.xml`r`n    faa/FAA.tif`r`n    cba/CBA.tif" -Left 74 -Top 184 -Width 314 -Height 208 -FontName "Consolas" -FontSize 12 -FontColor $colors.White)
    Add-Card -Slide $slide -Left 440 -Top 154 -Width 220 -Height 126 -Title "CSV Level 1" -Body "Minimal memuat Lintang, Bujur, Tinggi Ortometrik atau Tinggi Ort, dan FAA atau CBA." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 684 -Top 154 -Width 228 -Height 126 -Title "Metadata XML" -Body "Digunakan untuk sinkronisasi metadata level 1 dan level 2 ke database." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 440 -Top 300 -Width 220 -Height 126 -Title "Raster TIFF" -Body "Digunakan untuk level 2 dan dipotong mengikuti grid 0.125 derajat." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Card -Slide $slide -Left 684 -Top 300 -Width 228 -Height 126 -Title "AOI WebMap" -Body "Siapkan GeoJSON dan KML untuk menguji upload area aktif di peta." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Olive
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 16. Docs and evidence
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Gold -AccentTwo $colors.TealSoft
    Add-EyebrowTitle -Slide $slide -Eyebrow "Evidence" -Title "Dokumen, form, dan bukti uji yang perlu dikumpulkan" -Subtitle "Dengan evidence yang rapi, hasil pengujian akan terlihat jauh lebih kuat daripada demo lisan semata." -EyebrowFill $colors.Gold -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    Add-Card -Slide $slide -Left 48 -Top 156 -Width 202 -Height 110 -Title "Test case matrix" -Body "Daftar skenario, expected result, prioritas, dan status." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 266 -Top 156 -Width 202 -Height 110 -Title "Execution log" -Body "Mencatat siapa, kapan, dan bagaimana test dijalankan." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 484 -Top 156 -Width 202 -Height 110 -Title "UAT form" -Body "Bukti penilaian dari evaluator atau calon pengguna." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Card -Slide $slide -Left 702 -Top 156 -Width 210 -Height 110 -Title "Bug report" -Body "Membuat temuan gagal lebih terstruktur dan bisa ditindaklanjuti." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Olive
    $panel = Add-ShapeBox -Slide $slide -Type 5 -Left 48 -Top 296 -Width 864 -Height 134 -FillColor $colors.Navy
    $panel.Adjustments.Item(1) = 0.05
    [void](Add-TextBlock -Slide $slide -Text "Evidence minimum untuk dibawa saat sidang" -Left 72 -Top 318 -Width 250 -Height 20 -FontName "Aptos Display" -FontSize 16 -FontColor $colors.White -Bold $true)
    [void](Add-TextBlock -Slide $slide -Text "- Screenshot login, katalog, dan WebMap`r`n- Output import dari UI dan CLI`r`n- File hasil download vector, raster, dan metadata`r`n- Query validasi table point, raster, dan metadata`r`n- Rekap pass rate, defect, dan UAT" -Left 72 -Top 348 -Width 784 -Height 62 -FontName "Aptos" -FontSize 11 -FontColor $colors.Sand)
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 17. Discussion points
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-LightBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Cloud -AccentOne $colors.Teal -AccentTwo $colors.Gold
    Add-EyebrowTitle -Slide $slide -Eyebrow "Discussion" -Title "Poin yang baik untuk dibahas dengan pembimbing" -Subtitle "Temuan seperti ini justru membantu menunjukkan bahwa Anda memahami kondisi sistem dan batas pengujian yang realistis." -EyebrowFill $colors.Teal -EyebrowFont $colors.White -TitleColor $colors.Navy -BodyColor $colors.Slate
    Add-Card -Slide $slide -Left 48 -Top 156 -Width 272 -Height 142 -Title "Metadata manual belum persisten" -Body "Form metadata saat ini memvalidasi input dan menampilkan pesan sukses, tetapi belum menyimpan input manual ke tabel metadata." -FillColor $colors.Card -TitleColor $colors.Danger -BodyColor $colors.Ink -AccentColor $colors.Danger
    Add-Card -Slide $slide -Left 344 -Top 156 -Width 272 -Height 142 -Title "Delete dataset masih placeholder" -Body "Endpoint delete pada area admin sudah ada, tetapi logika implementasinya masih TODO sehingga belum menjadi fitur final." -FillColor $colors.Card -TitleColor $colors.Danger -BodyColor $colors.Ink -AccentColor $colors.Danger
    Add-Card -Slide $slide -Left 640 -Top 156 -Width 272 -Height 142 -Title "Geocoder bergantung layanan eksternal" -Body "Pencarian lokasi di WebMap memakai layanan eksternal sehingga hasil uji bisa dipengaruhi konektivitas internet." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 196 -Top 326 -Width 272 -Height 120 -Title "PHPUnit belum langsung siap" -Body "Dependency test ada di composer, tetapi runner belum tersedia di vendor pada checkout aktif." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 492 -Top 326 -Width 272 -Height 120 -Title "Posisi di sidang" -Body "Sampaikan poin ini sebagai ruang pengembangan dan hasil evaluasi mutu, bukan sekadar kekurangan." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Line -BodyColor $colors.Slate -SlideWidth $slideWidth -SlideHeight $slideHeight
    $slideIndex++

    # 18. Closing
    $slide = $presentation.Slides.Add($slideIndex, 12)
    Add-DarkBackground -Slide $slide -W $slideWidth -H $slideHeight -BaseColor $colors.Navy -AccentOne $colors.Teal -AccentTwo $colors.Gold
    [void](Add-TextBlock -Slide $slide -Text "Kesimpulan" -Left 58 -Top 54 -Width 120 -Height 18 -FontName "Aptos" -FontSize 10 -FontColor $colors.PaleGold -Bold $true)
    [void](Add-TextBlock -Slide $slide -Text "Presentasi pengujian akan lebih kuat bila membahas metode, alur, pihak, evidence, dan gap sistem secara utuh." -Left 58 -Top 108 -Width 540 -Height 74 -FontName "Georgia" -FontSize 28 -FontColor $colors.White -Bold $true)
    Add-Card -Slide $slide -Left 58 -Top 222 -Width 248 -Height 112 -Title "Bawa deck ini ke pembimbing" -Body "Gunakan untuk menyamakan scope metode yang ingin Anda jalankan." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Gold
    Add-Card -Slide $slide -Left 332 -Top 222 -Width 248 -Height 112 -Title "Jalankan template pengujian" -Body "Eksekusi test case, isi form UAT, dan kumpulkan evidence." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Teal
    Add-Card -Slide $slide -Left 606 -Top 222 -Width 248 -Height 112 -Title "Ubah hasil jadi bab laporan" -Body "Ringkas pass rate, defect, UAT, dan rekomendasi pengembangan." -FillColor $colors.Card -TitleColor $colors.Navy -BodyColor $colors.Ink -AccentColor $colors.Terracotta
    $box = Add-ShapeBox -Slide $slide -Type 5 -Left 58 -Top 372 -Width 796 -Height 74 -FillColor $colors.PaleGold -FillTransparency 0.02
    $box.Adjustments.Item(1) = 0.05
    [void](Add-TextBlock -Slide $slide -Text "Output file: docs/testing/geoportal-testing-capstone-presentation.pptx dan .pdf`r`nGenerator: docs/testing/generate-geoportal-testing-presentation.ps1" -Left 82 -Top 392 -Width 650 -Height 34 -FontName "Aptos" -FontSize 12 -FontColor $colors.Navy)
    Add-Footer -Slide $slide -SlideNumber $slideIndex -LineColor $colors.Sand -BodyColor $colors.Sand -SlideWidth $slideWidth -SlideHeight $slideHeight

    $presentation.SaveAs($pptxPath, 24)
    try {
        $presentation.SaveAs($pdfPath, 32)
    } catch {
        Write-Warning ("PDF export gagal: " + $_.Exception.Message)
    }
} finally {
    if ($presentation -ne $null) {
        $presentation.Close()
    }
    if ($powerPoint -ne $null) {
        $powerPoint.Quit()
    }

    if ($presentation -ne $null) {
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($presentation)
    }
    if ($powerPoint -ne $null) {
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($powerPoint)
    }

    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}

Write-Output ("PPTX generated: " + $pptxPath)
if (Test-Path $pdfPath) {
    Write-Output ("PDF generated: " + $pdfPath)
}
