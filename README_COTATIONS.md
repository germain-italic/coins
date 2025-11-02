# Système de Cotation des Pièces

## Vue d'ensemble

Le système de cotation permet d'afficher les prix estimés pour chaque pièce avec des liens directs vers des sources fiables (Numista, CGB.fr, Argus2euros).

## Fonctionnalités

### 1. Liens de recherche automatique

Sur chaque page de pièce (`coin.php`), une section "🔍 Rechercher la cotation sur :" apparaît avec :

- **📚 Numista** : Base de données collaborative mondiale (source fiable pour cotation)
- **🛒 eBay** : Marché en ligne pour voir les prix de vente actuels
- **🏷️ Le Bon Coin** : Annonces locales pour comparer les prix du marché français
- **💶 Argus2euros** : Spécialiste Euro (affiché uniquement pour les pièces Euro)

Chaque lien ouvre automatiquement une recherche pré-remplie avec les informations de la pièce (valeur, année, pays, monnaie).

### 2. Affichage des cotations

Si une pièce a une cotation enregistrée, elle s'affiche dans un encadré vert avec :
- Prix et devise
- État de conservation
- Lien vers la source
- Notes optionnelles
- Date de mise à jour

## Structure des données

Dans `gallery/coins_metadata.json`, chaque pièce peut avoir un champ `valuation` :

```json
{
    "id": 0,
    "country": "Italie",
    "value": "200 Lire",
    "year": "1978",
    "valuation": {
        "price": "3.50",
        "currency": "EUR",
        "condition": "TTB",
        "source_name": "Numista",
        "source_url": "https://en.numista.com/catalogue/pieces2605.html",
        "notes": "Pièce courante",
        "last_updated": "2025-11-02"
    }
}
```

### Champs du valuation

- **price** (requis) : Prix ou fourchette (ex: "2.50" ou "1-3")
- **currency** (requis) : Devise (EUR, USD, etc.)
- **condition** (requis) : État (TB, TTB, SUP, SPL, FDC)
- **source_name** (requis) : Nom de la source
- **source_url** (requis) : URL complète vers la page source
- **notes** (optionnel) : Remarques sur la cotation
- **last_updated** (requis) : Date au format YYYY-MM-DD

### États de conservation

- **TB** : Très Bien
- **TTB** : Très Très Bien
- **SUP** : Superbe
- **SPL** : Splendide
- **FDC** : Fleur De Coin (neuve)

## Ajout manuel de cotations

### Méthode recommandée

1. Aller sur la page de la pièce : `https://pieces.italic.fr/gallery/coin.php?id=X`
2. Cliquer sur un des liens de recherche (Numista recommandé)
3. Trouver la pièce correspondante sur le site source
4. Copier l'URL et noter le prix
5. Éditer `gallery/coins_metadata.json` et ajouter l'objet `valuation`

### Exemple pratique

```bash
# Éditer le fichier
nano gallery/coins_metadata.json

# Trouver la pièce par son ID et ajouter :
"valuation": {
    "price": "2.50",
    "currency": "EUR",
    "condition": "TTB",
    "source_name": "Numista",
    "source_url": "https://en.numista.com/catalogue/pieces12345.html",
    "last_updated": "2025-11-02"
}
```

## Script Python interactif (optionnel)

Un script Python est disponible pour faciliter l'ajout de cotations :

```bash
python3 scripts/add_valuations.py
```

Le script :
- Affiche les informations de chaque pièce
- Ouvre automatiquement la recherche dans le navigateur
- Propose une saisie interactive guidée
- Crée des backups automatiques avant modification

### Options du script

1. Traiter toutes les pièces
2. Traiter uniquement les pièces sans cotation
3. Traiter une pièce spécifique (par ID)

## Sources fiables

### Numista (Recommandé)

- **URL** : https://en.numista.com
- **Avantages** :
  - Catalogue mondial complet
  - Cotations par état
  - Photos haute qualité
  - Communauté active
- **Utilisation** : Pour toutes les pièces

### eBay

- **URL** : https://www.ebay.fr
- **Avantages** :
  - Prix de vente réels et actuels
  - Large sélection internationale
  - Historique des ventes disponible
- **Utilisation** : Pour voir les prix du marché actuel
- **Note** : Éviter pour cotation officielle, privilégier Numista

### Le Bon Coin

- **URL** : https://www.leboncoin.fr
- **Avantages** :
  - Prix du marché local français
  - Annonces entre particuliers
  - Tendances régionales
- **Utilisation** : Pour comparer avec le marché français
- **Note** : Prix souvent plus bas, ne pas utiliser comme référence principale

### Argus2euros

- **URL** : https://argus2euros.fr
- **Avantages** :
  - Spécialisé Euro
  - Cotations actualisées
  - Interface simple
- **Utilisation** : Uniquement pour pièces Euro

## Sécurité

- Tous les liens s'ouvrent dans un nouvel onglet (`target="_blank"`)
- Protection contre les attaques XSS avec `htmlspecialchars()`
- `rel="noopener noreferrer"` pour sécurité des liens externes
- URLs encodées avec `urlencode()`

## Maintenance

### Mettre à jour une cotation

Éditer le fichier JSON et modifier l'objet `valuation` :
- Changer le prix si nécessaire
- Mettre à jour `last_updated` avec la date du jour

### Supprimer une cotation

Supprimer l'objet `valuation` de la pièce dans le JSON.

### Backups

Si vous utilisez le script Python, des backups sont créés dans :
```
gallery/backups/coins_metadata_backup_YYYYMMDD_HHMMSS.json
```

### Restaurer depuis un backup

```bash
cp gallery/backups/coins_metadata_backup_YYYYMMDD_HHMMSS.json gallery/coins_metadata.json
```

## Déploiement

Les modifications sont directement visibles après édition du fichier JSON (pas de cache).

Pour déployer les changements de code :

```bash
git add gallery/coin.php
git commit -m "Add valuation search links"
git push
```

## Statistiques

Pour connaître le nombre de pièces avec/sans cotation :

```bash
python3 -c "
import json
with open('gallery/coins_metadata.json', 'r') as f:
    coins = json.load(f)
with_val = sum(1 for c in coins if c.get('valuation'))
print(f'Avec cotation: {with_val}/{len(coins)}')
print(f'Sans cotation: {len(coins)-with_val}/{len(coins)}')
"
```

## Améliorations futures possibles

- [ ] API automatique Numista (si clé disponible)
- [ ] Vérification automatique des URLs mortes
- [ ] Export CSV des cotations
- [ ] Graphiques d'évolution des prix
- [ ] Système d'alertes pour pièces de valeur
- [ ] Module d'édition des cotations via interface web
