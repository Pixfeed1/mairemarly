# aspirer_site.ps1 — Aspiration NON-DESTRUCTIVE du contenu public d'un site SPIP
# ---------------------------------------------------------------------------
# Version PowerShell native : AUCUNE dépendance (pas besoin de wget ni de WSL).
#
# But : récupérer, depuis l'extérieur (sans accès admin/FTP/BDD), tout le
#       contenu public d'un vieux site SPIP (rubriques, articles, images,
#       documents PDF) afin de reconstruire une version moderne à PROPOSER
#       à la commune.
#
# Ce script ne fait que LIRE des pages publiques, comme un navigateur.
# Il n'exploite aucune faille et ne modifie rien sur la cible.
#
# Usage (PowerShell) :
#   .\aspirer_site.ps1 http://marlygomont.free.fr
#
# Si Windows bloque l'exécution des scripts, lance d'abord :
#   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
# ---------------------------------------------------------------------------

param(
  [Parameter(Mandatory=$true)][string]$Base,
  [int]$MaxPages = 400
)

$ErrorActionPreference = 'SilentlyContinue'
$ProgressPreference    = 'SilentlyContinue'   # accélère fortement Invoke-WebRequest

# TLS moderne (certains vieux serveurs / archive.org l'exigent)
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

$Base     = $Base.TrimEnd('/')
$BaseUri  = [Uri]"$Base/"
$HostName = $BaseUri.Host
$Out      = "capture_$HostName"
$UA       = "Mozilla/5.0 (compatible; refonte-mairie; aspiration contenu public)"

foreach ($d in @($Out, "$Out\miroir", "$Out\articles", "$Out\rubriques", "$Out\fichiers")) {
  New-Item -ItemType Directory -Force -Path $d | Out-Null
}
Write-Host "==> Capture dans : $Out\" -ForegroundColor Cyan

# --- petits utilitaires ----------------------------------------------------

# Transforme une URL en nom de fichier valide sous Windows
function Get-SafeName([string]$u, [string]$forceExt = "") {
  $p = $u -replace '^https?://', ''
  $p = $p -replace '[\\/:\*\?"<>\|&=#]', '_'
  if ($p.Length -gt 110) { $p = $p.Substring(0, 110) }
  if ($forceExt -and ($p -notmatch [regex]::Escape($forceExt) + '$')) { $p = "$p$forceExt" }
  return $p
}

# Télécharge une page et renvoie son contenu texte (ou $null)
function Get-Page([string]$u) {
  try {
    $r = Invoke-WebRequest -Uri $u -UserAgent $UA -TimeoutSec 30 -UseBasicParsing
    return $r.Content
  } catch { return $null }
}

# --- 1) Miroir du site : parcours en largeur des liens internes ------------
Write-Host "==> [1/5] Miroir du site (parcours des pages)..." -ForegroundColor Cyan

$queue  = New-Object System.Collections.Queue
$seen   = New-Object 'System.Collections.Generic.HashSet[string]'
$assets = New-Object 'System.Collections.Generic.HashSet[string]'
$queue.Enqueue("$Base/") | Out-Null
$nbPages = 0

# extensions considérées comme fichiers à télécharger (pas à explorer)
$extFichier = '\.(jpg|jpeg|png|gif|bmp|svg|webp|ico|css|js|pdf|doc|docx|odt|xls|xlsx|zip|mp3|mp4)$'

while ($queue.Count -gt 0 -and $nbPages -lt $MaxPages) {
  $url = $queue.Dequeue()
  if ($seen.Contains($url)) { continue }
  [void]$seen.Add($url)

  $html = Get-Page $url
  if (-not $html) { continue }
  $nbPages++

  # on ignore les pages d'action/forum/calendrier (bruit, et on reste poli)
  if ($url -match '(\?|&)(action|var_mode|calendrier|forum)') { continue }

  Set-Content -Path (Join-Path "$Out\miroir" (Get-SafeName $url '.html')) `
              -Value $html -Encoding UTF8
  Write-Host ("    [{0,3}] {1}" -f $nbPages, $url)

  # extraction des liens href= et src=
  $liens = [regex]::Matches($html, '(?:href|src)\s*=\s*["'']([^"''>]+)["'']') |
           ForEach-Object { $_.Groups[1].Value }

  foreach ($l in $liens) {
    if ($l -match '^(mailto:|javascript:|tel:|#)') { continue }
    try { $abs = [Uri]::new([Uri]$url, $l).AbsoluteUri } catch { continue }
    $abs = $abs.Split('#')[0]
    # on ne sort jamais du domaine cible
    try { if (([Uri]$abs).Host -ne $HostName) { continue } } catch { continue }

    if ($abs -match $extFichier) { [void]$assets.Add($abs) }
    elseif (-not $seen.Contains($abs))  { $queue.Enqueue($abs) | Out-Null }
  }
  Start-Sleep -Milliseconds 300   # poli : on ne martèle pas le serveur
}
Write-Host "    $nbPages pages HTML enregistrees." -ForegroundColor Green

# --- 2) Téléchargement des images / CSS / PDF ------------------------------
Write-Host "==> [2/5] Telechargement des fichiers (images, PDF, CSS)..." -ForegroundColor Cyan
$nbFic = 0
foreach ($a in $assets) {
  $dest = Join-Path "$Out\fichiers" (Get-SafeName $a)
  if (Test-Path $dest) { continue }
  try {
    Invoke-WebRequest -Uri $a -OutFile $dest -UserAgent $UA -TimeoutSec 30 -UseBasicParsing
    $nbFic++
  } catch {}
  Start-Sleep -Milliseconds 150
}
Write-Host "    $nbFic fichiers telecharges." -ForegroundColor Green

# --- 3) Flux backend SPIP (RSS = liste structuree des articles) ------------
Write-Host "==> [3/5] Recherche du flux backend SPIP (RSS)..." -ForegroundColor Cyan
foreach ($ep in @('spip.php?page=backend', 'backend.php3', 'backend.php')) {
  $rss = Get-Page "$Base/$ep"
  if ($rss -and $rss -match '<rss|<item>') {
    Set-Content -Path "$Out\backend.xml" -Value $rss -Encoding UTF8
    Write-Host "    Trouve : $Base/$ep" -ForegroundColor Green
    break
  }
}

# --- 4) Balayage des articles / rubriques par identifiant ------------------
# Les vieux SPIP exposent article.php3?id_article=N (ou spip.php?articleN).
function Invoke-Balayage([string]$type, [string]$motif, [string]$dossier, [int]$maxVide) {
  Write-Host "    Balayage des $type ($motif)..." -ForegroundColor DarkGray
  $vide = 0; $n = 1; $trouves = 0
  while ($vide -lt $maxVide -and $n -le 500) {
    $page = Get-Page "$Base/$motif$n"
    if ($page -and $page -match '<title>[^<]{3,}</title>' -and
        $page -notmatch '(?i)404|introuvable|n.existe pas') {
      Set-Content -Path (Join-Path $dossier "${type}_$n.html") -Value $page -Encoding UTF8
      $vide = 0; $trouves++
    } else { $vide++ }
    $n++
    Start-Sleep -Milliseconds 250
  }
  return $trouves
}

Write-Host "==> [4/5] Articles et rubriques par identifiant..." -ForegroundColor Cyan
$nbArt = Invoke-Balayage 'article'  'spip.php?article'  "$Out\articles"  15
$null  = Invoke-Balayage 'rubrique' 'spip.php?rubrique' "$Out\rubriques" 8
# Variante ancienne (.php3) si la syntaxe moderne n'a rien donne
if ($nbArt -eq 0) {
  Invoke-Balayage 'article'  'article.php3?id_article='   "$Out\articles"  15 | Out-Null
  Invoke-Balayage 'rubrique' 'rubrique.php3?id_rubrique=' "$Out\rubriques" 8  | Out-Null
}
Write-Host "    $((Get-ChildItem "$Out\articles" -File).Count) articles recuperes." -ForegroundColor Green

# --- 5) Filet de securite : Wayback Machine (archive.org) ------------------
Write-Host "==> [5/5] Interrogation de la Wayback Machine..." -ForegroundColor Cyan
$wb = Get-Page "http://web.archive.org/cdx/search/cdx?url=$HostName*&output=text&fl=original&collapse=urlkey&limit=2000"
if ($wb) {
  Set-Content -Path "$Out\wayback.txt" -Value $wb -Encoding UTF8
  Write-Host "    $(($wb -split "`n").Count) URLs archivees listees." -ForegroundColor Green
}

# --- Bilan -----------------------------------------------------------------
Write-Host ""
Write-Host "============================================================" -ForegroundColor Yellow
Write-Host " Capture terminee : $Out\"
Write-Host "   Pages HTML      : $((Get-ChildItem "$Out\miroir"   -File -EA 0).Count)"
Write-Host "   Fichiers        : $((Get-ChildItem "$Out\fichiers" -File -EA 0).Count)"
Write-Host "   Articles par ID : $((Get-ChildItem "$Out\articles" -File -EA 0).Count)"
Write-Host "============================================================" -ForegroundColor Yellow
Write-Host "Etape suivante :  .\inventaire.ps1 $Out"
