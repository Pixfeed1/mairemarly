# Démarrage du projet — Mairie de Marly-Gomont

Document de lancement, à utiliser dès la signature. Les accès sont le chemin
critique : une commune met souvent plusieurs semaines à retrouver ses
identifiants. Cette demande part **en premier**, le reste avance en parallèle.

---

## 1. Accès à demander (urgent)

| Élément | Auprès de qui | Pourquoi c'est bloquant |
|---|---|---|
| **Accès FTP / SFTP** du site actuel | Mairie ou son prestataire | Récupérer `IMG/` (photos pleine résolution) et les documents |
| **Base de données MySQL** (ou un export `.sql`) | Idem | Contient articles, rubriques, auteurs, dates, brouillons |
| **Compte administrateur SPIP** | Idem | Solution de repli si la base est inaccessible |
| **Gestion du nom de domaine** | Mairie | Pour brancher le nouveau site le jour J |
| **Contrat d'hébergement actuel** | Mairie | Savoir ce qui est payé, jusqu'à quand, et si on migre |

> Le site est hébergé chez Free. Il faut identifier **qui détient le compte** :
> souvent un ancien élu ou un bénévole, parfois plus joignable. À traiter tôt,
> c'est un point de blocage classique.

### Si personne ne retrouve les accès

Ce n'est pas un échec du projet : la capture déjà réalisée
(`capture_marlygomont.free.fr`) contient 174 pages, 143 fichiers dont 53 PDF,
85 images et l'inventaire complet. On repart de là, en ressaisissant. Il faut
simplement l'annoncer : le délai et le budget de reprise de contenu changent.

---

## 2. Éléments à réclamer à la mairie

**Identité**
- [ ] Logo ou blason de la commune, en vectoriel si possible (`.ai`, `.eps`, `.svg`)
- [ ] Couleurs officielles si elles existent

**Contenus institutionnels**
- [ ] Liste des élus : nom, fonction, éventuellement photo
- [ ] Horaires d'ouverture du secrétariat, y compris périodes de vacances
- [ ] Coordonnées à jour (l'adresse `@wanadoo.fr` est-elle toujours active ?)
- [ ] Les procès-verbaux du conseil **en version bureautique** si elles existent :
      les PDF actuels sont des scans, donc ni cherchables ni accessibles

**Photographies**
- [ ] Photos récentes du village, de la mairie, des événements
- [ ] À défaut : prévoir une séance photo — c'est une prestation en plus, et
      l'argument se défend seul (« les photos du site datent de 2008 »)

**Associations et commerces**
- [ ] Coordonnées à jour de l'ASMG, de l'harmonie municipale, du comité
      d'animation, du secteur paroissial
- [ ] Liste des commerces et services en activité

---

## 3. Questions à poser au maire

1. **Qui publiera** sur le site une fois livré ? Une seule personne, ou plusieurs ?
   *Détermine le nombre de comptes et le volume de formation.*
2. **Quel nom de domaine ?** `marly-gomont.fr` est-il libre, ou déjà réservé ?
   *Quitter `free.fr` fait partie de la valeur livrée.*
3. **Y a-t-il une échéance ?** Élections, inauguration, fête du village.
4. **Le site doit-il rester en ligne pendant les travaux ?**
   *Oui dans la plupart des cas : on prépare sur une adresse de recette.*
5. **Qui valide le design ?** Le maire seul, ou une commission ?
   *Une commission multiplie les allers-retours : à cadrer dans le devis.*

---

## 4. Points à verrouiller dans le contrat

- **Accessibilité (RGAA)** : obligatoire pour une collectivité. Préciser le
  niveau visé et qui rédige la déclaration d'accessibilité.
- **RGPD** : bandeau cookies, mentions légales, registre des traitements.
  Auto-héberger polices et icônes, pas de CDN.
- **Hébergement** : qui paie, qui administre, qui sauvegarde.
- **Formation** : compter une session pour le secrétariat plus un mémo d'une
  page. Sans cela le site se figera à nouveau dans deux ans, exactement comme
  le précédent.
- **Maintenance** : les mises à jour de sécurité de SPIP. Contrat annuel, ou
  transfert de responsabilité à la commune par écrit.

---

## 5. Ce qui avance en parallèle, sans attendre les accès

1. Environnement local et installation de SPIP 4.4.9
2. Arborescence des rubriques (validée : Mairie, Vie du village,
   Associations et commerces, Découvrir)
3. Développement du thème
4. Page de recherche dans les comptes rendus

Seule la **reprise du contenu réel** dépend des accès.
