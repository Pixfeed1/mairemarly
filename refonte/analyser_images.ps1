# analyser_images.ps1 — Mesure les images recuperees
# ---------------------------------------------------------------------------
# Le choix du design depend directement de la qualite des photos disponibles :
#   - des images >= 1600 px de large permettent un grand visuel plein ecran
#   - en dessous de 1000 px, il faut un design a base de cartes et d'aplats
#
# Usage : .\analyser_images.ps1 capture_marlygomont.free.fr
# ---------------------------------------------------------------------------

param([Parameter(Mandatory=$true)][string]$Dir)

$ErrorActionPreference = 'SilentlyContinue'
Add-Type -AssemblyName System.Drawing

$dossier = Join-Path $Dir 'fichiers'
if (-not (Test-Path $dossier)) {
  Write-Error "Dossier introuvable : $dossier"
  exit 1
}

$images = Get-ChildItem $dossier -File -Recurse |
          Where-Object { $_.Extension -match '(?i)\.(jpg|jpeg|png|gif|webp)$' } |
          ForEach-Object {
            try {
              $img = [System.Drawing.Image]::FromFile($_.FullName)
              $o = [PSCustomObject]@{
                Nom     = $_.Name
                Largeur = $img.Width
                Hauteur = $img.Height
                Ko      = [int]($_.Length / 1KB)
              }
              $img.Dispose()
              $o
            } catch {}
          }

if (-not $images) { Write-Host "Aucune image lisible." -ForegroundColor Yellow; exit }

Write-Host ""
Write-Host "=== Les 20 plus grandes images ===" -ForegroundColor Cyan
$images | Sort-Object Largeur -Descending | Select-Object -First 20 | Format-Table -AutoSize

# Repartition par taille : c'est ce chiffre qui tranche le choix du design
$grandes = @($images | Where-Object { $_.Largeur -ge 1600 }).Count
$moyennes = @($images | Where-Object { $_.Largeur -ge 1000 -and $_.Largeur -lt 1600 }).Count
$petites = @($images | Where-Object { $_.Largeur -lt 1000 }).Count

Write-Host "=== Verdict ===" -ForegroundColor Cyan
Write-Host "  Total images          : $($images.Count)"
Write-Host "  >= 1600 px (hero OK)  : $grandes"
Write-Host "  1000-1599 px (cartes) : $moyennes"
Write-Host "  <  1000 px (vignettes): $petites"
Write-Host ""
if ($grandes -ge 5) {
  Write-Host "  -> Design a grandes photos possible." -ForegroundColor Green
} elseif ($moyennes -ge 8) {
  Write-Host "  -> Design a cartes illustrees. Hero photo a reprendre." -ForegroundColor Yellow
} else {
  Write-Host "  -> Photos trop petites : partir sur un design graphique" -ForegroundColor Yellow
  Write-Host "     (aplats de couleur, typo forte), et proposer une seance" -ForegroundColor Yellow
  Write-Host "     photo a la commune comme prestation complementaire." -ForegroundColor Yellow
}
Write-Host ""
