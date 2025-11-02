#!/usr/bin/env python3
"""
Analyse automatique des pièces de monnaie via Claude API.
Extrait: pays, monnaie, valeur faciale, année, commentaires.
"""

import os
import json
import base64
from pathlib import Path
from anthropic import Anthropic
from dotenv import load_dotenv

# Charger les variables depuis .env
load_dotenv()

# Configuration
PICTURES_DIR = Path("pictures/2025-11-02_19h15")
OUTPUT_FILE = Path("gallery/coins_metadata.json")
API_KEY_ENV = "ANTHROPIC_API_KEY"

def encode_image(image_path):
    """Encode image en base64 pour l'API."""
    with open(image_path, "rb") as f:
        return base64.standard_b64encode(f.read()).decode("utf-8")

def analyze_coin(client, face_path, pile_path, coin_id):
    """
    Analyse une pièce (2 photos) via Claude API.
    Retourne un dict avec les métadonnées.
    """
    face_b64 = encode_image(face_path)
    pile_b64 = encode_image(pile_path)

    prompt = """Analyse ces deux photos d'une pièce de monnaie (face et pile).

CRITICAL: Examine EVERY inscription with maximum attention, especially dates. Take your time to read digits carefully.

Extract the following information as strict JSON:
{
  "country": "Country name in French",
  "currency": "Currency name (ex: Lire, Franc, Euro, etc.)",
  "value": "Face value with unit (ex: 200 Lire, 5 Francs)",
  "year": "Minting year (YYYY format, or null if truly illegible)",
  "notes": "Optional remarks (condition, special features, notable symbols)"
}

Rules:
- Read ALL visible inscriptions carefully, even if blurry or small
- YEAR IS CRITICAL: Look on ENTIRE coin perimeter, BOTH sides, near the rim
- Pay extreme attention to distinguish similar digits: 0 vs 8, 1 vs 7, 3 vs 8, 5 vs 6
- Double-check each digit of the year before answering
- If truly illegible after careful examination, use null
- Be precise about currency (distinguish Italian Lira, French Franc, etc.)
- For notes, mention only remarkable elements
- Answer ONLY with JSON, no additional text"""

    message = client.messages.create(
        model="claude-sonnet-4-20250514",
        max_tokens=1000,
        messages=[{
            "role": "user",
            "content": [
                {
                    "type": "image",
                    "source": {
                        "type": "base64",
                        "media_type": "image/jpeg",
                        "data": face_b64
                    }
                },
                {
                    "type": "image",
                    "source": {
                        "type": "base64",
                        "media_type": "image/jpeg",
                        "data": pile_b64
                    }
                },
                {
                    "type": "text",
                    "text": prompt
                }
            ]
        }]
    )

    # Parse la réponse JSON
    response_text = message.content[0].text.strip()

    # Nettoyer si Claude ajoute des backticks markdown
    if response_text.startswith("```"):
        lines = response_text.split("\n")
        response_text = "\n".join(lines[1:-1]) if len(lines) > 2 else response_text

    metadata = json.loads(response_text)
    metadata["id"] = coin_id
    metadata["images"] = [face_path.name, pile_path.name]

    return metadata

def main():
    # Vérifier la clé API
    if not os.getenv(API_KEY_ENV):
        print(f"❌ Erreur: Variable d'environnement {API_KEY_ENV} non définie")
        print(f"\nCrée un fichier .env avec:")
        print(f"  {API_KEY_ENV}=ta-clé-api")
        return 1

    # Initialiser le client
    client = Anthropic(api_key=os.getenv(API_KEY_ENV))

    # Charger les images
    images = sorted([f for f in PICTURES_DIR.glob("*.jpg")])
    if not images:
        print(f"❌ Aucune image trouvée dans {PICTURES_DIR}")
        return 1

    # Grouper par paires
    coins = [(images[i], images[i+1]) for i in range(0, len(images), 2)]
    total = len(coins)

    print(f"🪙 Analyse de {total} pièces ({len(images)} photos)")
    print(f"📁 Sortie: {OUTPUT_FILE}")
    print(f"💰 Coût estimé: ~${total * 0.015:.2f} (Sonnet 4)\n")

    # Analyser chaque pièce
    results = []
    for idx, (face, pile) in enumerate(coins):
        print(f"[{idx+1}/{total}] Analyse pièce #{idx+1}...", end=" ", flush=True)

        try:
            metadata = analyze_coin(client, face, pile, idx)
            results.append(metadata)

            # Afficher le résultat
            country = metadata.get("country", "?")
            value = metadata.get("value", "?")
            year = metadata.get("year", "?")
            print(f"✓ {country} - {value} ({year})")

        except Exception as e:
            print(f"❌ Erreur: {e}")
            results.append({
                "id": idx,
                "images": [face.name, pile.name],
                "error": str(e)
            })

    # Sauvegarder les résultats
    OUTPUT_FILE.parent.mkdir(exist_ok=True)
    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    print(f"\n✅ Terminé! Métadonnées sauvegardées dans {OUTPUT_FILE}")
    print(f"📊 {len([r for r in results if 'error' not in r])}/{total} pièces analysées avec succès")

    return 0

if __name__ == "__main__":
    exit(main())
