param([Parameter(Mandatory = $true)][string]$PayloadPath)

$ErrorActionPreference = 'Stop'
$payload = Get-Content -LiteralPath $PayloadPath -Raw -Encoding UTF8 | ConvertFrom-Json
Copy-Item -LiteralPath $payload.template_path -Destination $payload.working_docx_path -Force
Unblock-File -LiteralPath $payload.working_docx_path

$word = $null
$document = $null

function Set-ParagraphText([object]$paragraph, [string]$text) {
    $paragraph.Range.Text = $text + "`r"
}

function Set-CellText([object]$cell, [string]$text) {
    $range = $cell.Range
    $range.End = $range.End - 1
    $range.Text = $text
}

function Replace-All([object]$document, [string]$findText, [string]$replaceText) {
    $range = $document.Content
    $find = $range.Find
    $find.ClearFormatting()
    $find.Replacement.ClearFormatting()
    $find.Text = $findText
    $find.Replacement.Text = $replaceText
    $find.Forward = $true
    $find.Wrap = 1
    $find.Format = $false
    $find.MatchCase = $true
    $find.Execute($findText, $true, $true, $false, $false, $false, $true, 1, $false, $replaceText, 2) | Out-Null
}

try {
    $stage = 'starting Word'
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $stage = 'opening template'
    $document = $word.Documents.Open($payload.working_docx_path, $false, $false, $false)

    $stage = 'filling body paragraph'
    if ($payload.blank_mode) {
        # Keep the paragraph visible for now so Word preserves its exact pagination.
    } else {
        Replace-All $document 'XXXXX - XXXXXXXXXXXXXXXXXXX' $payload.company_name
        Replace-All $document 'XX de XXXXXXXX' $payload.start_date_text
    }

    $stage = 'filling student table'
    $table = $document.Tables.Item(1)
    Set-CellText $table.Cell(2, 1) $payload.student_name
    Set-CellText $table.Cell(2, 2) $payload.course_name
    Set-CellText $table.Cell(3, 1) ''
    Set-CellText $table.Cell(3, 2) ''

    $stage = 'filling signatures'
    if ($payload.blank_mode) {
        Replace-All $document 'xxxxxx' '      '
        Replace-All $document 'Gestor(a) Geral do IEMA Pleno XXXXXX' '                                      '
        Replace-All $document 'Nome' '    '
        Replace-All $document 'Cargo' '     '
    } else {
        Replace-All $document 'xxxxxx' $payload.manager_name
        Replace-All $document 'Gestor(a) Geral do IEMA Pleno XXXXXX' $payload.manager_title
        Replace-All $document 'Sr(a). ' ("Sr(a). " + $payload.responsible_name)
        Replace-All $document 'Nome' $payload.responsible_name
        Replace-All $document 'Cargo' $payload.responsible_role
    }

    $stage = 'saving Word document'
    $document.Save()
    $stage = 'exporting PDF'
    $document.ExportAsFixedFormat($payload.output_pdf_path, 17)
    $document.Close($false)
    $document = $null
} catch {
    throw "Generation failed while $stage`: $($_.Exception.Message)"
} finally {
    if ($null -ne $document) { $document.Close($false) }
    if ($null -ne $word) { $word.Quit() }
}
