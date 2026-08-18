# inventaire.ps1 — Extrait un inventaire lisible depuis une capture aspiree
# ---------------------------------------------------------------------------
# Usage : .\inventaire.ps1 capture_marlygomont.free.fr
#
# Produit inventaire.md : rubriques, titres d'articles, coordonnees mairie,
# documents. C'est la base de la maquette a proposer a la commune.
# ---------------------------------------------------------------------------

param([Parameter(Mandatory=$true)][string]$Dir)

$ErrorActionPreference = 'SilentlyContinue'

if (-not (Test-Path $Dir)) {
  Write-Error "Dossier introuvable : $Dir"
  exit 1
}
$Out = Join-Path $Dir 'inventaire.md'

# Retire les balises HTML et decode les entites courantes
function Remove-Html([string]$t) {
  $t = $t -replace '<[^>]+>', ''
  $t = $t -replace '&nbsp;', ' ' -replace '&amp;', '&' -replace '&#39;', "'"
  $t = $t -replace '&quot;', '"' -replace '&eacute;', 'e' -replace '&egrave;', 'e'
  return $t.Trim()
}

# Recupere tous les titres <title> d'un dossier de pages
function Get-Titres([string]$dossier) {
  Get-ChildItem $dossier -Filter *.html -File -EA 0 | ForEach-Object {
    $c = Get-Content $_.FullName -Raw -Encoding UTF8
    $m = [regex]::Match($c, '(?is)<title>(.*?)</title>')
    if ($m.Success) { Remove-Html $m.Groups[1].Value }
  } | Where-Object { $_ } | Sort-Object -Unique
}

# Cherche un motif regex dans toutes les pages du miroir
function Find-Motif([string]$motif, [int]$max = 12) {
  Get-ChildItem (Join-Path $Dir 'miroir') -Filter *.html -File -Recurse -EA 0 |
    ForEach-Object {
      $c = Get-Content $_.FullName -Raw -Encoding UTF8
      [regex]::Matches($c, $motif) | ForEach-Object { $_.Value.Trim() }
    } | Sort-Object -Unique | Select-Object -First $max
}

$lignes = New-Object System.Collections.Generic.List[string]
function Add-L([string]$t) { $lignes.Add($t) }

Add-L "# Inventaire du contenu public - $Dir"
Add-L ""
Add-L "_Genere automatiquement. A verifier / completer a la main avant la maquette._"
Add-L ""

# --- Rubriques -------------------------------------------------------------
Add-L "## Rubriques / menu"
Add-L ""
$rub = Get-Titres (Join-Path $Dir 'rubriques')
if ($rub) { $rub | ForEach-Object { Add-L "- $_" } } else { Add-L "_(aucune rubrique detectee)_" }
Add-L ""

# --- Articles --------------------------------------------------------------
Add-L "## Articles"
Add-L ""
$titresArt = @()
# depuis le flux RSS si present
$rss = Join-Path $Dir 'backend.xml'
if (Test-Path $rss) {
  $c = Get-Content $rss -Raw -Encoding UTF8
  $titresArt += [regex]::Matches($c, '(?is)<title>(.*?)</title>') |
                ForEach-Object { Remove-Html $_.Groups[1].Value }
}
# depuis les articles aspires par ID
$titresArt += Get-Titres (Join-Path $Dir 'articles')
$titresArt = $titresArt | Where-Object { $_ } | Sort-Object -Unique
if ($titresArt) { $titresArt | ForEach-Object { Add-L "- $_" } } else { Add-L "_(aucun article detecte)_" }
Add-L ""

# --- Coordonnees -----------------------------------------------------------
Add-L "## Coordonnees probables de la mairie"
Add-L ""
Add-L '```'
Add-L "-- Telephones --"
Find-Motif '0[1-9]([ .\-]?[0-9]{2}){4}' | ForEach-Object { Add-L $_ }
Add-L ""
Add-L "-- Emails --"
Find-Motif '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}' | ForEach-Object { Add-L $_ }
Add-L ""
Add-L "-- Code postal + ville --"
Find-Motif '0[0-9]{4}\s+[A-Z][A-Za-z\-]+' | ForEach-Object { Add-L $_ }
Add-L '```'
Add-L ""

# --- Documents -------------------------------------------------------------
Add-L "## Documents telechargeables"
Add-L ""
$docs = Get-ChildItem (Join-Path $Dir 'fichiers') -File -EA 0 |
        Where-Object { $_.Extension -match '(?i)\.(pdf|doc|docx|odt|xls|xlsx)$' }
if ($docs) { $docs | ForEach-Object { Add-L "- $($_.Name)" } } else { Add-L "_(aucun document)_" }
Add-L ""

# --- Images ----------------------------------------------------------------
Add-L "## Banque visuelle recuperee"
Add-L ""
$imgs = Get-ChildItem (Join-Path $Dir 'fichiers') -File -EA 0 |
        Where-Object { $_.Extension -match '(?i)\.(jpg|jpeg|png|gif|webp|svg)$' }
Add-L "Total : $($imgs.Count) images dans $Dir\fichiers"

Set-Content -Path $Out -Value $lignes -Encoding UTF8
Write-Host "Inventaire ecrit dans : $Out" -ForegroundColor Green
Write-Host "----------------------------------------"
Get-Content $Out
