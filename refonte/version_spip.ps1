# version_spip.ps1 — Quelle est la dernière version de SPIP ?
# ---------------------------------------------------------------------------
# Interroge le dépôt officiel pour lister les versions publiées, plutôt que
# de se fier à une page de documentation qui peut être en retard.
#
# Usage :  .\version_spip.ps1
# ---------------------------------------------------------------------------

$ErrorActionPreference = 'SilentlyContinue'
$ProgressPreference    = 'SilentlyContinue'
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

Write-Host "`n=== Versions publiées de SPIP ===" -ForegroundColor Cyan

# --- Dépôt officiel SPIP : la source de vérité ------------------------------
$git = Get-Command git -EA 0
if (-not $git) {
  Write-Host "  [!] git est absent. Installe-le, ou consulte spip.net directement." -ForegroundColor Yellow
} else {
  $brut = git ls-remote --tags https://git.spip.net/spip/spip.git 2>$null
  if (-not $brut) {
    Write-Host "  [!] Dépôt injoignable. Vérifie ta connexion." -ForegroundColor Yellow
  } else {
    $toutes = $brut |
      ForEach-Object { ($_ -split 'refs/tags/')[-1] } |
      Where-Object { $_ -match '^\d+\.\d+' -and $_ -notmatch '\^\{\}$' }

    # une version stable ne porte ni beta, ni rc, ni alpha, ni dev
    $stables = $toutes | Where-Object { $_ -notmatch '(?i)alpha|beta|rc|dev' }
    $essais  = $toutes | Where-Object { $_ -match  '(?i)alpha|beta|rc' }

    Write-Host "`n  Versions stables (huit dernières) :" -ForegroundColor Green
    $stables | Sort-Object -Descending | Select-Object -First 8 | ForEach-Object { Write-Host "    $_" }

    $derniere = ($stables | Sort-Object -Descending | Select-Object -First 1)
    Write-Host "`n  >>> DERNIÈRE STABLE : $derniere" -ForegroundColor Yellow

    if ($essais) {
      Write-Host "`n  Versions d'essai (à ne PAS mettre en production) :" -ForegroundColor DarkGray
      $essais | Sort-Object -Descending | Select-Object -First 3 | ForEach-Object { Write-Host "    $_" -ForegroundColor DarkGray }
    }
  }
}

Write-Host @"

=== À vérifier avant de choisir ===
  1. La version de PHP exigée par la branche retenue
     -> elle conditionne le choix de l'hébergeur, c'est bloquant.
  2. La branche encore maintenue en correctifs de sécurité
     -> pour un site public, c'est un critère plus important que la nouveauté.
  3. La compatibilité des plugins nécessaires avec cette branche.

  Pages de référence :
    https://www.spip.net/fr_article1489.html   (téléchargement)
    https://blog.spip.net/                     (annonces de version)
    https://contrib.spip.net/                  (plugins)

"@ -ForegroundColor Cyan
