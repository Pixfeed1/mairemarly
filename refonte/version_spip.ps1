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

# --- 1. Miroir GitHub : les étiquettes de version ---------------------------
try {
  $tags = Invoke-RestMethod -Uri "https://api.github.com/repos/spip/spip/tags?per_page=100" `
                            -Headers @{ 'User-Agent' = 'verif-version-spip' } -TimeoutSec 30
  # on ne garde que les étiquettes numériques, triées en version
  $versions = $tags.name |
    Where-Object { $_ -match '^v?\d+\.\d+' } |
    ForEach-Object { $_ -replace '^v','' } |
    Sort-Object {
      $p = ($_ -split '[^\d]+' | Where-Object { $_ })
      [version]("{0}.{1}.{2}" -f ($p[0],0)[!$p[0]], ($p[1],0)[!$p[1]], ($p[2],0)[!$p[2]])
    } -Descending

  Write-Host "`n  Dix dernières versions publiées :" -ForegroundColor Green
  $versions | Select-Object -First 10 | ForEach-Object { Write-Host "    $_" }

  if ($versions) {
    Write-Host "`n  >>> Dernière version : $($versions[0])" -ForegroundColor Yellow
    # branches actives = familles de versions majeures.mineures presentes
    $branches = $versions | ForEach-Object { ($_ -split '\.')[0..1] -join '.' } |
                Select-Object -Unique | Select-Object -First 5
    Write-Host "  Branches présentes   : $($branches -join ', ')"
  }
} catch {
  Write-Host "  [!] Dépôt GitHub injoignable : $($_.Exception.Message)" -ForegroundColor Yellow
}

# --- 2. Dépôt officiel SPIP (source de vérité) ------------------------------
Write-Host "`n=== Vérification sur le dépôt officiel ===" -ForegroundColor Cyan
$git = Get-Command git -EA 0
if ($git) {
  Write-Host "  git ls-remote --tags https://git.spip.net/spip/spip.git" -ForegroundColor DarkGray
  $t = git ls-remote --tags https://git.spip.net/spip/spip.git 2>$null
  if ($t) {
    $t | ForEach-Object { ($_ -split 'refs/tags/')[-1] } |
      Where-Object { $_ -match '^\d+\.\d+' -and $_ -notmatch '\^\{\}$' } |
      Sort-Object -Descending | Select-Object -First 8 |
      ForEach-Object { Write-Host "    $_" }
  } else {
    Write-Host "  (dépôt officiel injoignable, on s'en tient au miroir GitHub)" -ForegroundColor DarkGray
  }
} else {
  Write-Host "  git absent : passe par le miroir GitHub ci-dessus." -ForegroundColor DarkGray
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
