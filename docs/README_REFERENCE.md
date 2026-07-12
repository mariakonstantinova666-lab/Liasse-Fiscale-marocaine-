# Référence fonctionnelle des fichiers Excel corrigés

## Objet

Ce document décrit l'analyse des fichiers Excel de référence présents dans `docs/` pour l'application de liasse fiscale marocaine.

Il sert uniquement de documentation fonctionnelle : il ne modifie pas les calculs, le backend Laravel, les imports, les contrôleurs, les modèles, les migrations ni la base de données.

## Fichiers analysés

Le dossier `docs/` contient deux classeurs de référence corrigés :

| Fichier | Rôle |
|---|---|
| `D3Simpl2_N..xlsm` | Cas avec une seule balance : exercice courant N. |
| `D3Simpl2_N_N-1.xlsm` | Cas complet avec balance N et balance N-1 corrigée. |

Les fichiers temporaires Excel `~$...` ne doivent pas être utilisés comme référence.

> Remarque : le fichier N présent dans le dossier porte actuellement le nom `D3Simpl2_N..xlsm`, avec deux points avant l'extension.

## Structure des classeurs

Les deux classeurs contiennent les mêmes feuilles principales de liasse :

| Feuille | Rôle fonctionnel |
|---|---|
| `PagePrincipale` | Informations société et période fiscale. |
| `A (2)` | Balance de l'exercice courant N. |
| `BalanceN` | Feuille technique normalisée de la balance N. |
| `BalanceN-1` | Feuille technique normalisée de la balance N-1. Vide dans le fichier N seul, alimentée dans le fichier N/N-1. |
| `Balance N-1 corrigée` | Feuille source de la balance N-1 corrigée, présente dans `D3Simpl2_N_N-1.xlsm`. |
| `T00` à `T26` | Tableaux de la liasse fiscale. |
| `Verifications` | Contrôles de cohérence du modèle Excel. |
| `Complements`, `Complements2`, `Erreurs_XML` | Feuilles techniques liées au modèle DGI/EDI. |

Dans le fichier `D3Simpl2_N_N-1.xlsm`, la correction importante est la présence d'une vraie balance précédente dans `Balance N-1 corrigée`, reprise dans la feuille technique `BalanceN-1`.

## Balances de référence

### Balance N

La balance N se trouve dans la feuille `A (2)`.

- Nombre de lignes comptables : 61.
- Total débit : `2 214 600,03`.
- Total crédit : `2 214 600,03`.
- La balance est équilibrée.

### Balance N-1 corrigée

La balance N-1 corrigée se trouve dans la feuille `Balance N-1 corrigée`.

- Nombre de lignes comptables : 61.
- Total débit : `2 023 322,00`.
- Total crédit : `2 023 322,00`.
- La balance est équilibrée.
- La feuille technique `BalanceN-1` contient les mêmes comptes et montants que la feuille source corrigée.

## Différence principale apportée par la correction N-1

La correction ne change pas la structure des tableaux : elle corrige la source des valeurs de l'exercice précédent.

La colonne `Exercice précédent`, les soldes de début d'exercice et les colonnes de stock initial doivent maintenant être alimentés par la balance N-1 corrigée.

Il ne faut donc pas :

- laisser les valeurs N-1 vides lorsqu'une balance N-1 existe ;
- recopier les valeurs N dans la colonne précédente ;
- calculer l'exercice précédent à partir de constantes ;
- mélanger des comptes N avec des comptes N-1 dans une même colonne.

La règle centrale est :

```text
Exercice N          = calcul depuis la balance N
Exercice précédent = même calcul, mêmes racines, même sens, depuis la balance N-1 corrigée
```

## Fonction de solde utilisée par le modèle Excel

Les formules du classeur utilisent des fonctions de type :

- `SoldeN("racine")`
- `SoldeN_1("racine")`

La logique observée est :

```text
Solde(racine) = somme des soldes débiteurs - somme des soldes créditeurs
pour tous les comptes commençant par cette racine.
```

Ensuite, le signe est adapté selon la nature du poste :

| Nature du poste | Sens usuel |
|---|---|
| Actif, charges, TVA récupérable | `Solde(...)` |
| Passif, capitaux propres, produits, TVA facturée | `-Solde(...)` |
| Amortissements et provisions correctrices | souvent `-Solde(...)` pour afficher un montant positif |

Exemple :

- compte `71240000`, crédit N = `263 357,67` ;
- `SoldeN("712") = -264 857,67` ;
- le CPC affiche les ventes avec `-SoldeN("712") = 264 857,67`.

## Règles N / N-1 par tableau

### Bilan actif

Pour chaque poste d'actif :

- colonne `Brut` : balance N ;
- colonne `Amort. & Prov.` : amortissements/provisions N affichés en positif ;
- colonne `Net` : brut N - amortissements/provisions N ;
- colonne `Exercice précédent` : net N-1 calculé avec la balance N-1 corrigée.

Exemples corrigés :

| Poste | N | N-1 corrigé |
|---|---:|---:|
| Actif immobilisé brut | 367 993,01 | 362 993,01 |
| Amortissements/provisions immobilisés | 357 427,30 | 349 611,83 |
| Actif immobilisé net | 10 565,71 | 13 381,18 |
| Actif circulant net | 1 186 040,63 | 1 044 540,09 |
| Trésorerie actif | 11 394,80 | 10 255,32 |

### Bilan passif

Pour chaque poste du passif :

- colonne `Exercice` : calcul depuis N ;
- colonne `Exercice précédent` : même calcul depuis N-1 corrigée.

Les comptes de passif et de capitaux propres sont généralement affichés avec `-Solde(...)`.

Le résultat net doit être cohérent avec le CPC :

| Poste | N | N-1 corrigé |
|---|---:|---:|
| Résultat net | -2 665,62 | 7 486,26 |
| Financement permanent | -114 310,30 | -104 158,42 |
| Passif circulant | 1 322 311,44 | 1 172 335,01 |

La colonne `Exercice précédent` du bilan passif doit donc être alimentée par `SoldeN_1(...)` avec le même mapping que N.

### Compte de produits et charges

Les charges utilisent le sens débit - crédit. Les produits utilisent le sens crédit - débit.

Les colonnes attendues sont :

- `Totaux de l'exercice` : balance N ;
- `Exercice précédent` : balance N-1 corrigée.

Valeurs de référence :

| Ligne CPC | N | N-1 corrigé |
|---|---:|---:|
| Ventes de biens et services produits `712` | 264 857,67 | 238 371,90 |
| Variation de stock de produits `713` | 70 000,00 | 63 000,00 |
| Produits d'exploitation | 334 861,29 | 301 375,16 |
| Charges d'exploitation | 332 486,91 | 290 358,90 |
| Résultat d'exploitation | 2 374,38 | 11 016,26 |
| Charges non courantes | 2 040,00 | 1 100,00 |
| Impôts sur les résultats | 3 000,00 | 2 430,00 |
| Total produits | 334 861,29 | 301 375,16 |
| Total charges | 337 526,91 | 293 888,90 |
| Résultat net | -2 665,62 | 7 486,26 |

La correction N-1 impacte notamment le compte `61680000` : il vaut `9 600,00` en N et `0,00` en N-1 corrigé. Cela explique une partie de l'écart entre les charges d'exploitation N et N-1.

### Tableau des immobilisations autres que financières

La colonne `Montant brut début exercice` provient de la balance N-1 corrigée.

La colonne `Montant brut fin exercice` provient de la balance N.

La différence nette est placée dans les augmentations ou diminutions selon le modèle Excel, mais une ventilation exacte des mouvements peut nécessiter des informations complémentaires.

Exemple corrigé :

| Poste | Début N depuis N-1 | Fin N | Variation |
|---|---:|---:|---:|
| Racine `235` mobilier, matériel de bureau et aménagements | 79 902,01 | 84 902,01 | +5 000,00 |

La variation vient principalement du compte `23550000` :

- N : `13 287,35` ;
- N-1 corrigé : `8 287,35`.

### Tableau des amortissements

Le cumul de début d'exercice provient de N-1 corrigée.

Le cumul de fin d'exercice provient de N.

La dotation de l'exercice correspond à :

```text
dotation = cumul fin N - cumul début N-1 + amortissements sur sorties
```

En l'absence de sorties renseignées, la variation nette donne la dotation.

Exemple corrigé :

| Poste | Début N depuis N-1 | Fin N | Dotation nette |
|---|---:|---:|---:|
| Racine `2835` amortissements mobilier/agencements | 79 587,65 | 87 403,12 | 7 815,47 |

### Détail de la TVA

Le tableau TVA utilise N-1 comme solde initial et N comme solde final.

| Poste | Début exercice depuis N-1 | Fin exercice depuis N | Variation nette |
|---|---:|---:|---:|
| TVA facturée `4455` | 108 637,31 | 120 708,12 | 12 070,81 |
| TVA récupérable `3455` | 15 547,17 | 17 274,63 | 1 727,46 |

La racine `3455` additionne les comptes commençant par `3455`, notamment :

- `34550000`
- `34551000`
- `34552000`

Les déclarations TVA mensuelles ne sont pas entièrement déductibles de la balance seule. La balance donne les soldes de début et de fin ; les opérations et déclarations doivent expliquer :

```text
solde début + opérations comptables - déclarations = solde fin
```

### État détaillé des stocks

Le tableau des stocks doit reprendre les lignes détaillées du modèle Excel, même si certaines lignes sont à zéro.

Règle :

- `Stock final` : balance N ;
- `Stock initial` : balance N-1 corrigée ;
- `Variation` : stock final - stock initial, conformément aux formules observées dans le classeur.

Mappings observés :

| Ligne | Stock final N | Provision N | Stock initial N-1 | Provision N-1 |
|---|---|---|---|---|
| Biens immeubles | `0` | `0` | `0` | `0` |
| Biens meubles | `311` | `3911` | `311` | `3911` |
| Matières premières | `3121` | `39121` | `3121` | `39121` |
| Matières consommables | `3122 + 3126 + 3128` | `39122 + 39126 + 39128` | mêmes racines N-1 | mêmes racines N-1 |
| Pièces détachées | `0` | `0` | `0` | `0` |
| Emballages récupérables | `31232` | `391232` | `31232` | `391232` |
| Emballages vendus | `0` | `0` | `0` | `0` |
| Emballages perdus | `31231` | `391231` | `31231` | `391231` |
| Produits en cours | `3131 + 3138 + 3141 + 3148` | `39131 + 39138 + 39141 + 39148` | mêmes racines N-1 | mêmes racines N-1 |
| Études en cours | `31342` | `391342` | `31342` | `391342` |
| Travaux en cours | `31341` | `391341` | `31341` | `391341` |
| Services en cours | `31343` | `391343` | `31343` | `391343` |
| Produits finis | `315` | `3915` | `315` | `3915` |
| Biens finis | `0` | `0` | `0` | `0` |
| Déchets | `31451` | `391451` | `31451` | `391451` |
| Rebuts | `31452` | `391452` | `31452` | `391452` |
| Matières de récupération | `31453` | `391453` | `31453` | `391453` |

Dans les fichiers corrigés, le seul stock significatif est :

| Poste | Stock final N | Stock initial N-1 | Variation |
|---|---:|---:|---:|
| Produits finis `315` | 471 035,17 | 401 035,17 | 70 000,00 |

### Tableau de financement de l'exercice

La première partie du tableau de financement compare les masses du bilan :

- colonne `Exercice` : masses calculées depuis N ;
- colonne `Exercice précédent` : masses calculées depuis N-1 corrigée ;
- colonnes `Emplois` et `Ressources` : variations classées selon le sens économique de chaque masse.

Valeurs de référence après correction :

| Masse | N | N-1 corrigé | Variation |
|---|---:|---:|---:|
| Financement permanent | -114 310,30 | -104 158,42 | -10 151,88 |
| Actif immobilisé net | 10 565,71 | 13 381,18 | -2 815,47 |
| Fonds de roulement fonctionnel | -124 876,01 | -117 539,60 | -7 336,41 |
| Actif circulant | 1 186 040,63 | 1 044 540,09 | 141 500,54 |
| Passif circulant | 1 322 311,44 | 1 172 335,01 | 149 976,43 |
| Besoin de financement global | -136 270,81 | -127 794,92 | -8 475,89 |
| Trésorerie nette | 11 394,80 | 10 255,32 | 1 139,48 |

Correction importante par rapport à l'analyse précédente : la trésorerie nette N-1 attendue est `10 255,32`, pas `48 255,32`.

La seconde partie du tableau reprend des flux comme :

- capacité d'autofinancement ;
- distributions ;
- cessions ;
- acquisitions ;
- remboursements ;
- variation du besoin de financement global ;
- variation de trésorerie.

Une partie de ces flux peut être approchée depuis les balances N et N-1, mais une ventilation parfaite peut nécessiter d'autres informations que les seuls soldes de clôture.

## Comptes modifiés entre N et N-1 corrigée

Les deux balances contiennent les mêmes 61 comptes, mais plusieurs soldes N-1 corrigés diffèrent de N.

Principaux écarts fonctionnels :

| Compte | N | N-1 corrigé | Impact |
|---|---:|---:|---|
| `23550000` | 13 287,35 D | 8 287,35 D | Immobilisations : +5 000,00 en N. |
| `28356000` | 87 403,12 C | 79 587,65 C | Amortissements : dotation nette 7 815,47. |
| `31510000` | 471 035,17 D | 401 035,17 D | Stocks : variation +70 000,00. |
| `34210000` | 691 462,83 D | 622 316,55 D | Clients / actif circulant. |
| `3455...` | 17 274,63 D | 15 547,17 D | TVA récupérable. |
| `4411...` | 45 342,32 C | 40 808,09 C | Fournisseurs / passif circulant. |
| `44320000` | 636 333,21 C | 572 699,89 C | Personnel / passif circulant. |
| `4455...` | 120 708,12 C | 108 637,31 C | TVA facturée. |
| `44630000` | 497 987,24 C | 430 443,23 C | Associés / passif circulant. |
| `51410000` + `51610000` | 11 394,80 D | 10 255,32 D | Trésorerie actif. |
| `612` à `619` | 332 486,91 D | 290 358,90 D | Charges d'exploitation. |
| `712` + `713` + `718` | 334 861,29 C | 301 375,16 C | Produits d'exploitation. |
| `65830000` | 2 040,00 D | 1 100,00 D | Charges non courantes. |
| `67050000` | 3 000,00 D | 2 430,00 D | Impôt sur les résultats. |

## Règles à appliquer pour la colonne "Exercice précédent"

Pour tous les tableaux comparatifs :

1. Vérifier qu'une balance N-1 existe pour la même société et l'exercice précédent.
2. Utiliser uniquement les lignes `balance_items` de N-1 pour la colonne `Exercice précédent`.
3. Rejouer exactement les mêmes mappings de comptes que pour N.
4. Rejouer exactement le même sens de signe que pour N.
5. Ne jamais remplacer N-1 par N si la balance N-1 est absente.
6. Si N-1 est absente, laisser la colonne précédente à zéro, vide ou explicitement indisponible selon le comportement prévu.
7. Pour les tableaux de mouvement, utiliser N-1 comme ouverture et N comme clôture.

Résumé opérationnel :

| Type de colonne | Source attendue |
|---|---|
| Exercice N | Balance N |
| Exercice précédent | Balance N-1 corrigée |
| Brut fin exercice | Balance N |
| Brut début exercice | Balance N-1 corrigée |
| Cumul amortissement fin | Balance N |
| Cumul amortissement début | Balance N-1 corrigée |
| Stock final | Balance N |
| Stock initial | Balance N-1 corrigée |
| Solde TVA fin | Balance N |
| Solde TVA début | Balance N-1 corrigée |

## Conclusion

La correction du fichier de référence confirme que la gestion N/N-1 doit être strictement symétrique :

- N alimente les valeurs de l'exercice courant, les clôtures et les stocks finaux ;
- N-1 corrigée alimente les valeurs de l'exercice précédent, les ouvertures et les stocks initiaux ;
- les comptes et les signes doivent être identiques entre N et N-1 ;
- les variations doivent être calculées après avoir obtenu séparément les valeurs N et N-1.

Le point le plus important pour l'application est donc de garantir que chaque tableau de la liasse lit la bonne année comptable dans `balance_items` avant d'appliquer ses règles de mapping.
