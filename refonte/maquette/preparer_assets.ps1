# preparer_assets.ps1 — Prépare les ressources de la maquette
# ---------------------------------------------------------------------------
# Télécharge, dans le dossier de la maquette :
#   1. Remix Icon (Apache 2.0)  -> vendor/    police + CSS, auto-hébergés
#   2. des photographies libres -> img/       via Lorem Picsum (photos Unsplash)
#
# Auto-héberger plutôt que passer par un CDN n'est pas un détail : un CDN
# transmet l'adresse IP de chaque visiteur à un tiers, ce qu'une collectivité
# ne peut pas faire sans base légale (RGPD).
#
# Usage :  .\preparer_assets.ps1
# ---------------------------------------------------------------------------

$ErrorActionPreference = 'SilentlyContinue'
$ProgressPreference    = 'SilentlyContinue'
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

$racine = $PSScriptRoot
if (-not $racine) { $racine = (Get-Location).Path }
New-Item -ItemType Directory -Force -Path "$racine\vendor","$racine\img" | Out-Null

function Recupere($url, $dest, $libelle) {
  try {
    Invoke-WebRequest -Uri $url -OutFile $dest -TimeoutSec 60 -UseBasicParsing
    $ko = [int]((Get-Item $dest).Length / 1KB)
    Write-Host ("  OK   {0,-34} {1} Ko" -f $libelle, $ko) -ForegroundColor Green
    return $true
  } catch {
    Write-Host ("  ECHEC {0,-33} {1}" -f $libelle, $_.Exception.Message) -ForegroundColor Yellow
    return $false
  }
}

# --- 1. Remix Icon ----------------------------------------------------------
Write-Host "`n=== Remix Icon (Apache 2.0) ===" -ForegroundColor Cyan
$base = "https://cdn.jsdelivr.net/npm/remixicon/fonts"
Recupere "$base/remixicon.css"   "$racine\vendor\remixicon.css"   "remixicon.css"   | Out-Null
Recupere "$base/remixicon.woff2" "$racine\vendor\remixicon.woff2" "remixicon.woff2" | Out-Null

# le CSS pointe vers les polices par des chemins relatifs : on les ramène à plat
$cssPath = "$racine\vendor\remixicon.css"
if (Test-Path $cssPath) {
  (Get-Content $cssPath -Raw) -replace 'url\([^)]*remixicon\.woff2[^)]*\)', 'url("remixicon.woff2")' |
    Set-Content $cssPath -Encoding UTF8
  Write-Host "  Chemins de police reecrits en local." -ForegroundColor DarkGray
}

# --- 2. Photographies -------------------------------------------------------
# Lorem Picsum sert de vraies photographies Unsplash, sans clé d'API.
# Chaque identifiant donne toujours la même image : la maquette est stable.
# >>> Pour la version livrée au client, remplace ces fichiers par des photos
#     choisies sur Pexels / Unsplash, ou par tes propres prises de vue.
Write-Host "`n=== Photographies (Lorem Picsum / Unsplash) ===" -ForegroundColor Cyan
$photos = @(
  @{ f='hero.jpg';         id=1018; w=1920; h=900; d='héros — paysage' },
  @{ f='actu-forum.jpg';   id=225;  w=1200; h=700; d='forum des artisans' },
  @{ f='actu-conseil.jpg'; id=1076; w=800;  h=500; d='conseil municipal' },
  @{ f='actu-concert.jpg'; id=452;  w=800;  h=500; d='concert harmonie' },
  @{ f='actu-mairie.jpg';  id=1071; w=800;  h=500; d='secrétariat' },
  @{ f='evt-fete.jpg';     id=431;  w=900;  h=720; d='fête du village' },
  @{ f='bulletin.jpg';     id=24;   w=500;  h=680; d='bulletin municipal' }
)
foreach ($p in $photos) {
  $url = "https://picsum.photos/id/$($p.id)/$($p.w)/$($p.h)"
  Recupere $url "$racine\img\$($p.f)" $p.d | Out-Null
  Start-Sleep -Milliseconds 200
}

# --- Bilan ------------------------------------------------------------------
$nv = (Get-ChildItem "$racine\vendor" -File -EA 0).Count
$ni = (Get-ChildItem "$racine\img"    -File -EA 0).Count
Write-Host "`n============================================================" -ForegroundColor Yellow
Write-Host "  vendor/ : $nv fichiers    img/ : $ni photos"
Write-Host "  Ouvre index.html dans ton navigateur."
Write-Host "============================================================`n" -ForegroundColor Yellow
if ($ni -eq 0) {
  Write-Host "Aucune photo : la maquette affichera ses degrades de secours." -ForegroundColor Yellow
}
