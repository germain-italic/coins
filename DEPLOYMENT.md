# Guide de déploiement sécurisé

## Prérequis serveur

### Apache
```apache
# Vérifier que mod_headers est activé
a2enmod headers
systemctl restart apache2
```

### Nginx
Ajouter dans le bloc `server`:
```nginx
# Protection fichiers sensibles
location ~ /\. {
    deny all;
}

location ~* \.py$ {
    deny all;
}

# Headers de sécurité
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'" always;

# Désactiver directory listing
autoindex off;
```

## Configuration PHP (php.ini)

```ini
# Session sécurisée
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.use_strict_mode = 1

# Désactiver affichage erreurs en production
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Limiter uploads
file_uploads = Off
upload_max_filesize = 2M
post_max_size = 8M

# Désactiver fonctions dangereuses
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

## Checklist de déploiement

### 1. Permissions fichiers
```bash
# Propriétaire www-data (ou utilisateur web)
chown -R www-data:www-data /path/to/coins

# Permissions restrictives
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# coins_metadata.json doit être writable
chmod 664 gallery/coins_metadata.json

# .env JAMAIS accessible web
chmod 600 .env
```

### 2. Vérifier .env
```bash
# .env ne doit PAS être dans webroot
# Ou protégé par .htaccess (déjà configuré)

# Mot de passe fort (min 16 caractères)
EDIT_PASSWORD=$(openssl rand -base64 24)
```

### 3. HTTPS obligatoire
```apache
# Apache: Forcer HTTPS
<VirtualHost *:80>
    ServerName coins.example.com
    Redirect permanent / https://coins.example.com/
</VirtualHost>
```

```nginx
# Nginx: Forcer HTTPS
server {
    listen 80;
    server_name coins.example.com;
    return 301 https://$server_name$request_uri;
}
```

### 4. Tests de sécurité

**Test 1: Fichiers sensibles inaccessibles**
```bash
curl https://your-domain.com/.env  # Doit retourner 403
curl https://your-domain.com/.git/config  # Doit retourner 403
curl https://your-domain.com/scripts/crop_images.py  # Doit retourner 403
```

**Test 2: Headers sécurité présents**
```bash
curl -I https://your-domain.com/gallery/
# Vérifier présence de:
# X-Content-Type-Options: nosniff
# X-Frame-Options: DENY
# Content-Security-Policy: ...
```

**Test 3: Rate limiting fonctionne**
```bash
# Tenter 6 connexions avec mauvais mot de passe
# La 6ème doit retourner 429 Too Many Requests
```

**Test 4: Session sécurisée**
```bash
# Cookies doivent avoir les flags:
# HttpOnly; Secure; SameSite=Strict
```

### 5. Monitoring

**Logs à surveiller:**
- `/var/log/php/error.log` - Erreurs PHP
- `/tmp/coin_login_attempts.json` - Tentatives de connexion
- Logs Apache/Nginx - Accès suspects

**Alertes recommandées:**
- Plus de 10 tentatives login échouées en 1h
- Accès à des fichiers .env, .git
- Erreurs PHP répétées

## Sauvegarde

```bash
# Sauvegarder les métadonnées régulièrement
rsync -av /path/to/coins/gallery/coins_metadata.json /backup/

# Versioner avec git
cd /path/to/coins
git add gallery/coins_metadata.json
git commit -m "Backup metadata $(date +%Y-%m-%d)"
```

## Mise à jour

```bash
# Pull depuis git
git pull origin master

# Vérifier permissions
chmod 664 gallery/coins_metadata.json

# Tester en local d'abord
php -S localhost:8000 -t gallery/
```

## Rollback d'urgence

```bash
# Revenir à version précédente
git log --oneline
git checkout <commit-hash> gallery/coins_metadata.json

# Ou depuis backup
cp /backup/coins_metadata.json gallery/
```
