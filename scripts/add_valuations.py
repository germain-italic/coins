#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script interactif pour ajouter des cotations aux pièces de monnaie
Ouvre automatiquement Numista pour recherche et permet la saisie manuelle
"""

import json
import webbrowser
import urllib.parse
from datetime import datetime
from pathlib import Path

# Chemins
METADATA_FILE = Path(__file__).parent.parent / "gallery" / "coins_metadata.json"
BACKUP_DIR = Path(__file__).parent.parent / "gallery" / "backups"

# Sources de cotation disponibles
SOURCES = {
    "1": {"name": "Numista", "base_url": "https://en.numista.com"},
    "2": {"name": "CGB.fr", "base_url": "https://www.cgb.fr"},
    "3": {"name": "Argus2euros", "base_url": "https://argus2euros.fr"},
    "4": {"name": "Autre source fiable", "base_url": ""}
}

def load_metadata():
    """Charge les métadonnées des pièces"""
    with open(METADATA_FILE, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_metadata(data):
    """Sauvegarde les métadonnées avec backup"""
    # Créer le dossier de backup si nécessaire
    BACKUP_DIR.mkdir(exist_ok=True)

    # Backup de l'ancien fichier
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_file = BACKUP_DIR / f"coins_metadata_backup_{timestamp}.json"

    if METADATA_FILE.exists():
        with open(METADATA_FILE, 'r', encoding='utf-8') as f:
            backup_data = f.read()
        with open(backup_file, 'w', encoding='utf-8') as f:
            f.write(backup_data)
        print(f"✓ Backup créé: {backup_file.name}")

    # Sauvegarde du nouveau fichier
    with open(METADATA_FILE, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=4)
    print(f"✓ Métadonnées sauvegardées")

def open_search(coin):
    """Ouvre une recherche automatique sur Numista"""
    # Construire la requête de recherche
    search_parts = []

    if coin.get('value'):
        search_parts.append(coin['value'])
    if coin.get('year'):
        search_parts.append(str(coin['year']))
    if coin.get('country'):
        search_parts.append(coin['country'])
    if coin.get('currency'):
        search_parts.append(coin['currency'])

    search_query = ' '.join(search_parts)
    encoded_query = urllib.parse.quote(search_query)

    # URL de recherche Numista
    search_url = f"https://en.numista.com/catalogue/index.php?mode=simplifie&p=1&l=&r=&e=&d=&ca=3&no=&i=&v=&m=&a=&t=&dg=&w=&u=&f=&g=&tb=1&tc=1&tn=1&tp=1&tt=1&te=1&cat=y&ru=&cc=&cno=&cn=&cj=&ce=&cu=&cg=&cy=&bi=&mt=&ds=&do=&dd=&de=&km=&fr=&ca=&pe=&py=&ct=coin&std=1&wi=0&ma=0&se={encoded_query}"

    print(f"\n🔍 Ouverture de la recherche dans le navigateur...")
    print(f"   Requête: {search_query}")
    webbrowser.open(search_url)

def display_coin(coin, index, total):
    """Affiche les informations d'une pièce"""
    print("\n" + "="*80)
    print(f"PIÈCE {index + 1}/{total} (ID: {coin['id']})")
    print("="*80)
    print(f"🌍 Pays:     {coin.get('country', 'N/A')}")
    print(f"💰 Monnaie:  {coin.get('currency', 'N/A')}")
    print(f"💵 Valeur:   {coin.get('value', 'N/A')}")
    print(f"📅 Année:    {coin.get('year', 'N/A')}")
    print(f"📝 Notes:    {coin.get('notes', 'N/A')[:100]}{'...' if len(coin.get('notes', '')) > 100 else ''}")

    if coin.get('valuation'):
        print(f"\n✓ Cotation existante:")
        val = coin['valuation']
        print(f"  Prix: {val.get('price', 'N/A')} {val.get('currency', '')}")
        print(f"  État: {val.get('condition', 'N/A')}")
        print(f"  Source: {val.get('source_name', 'N/A')}")
        print(f"  URL: {val.get('source_url', 'N/A')}")

    print("-"*80)

def get_valuation_data(coin):
    """Demande interactivement les données de cotation"""
    print("\n📊 SAISIE DE LA COTATION")
    print("-"*80)

    # Choix de la source
    print("\nSources disponibles:")
    for key, source in SOURCES.items():
        print(f"  {key}. {source['name']}")

    source_choice = input("\nChoisir la source (1-4, ou 's' pour skip): ").strip()

    if source_choice.lower() == 's':
        return None

    if source_choice not in SOURCES:
        print("❌ Choix invalide, annulé")
        return None

    source = SOURCES[source_choice]

    # URL de la source
    source_url = input(f"\nURL {source['name']} (coller l'URL de la page de la pièce): ").strip()
    if not source_url:
        print("❌ URL requise, annulé")
        return None

    # Prix
    price = input("Prix (ex: '2.50' ou '1-3'): ").strip()
    if not price:
        print("❌ Prix requis, annulé")
        return None

    # Devise
    currency = input("Devise (EUR, USD, etc.) [EUR]: ").strip() or "EUR"

    # État de conservation
    print("\nÉtats communs: TB (Très Bien), TTB (Très Très Bien), SUP (Superbe), SPL (Splendide)")
    condition = input("État de conservation [TTB]: ").strip() or "TTB"

    # Notes optionnelles
    notes = input("Notes optionnelles sur la cotation: ").strip() or None

    # Créer l'objet de cotation
    valuation = {
        "price": price,
        "currency": currency,
        "condition": condition,
        "source_name": source['name'],
        "source_url": source_url,
        "last_updated": datetime.now().strftime("%Y-%m-%d")
    }

    if notes:
        valuation["notes"] = notes

    # Confirmation
    print("\n✓ Cotation à ajouter:")
    print(f"  Prix: {price} {currency}")
    print(f"  État: {condition}")
    print(f"  Source: {source['name']}")
    print(f"  URL: {source_url}")
    if notes:
        print(f"  Notes: {notes}")

    confirm = input("\nConfirmer ? (o/n) [o]: ").strip().lower()
    if confirm and confirm != 'o':
        print("❌ Annulé")
        return None

    return valuation

def main():
    """Fonction principale"""
    print("="*80)
    print("AJOUT DE COTATIONS AUX PIÈCES DE MONNAIE")
    print("="*80)
    print("\nCe script vous aide à ajouter des cotations fiables pour chaque pièce.")
    print("Pour chaque pièce, le navigateur ouvrira une recherche Numista.")
    print("Vous pourrez alors copier l'URL et saisir la cotation.\n")

    # Charger les métadonnées
    coins = load_metadata()
    print(f"✓ {len(coins)} pièces chargées")

    # Options de démarrage
    print("\nOptions:")
    print("  1. Traiter toutes les pièces")
    print("  2. Traiter uniquement les pièces sans cotation")
    print("  3. Traiter une pièce spécifique (par ID)")

    choice = input("\nChoix (1-3) [2]: ").strip() or "2"

    # Filtrer les pièces selon le choix
    if choice == "1":
        coins_to_process = coins
    elif choice == "2":
        coins_to_process = [c for c in coins if not c.get('valuation')]
        print(f"✓ {len(coins_to_process)} pièces sans cotation")
    elif choice == "3":
        coin_id = input("ID de la pièce: ").strip()
        try:
            coin_id = int(coin_id)
            coins_to_process = [c for c in coins if c['id'] == coin_id]
            if not coins_to_process:
                print(f"❌ Aucune pièce avec l'ID {coin_id}")
                return
        except ValueError:
            print("❌ ID invalide")
            return
    else:
        print("❌ Choix invalide")
        return

    if not coins_to_process:
        print("\n✓ Aucune pièce à traiter")
        return

    # Traiter chaque pièce
    modified = False

    for i, coin in enumerate(coins_to_process):
        display_coin(coin, i, len(coins_to_process))

        # Demander si on veut ouvrir la recherche
        open_search_choice = input("\nOuvrir la recherche Numista ? (o/n) [o]: ").strip().lower()
        if not open_search_choice or open_search_choice == 'o':
            open_search(coin)

        # Saisir les données de cotation
        valuation = get_valuation_data(coin)

        if valuation:
            # Trouver la pièce dans la liste originale et mettre à jour
            for original_coin in coins:
                if original_coin['id'] == coin['id']:
                    original_coin['valuation'] = valuation
                    modified = True
                    print("\n✓ Cotation ajoutée à la pièce")
                    break

        # Demander si on continue
        if i < len(coins_to_process) - 1:
            continue_choice = input("\nContinuer avec la pièce suivante ? (o/n/s pour sauvegarder et quitter) [o]: ").strip().lower()
            if continue_choice == 'n':
                break
            elif continue_choice == 's':
                if modified:
                    save_metadata(coins)
                print("\n✓ Session terminée")
                return

    # Sauvegarder les modifications
    if modified:
        print("\n" + "="*80)
        save_metadata(coins)
        print("="*80)
        print(f"\n✓ Traitement terminé !")

        # Statistiques
        coins_with_valuation = sum(1 for c in coins if c.get('valuation'))
        print(f"\n📊 Statistiques:")
        print(f"  Total pièces: {len(coins)}")
        print(f"  Avec cotation: {coins_with_valuation}")
        print(f"  Sans cotation: {len(coins) - coins_with_valuation}")
    else:
        print("\n✓ Aucune modification effectuée")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n⚠️  Interruption par l'utilisateur")
        print("Les modifications non sauvegardées sont perdues")
    except Exception as e:
        print(f"\n❌ Erreur: {e}")
        import traceback
        traceback.print_exc()
