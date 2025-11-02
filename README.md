# Coins Gallery

Photo gallery repository for coin collections.

## Structure

- `pictures/` - Photo galleries in dated subdirectories (YYYY-MM-DD_HHhMM)
- `crop_images.py` - Crop portrait photos to square format

## Installation

```bash
python3 -m venv venv
source venv/bin/activate
pip install Pillow
```

## Usage

### Crop images to square

```bash
source venv/bin/activate
python crop_images.py pictures/
```

Options:
- `--dry-run` - Preview without processing
- `--size 1848` - Target size (default: 1848x1848)
- `--no-recursive` - Process only specified directory

Crops portrait images (1848x4000) to square (1848x1848) from center, handles EXIF orientation.
