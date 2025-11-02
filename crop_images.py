#!/usr/bin/env python3
"""
Script pour recadrer les photos de pièces de monnaie du format portrait (1848x4000)
au format carré (1848x1848) en recadrant depuis le centre.
"""

import os
import sys
from pathlib import Path
from PIL import Image


def crop_image_to_square(image_path, output_path=None, target_size=1848):
    """
    Recadre une image au format carré depuis le centre.
    Fonctionne pour les images portrait ou paysage.

    Args:
        image_path: Chemin vers l'image source
        output_path: Chemin de sortie (None = écrase l'original)
        target_size: Taille du carré final (défaut: 1848)
    """
    try:
        with Image.open(image_path) as img:
            # Appliquer l'orientation EXIF si présente
            from PIL import ImageOps
            img = ImageOps.exif_transpose(img)

            width, height = img.size

            # Vérifier que l'image est assez grande
            if width >= target_size and height >= target_size:
                # Calculer les coordonnées pour recadrer depuis le centre
                left = (width - target_size) // 2
                top = (height - target_size) // 2
                right = left + target_size
                bottom = top + target_size

                # Recadrer l'image
                cropped_img = img.crop((left, top, right, bottom))

                # Sauvegarder
                save_path = output_path if output_path else image_path
                cropped_img.save(save_path, quality=95, optimize=True)

                return True, f"✓ {image_path.name}: {width}x{height} → {target_size}x{target_size}"
            else:
                return False, f"✗ {image_path.name}: dimensions insuffisantes ({width}x{height})"

    except Exception as e:
        return False, f"✗ {image_path.name}: erreur - {str(e)}"


def process_directory(directory, recursive=True, dry_run=False):
    """
    Traite tous les fichiers image dans un répertoire.

    Args:
        directory: Répertoire à traiter
        recursive: Traiter les sous-répertoires
        dry_run: Mode simulation (ne modifie pas les fichiers)
    """
    extensions = {'.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG'}
    directory = Path(directory)

    if not directory.exists():
        print(f"Erreur: le répertoire {directory} n'existe pas")
        return

    # Trouver tous les fichiers image
    if recursive:
        image_files = [f for f in directory.rglob('*') if f.suffix in extensions and f.is_file()]
    else:
        image_files = [f for f in directory.glob('*') if f.suffix in extensions and f.is_file()]

    if not image_files:
        print(f"Aucune image trouvée dans {directory}")
        return

    print(f"{'[MODE SIMULATION] ' if dry_run else ''}Traitement de {len(image_files)} images...\n")

    success_count = 0
    error_count = 0

    for image_file in sorted(image_files):
        if dry_run:
            try:
                with Image.open(image_file) as img:
                    width, height = img.size
                    print(f"[DRY-RUN] {image_file.name}: {width}x{height}")
            except Exception as e:
                print(f"[DRY-RUN] ✗ {image_file.name}: erreur - {str(e)}")
        else:
            success, message = crop_image_to_square(image_file)
            print(message)
            if success:
                success_count += 1
            else:
                error_count += 1

    if not dry_run:
        print(f"\n{'='*60}")
        print(f"Traitement terminé: {success_count} succès, {error_count} erreurs")


def main():
    import argparse

    parser = argparse.ArgumentParser(
        description="Recadre les photos de pièces au format carré (1848x1848) depuis le centre"
    )
    parser.add_argument(
        'directory',
        nargs='?',
        default='pictures',
        help='Répertoire contenant les images (défaut: pictures)'
    )
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Mode simulation: affiche les fichiers sans les modifier'
    )
    parser.add_argument(
        '--no-recursive',
        action='store_true',
        help='Ne pas traiter les sous-répertoires'
    )
    parser.add_argument(
        '--size',
        type=int,
        default=1848,
        help='Taille du carré final (défaut: 1848)'
    )

    args = parser.parse_args()

    process_directory(
        args.directory,
        recursive=not args.no_recursive,
        dry_run=args.dry_run
    )


if __name__ == '__main__':
    main()
