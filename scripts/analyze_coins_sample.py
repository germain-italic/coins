#!/usr/bin/env python3
"""Version test: analyse seulement les 5 premières pièces."""

import os
import json
import base64
from pathlib import Path
from anthropic import Anthropic
from dotenv import load_dotenv

load_dotenv()

PICTURES_DIR = Path("pictures/2025-11-02_19h15")
OUTPUT_FILE = Path("gallery/coins_metadata.json")
API_KEY_ENV = "ANTHROPIC_API_KEY"
SAMPLE_SIZE = 5  # Nombre de pièces à tester

def encode_image(image_path):
    with open(image_path, "rb") as f:
        return base64.standard_b64encode(f.read()).decode("utf-8")

def analyze_coin(client, face_path, pile_path, coin_id):
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

    response_text = message.content[0].text.strip()

    if response_text.startswith("```"):
        lines = response_text.split("\n")
        response_text = "\n".join(lines[1:-1]) if len(lines) > 2 else response_text

    metadata = json.loads(response_text)
    metadata["id"] = coin_id
    metadata["images"] = [face_path.name, pile_path.name]

    return metadata

def main():
    if not os.getenv(API_KEY_ENV):
        print(f"❌ Erreur: {API_KEY_ENV} non défini dans .env")
        return 1

    client = Anthropic(api_key=os.getenv(API_KEY_ENV))

    images = sorted([f for f in PICTURES_DIR.glob("*.jpg")])
    if not images:
        print(f"❌ Aucune image dans {PICTURES_DIR}")
        return 1

    # Limiter aux N premières pièces
    coins = [(images[i], images[i+1]) for i in range(0, min(len(images), SAMPLE_SIZE * 2), 2)]

    print(f"🪙 TEST: Analyse de {len(coins)} pièces (échantillon)")
    print(f"📁 Sortie: {OUTPUT_FILE}\n")

    results = []
    for idx, (face, pile) in enumerate(coins):
        print(f"[{idx+1}/{len(coins)}] Pièce #{idx+1}...", end=" ", flush=True)

        try:
            metadata = analyze_coin(client, face, pile, idx)
            results.append(metadata)

            country = metadata.get("country", "?")
            value = metadata.get("value", "?")
            year = metadata.get("year", "?")
            print(f"✓ {country} - {value} ({year})")

        except Exception as e:
            print(f"❌ {e}")
            results.append({
                "id": idx,
                "images": [face.name, pile.name],
                "error": str(e)
            })

    OUTPUT_FILE.parent.mkdir(exist_ok=True)
    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    print(f"\n✅ Test terminé! {len(results)} pièces dans {OUTPUT_FILE}")
    print(f"📊 Vérifie la galerie: http://127.0.0.1:8000/gallery/")

    return 0

if __name__ == "__main__":
    exit(main())
