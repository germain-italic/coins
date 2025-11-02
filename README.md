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

## Gallery

### Dev server

```bash
php server.php
# http://127.0.0.1:8000
```

### Structure

- 2 photos per coin (pile/face) in chronological order
- `index.php` - Redirects to gallery
- `gallery/config.php` - Centralized paths configuration
- `gallery/index.php` - Grid of all coins
- `gallery/coin.php?id=N` - Coin detail page (2 photos + legend)
- `gallery/lightbox.php?coin=N&photo=0|1` - Fullscreen lightbox

### Keyboard navigation

- **Coin page**: ← → between coins
- **Lightbox**: ← → between photos (auto-switches coins), Esc to close

### Legend fields

- Country, year, value, notes (to be populated later)
