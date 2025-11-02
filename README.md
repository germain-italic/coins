# Coins Gallery

Photo gallery repository for coin collections.

## Structure

- `pictures/` - Photo galleries in dated subdirectories (YYYY-MM-DD_HHhMM)
- `crop_images.py` - Crop portrait photos to square format

## Installation

```bash
python3 -m venv venv
source venv/bin/activate
pip install Pillow anthropic python-dotenv
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
- `gallery/index.php` - Grid of all coins with filters
- `gallery/coin.php?id=N` - Coin detail page (2 photos + legend)
- `gallery/lightbox.php?coin=N&photo=0|1` - Fullscreen lightbox
- `gallery/edit_metadata.php` - API for manual editing

### Features

**Homepage:**
- Filterable by country, currency, year
- Live result count
- Auto-update on filter change

**Coin detail page:**
- 2 photos (pile/face) with labels
- Full metadata: country, currency, value, year, notes
- AI attribution notice (auto-removed on manual edit)
- "Modifier" button for manual corrections

**Lightbox:**
- Fullscreen photo viewer
- Navigate between photos and coins seamlessly

### Keyboard navigation

- **Coin page**: ← → between coins
- **Lightbox**: ← → between photos (auto-switches coins), Esc to close

### Manual editing

1. Add password to `.env`: `EDIT_PASSWORD=your-password`
2. Click "Modifier" button on coin detail page
3. Enter password (saved in session)
4. Edit metadata via prompts
5. AI attribution removed on save

Security: CSRF protection, input validation, session-based auth

## AI Analysis

### Setup

1. Get API key: https://console.anthropic.com/
2. Create `.env` file:
```bash
cp .env.example .env
# Edit .env and add your API key
```

### Analyze coins

```bash
source venv/bin/activate
python analyze_coins.py
```

- Uses Claude Sonnet 4 (vision model)
- Extracts: country, currency, value, year, notes
- Output: `gallery/coins_metadata.json`
- Cost: ~$0.015/coin (~$1.50 for 99 coins)
- Accuracy: ~95% (manual review recommended)

### Manual corrections

Edit `gallery/coins_metadata.json` to fix any errors, then refresh the gallery.
