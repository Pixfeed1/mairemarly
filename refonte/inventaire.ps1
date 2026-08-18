# inventaire.ps1 — Extrait un inventaire lisible depuis une capture aspiree
# ---------------------------------------------------------------------------
# Usage : .\inventaire.ps1 capture_marlygomont.free.fr
#
# Produit inventaire.md : rubriques, articles, auteurs, coordonnees mairie,
# documents. C'est la base de la maquette a proposer a la commune.
# ---------------------------------------------------------------------------

param([Parameter(Mandatory=$true)][string]$Dir)

$ErrorActionPreference = 'SilentlyContinue'

if (-not (Test-Path $Dir)) {
  Write-Error "Dossier introuvable : $Dir"
  exit 1
}
$Out = Join-Path $Dir 'inventaire.md'

# Retire les balises HTML et decode les entites (&#233; -> e accent, &amp; -> &)
function Remove-Html([string]$t) {
  $t = $t -replace '<[^>]+>', ''
  $t = [System.Net.WebUtility]::HtmlDecode($t)
  $t = $t -replace '\s+', ' '
  return $t.Trim()
}

# Recupere tous les titres <title> d'un dossier de pages
function Get-Titres([string]$dossier) {
  Get-ChildItem $dossier -Filter *.html -File -Recurse -EA 0 | ForEach-Object {
    $c = Get-Content $_.FullName -Raw -Encoding UTF8
    $m = [regex]::Match($c, '(?is)<title>(.*?)</title>')
    if ($m.Success) { Remove-Html $m.Groups[1].Value }
  } | Where-Object { $_ }
}

# Detecte le suffixe commun aux titres (ex : " - Site du village de X")
# afin de le retirer : SPIP ajoute le nom du site a chaque <title>.
function Get-SuffixeCommun($titres) {
  $compte = @{}
  foreach ($t in $titres) {
    $i = $t.LastIndexOf(' - ')
    if ($i -gt 0) {
      $s = $t.Substring($i)
      if ($compte.ContainsKey($s)) { $compte[$s]++ } else { $compte[$s] = 1 }
    }
  }
  if ($compte.Count -eq 0) { return '' }
  $meilleur = $compte.GetEnumerator() | Sort-Object Value -Descending | Select-Object -First 1
  # on ne retire le suffixe que s'il est vraiment recurrent
  if ($meilleur.Value -ge 3) { return $meilleur.Key }
  return ''
}

# Cherche un motif regex dans toutes les pages du miroir
function Find-Motif([string]$motif, [int]$max = 15) {
  Get-ChildItem (Join-Path $Dir 'miroir') -Filter *.html -File -Recurse -EA 0 |
    ForEach-Object {
      $c = Get-Content $_.FullName -Raw -Encoding UTF8
      [regex]::Matches($c, $motif) | ForEach-Object { $_.Value.Trim() }
    } | Sort-Object -Unique | Select-Object -First $max
}

$lignes = New-Object System.Collections.Generic.List[string]
function Add-L([string]$t) { $lignes.Add($t) }

# ---------------------------------------------------------------------------
# Collecte des titres bruts (rubriques + articles + flux RSS)
# ---------------------------------------------------------------------------
$titresRub = @(Get-Titres (Join-Path $Dir 'rubriques'))
$titresArt = @()
$rss = Join-Path $Dir 'backend.xml'
if (Test-Path $rss) {
  $c = Get-Content $rss -Raw -Encoding UTF8
  $titresArt += [regex]::Matches($c, '(?is)<title>(.*?)</title>') |
                ForEach-Object { Remove-Html $_.Groups[1].Value }
}
$titresArt += Get-Titres (Join-Path $Dir 'articles')
$titresArt += Get-Titres (Join-Path $Dir 'miroir')

# Suffixe " - Nom du site" calcule sur l'ensemble, puis retire partout
$suffixe = Get-SuffixeCommun (@($titresRub) + @($titresArt))
$nomSite = if ($suffixe) { $suffixe.Substring(3).Trim() } else { '' }
function Remove-Suffixe([string]$t) {
  if ($suffixe -and $t.EndsWith($suffixe)) { return $t.Substring(0, $t.Length - $suffixe.Length).Trim() }
  return $t
}

# Separe "Titre, par Untel" en deux : le titre et le ou les auteurs
$auteursDepuisTitres = @()
function Split-Auteur([string]$t) {
  $m = [regex]::Match($t, '(?i)^(.*?),\s*par\s+(.+)$')
  if ($m.Success) {
    $script:auteursDepuisTitres += ($m.Groups[2].Value -split '\s+et\s+|,\s*')
    return $m.Groups[1].Value.Trim()
  }
  return $t
}

# ---------------------------------------------------------------------------
# Rapport
# ---------------------------------------------------------------------------
Add-L "# Inventaire du contenu public - $Dir"
Add-L ""
if ($nomSite) { Add-L "**Site :** $nomSite" ; Add-L "" }
Add-L "_Genere automatiquement. A verifier / completer a la main avant la maquette._"
Add-L ""

# --- Rubriques -------------------------------------------------------------
Add-L "## Rubriques / menu"
Add-L ""
$rub = $titresRub | ForEach-Object { Remove-Suffixe $_ } |
       Where-Object { $_ -and $_ -ne $nomSite } | Sort-Object -Unique
if ($rub) { $rub | ForEach-Object { Add-L "- $_" } } else { Add-L "_(aucune rubrique detectee)_" }
Add-L ""

# --- Articles --------------------------------------------------------------
Add-L "## Articles"
Add-L ""
$art = $titresArt | ForEach-Object { Split-Auteur (Remove-Suffixe $_) } |
       Where-Object { $_ -and $_ -ne $nomSite -and $_.Length -ge 3 } | Sort-Object -Unique
if ($art) { $art | ForEach-Object { Add-L "- $_" } } else { Add-L "_(aucun article detecte)_" }
Add-L ""
Add-L "**Total : $($art.Count) articles distincts.**"
Add-L ""

# --- Auteurs ---------------------------------------------------------------
# Les NOMS d'auteurs sont publics (signature des articles). En revanche les
# comptes, emails et mots de passe vivent en base de donnees et ne sont PAS
# recuperables de l'exterieur : les comptes devront etre recrees a la main.
Add-L "## Auteurs detectes (noms publics uniquement)"
Add-L ""
$auteurs = @($auteursDepuisTitres)

# 1. Flux RSS SPIP : balise <dc:creator>
if (Test-Path $rss) {
  $cr = Get-Content $rss -Raw -Encoding UTF8
  $auteurs += [regex]::Matches($cr, '(?is)<dc:creator>(.*?)</dc:creator>') |
              ForEach-Object { Remove-Html $_.Groups[1].Value }
}

# 2. Pages HTML : blocs SPIP class="auteurs" / id="auteurs", et <meta name="author">
foreach ($sd in @('miroir', 'articles', 'rubriques')) {
  $chemin = Join-Path $Dir $sd
  if (-not (Test-Path $chemin)) { continue }
  Get-ChildItem $chemin -Filter *.html -File -Recurse -EA 0 | ForEach-Object {
    $c = Get-Content $_.FullName -Raw -Encoding UTF8
    $auteurs += [regex]::Matches($c, '(?is)<[^>]+(?:class|id)="[^"]*auteur[^"]*"[^>]*>(.*?)</') |
                ForEach-Object { Remove-Html $_.Groups[1].Value }
    $auteurs += [regex]::Matches($c, '(?i)<meta\s+name="author"\s+content="([^"]+)"') |
                ForEach-Object { $_.Groups[1].Value }
  }
}

# nettoyage : on enleve le "par " initial et on filtre le bruit
$auteurs = $auteurs |
           ForEach-Object { (Remove-Html $_) -replace '(?i)^\s*par\s+', '' } |
           ForEach-Object { $_.Trim(" .,-`t") } |
           Where-Object { $_ -and $_.Length -ge 3 -and $_.Length -le 50 -and $_ -notmatch '^\d+$' } |
           Sort-Object -Unique
if ($auteurs) { $auteurs | ForEach-Object { Add-L "- $_" } } else { Add-L "_(aucun auteur detecte)_" }
Add-L ""
Add-L "> Identifiants, emails et mots de passe ne sont PAS accessibles depuis"
Add-L "> l'exterieur. Les comptes seront a recreer dans le nouveau site."
Add-L ""

# --- Coordonnees -----------------------------------------------------------
Add-L "## Coordonnees probables de la mairie"
Add-L ""
Add-L '```'
Add-L "-- Telephones --"
# on exige un separateur (espace/point/tiret) pour eviter les faux positifs
# du type numeros de scan dans les noms de fichiers PDF
Find-Motif '0[1-9]([ .\-][0-9]{2}){4}' | ForEach-Object { Add-L $_ }
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
Add-L "**Total : $($docs.Count) documents.**"
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
