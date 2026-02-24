#!/bin/bash
# deploy/setup.sh
#
# Idempotent server provisioning script for PreShift on Ubuntu 24.04 LTS.
# Installs and configures: Nginx, MySQL, PHP 8.4, Node 20, Composer,
# Certbot, Supervisor, and the PreShift application directory.
#
# Usage (run as root on a fresh droplet):
#   chmod +x setup.sh
#   ./setup.sh
#
# After running, you must:
#   1. Copy .env.production.example to /var/www/preshift/api/.env and fill secrets
#   2. Run: php artisan key:generate
#   3. Run: php artisan migrate --force
#   4. Set up SSL: certbot --nginx -d preshift86.com -d www.preshift86.com
#   5. Build the frontend: cd /var/www/preshift/client && npm ci && npm run build

set -euo pipefail

# ── Guard: must run as root ──────────────────────────────────────────
if [ "$EUID" -ne 0 ]; then
  echo "Error: this script must be run as root."
  exit 1
fi

APP_USER="preshift"
APP_DIR="/var/www/preshift"
LOG_DIR="/var/log/preshift"
PHP_VERSION="8.3"
NODE_MAJOR=20

echo "==> Updating system packages..."
apt-get update -y
apt-get upgrade -y

# ── Install system packages ──────────────────────────────────────────
echo "==> Installing system packages..."
apt-get install -y \
  nginx \
  mysql-server \
  php${PHP_VERSION} \
  php${PHP_VERSION}-fpm \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-zip \
  php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-gd \
  unzip \
  certbot \
  python3-certbot-nginx \
  supervisor \
  git \
  curl \
  ufw

# ── Install Node.js 20 via NodeSource ────────────────────────────────
if ! command -v node &>/dev/null || [[ "$(node -v)" != v${NODE_MAJOR}* ]]; then
  echo "==> Installing Node.js ${NODE_MAJOR}..."
  curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash -
  apt-get install -y nodejs
else
  echo "==> Node.js $(node -v) already installed, skipping."
fi

# ── Install Composer ─────────────────────────────────────────────────
if ! command -v composer &>/dev/null; then
  echo "==> Installing Composer..."
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
  chmod +x /usr/local/bin/composer
else
  echo "==> Composer already installed, skipping."
fi

# ── MySQL setup ──────────────────────────────────────────────────────
echo "==> Configuring MySQL..."
systemctl enable mysql
systemctl start mysql

# Create database and user (idempotent — IF NOT EXISTS)
mysql -e "CREATE DATABASE IF NOT EXISTS preshift CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Check if user exists before creating
if ! mysql -e "SELECT User FROM mysql.user WHERE User='preshift'" | grep -q preshift; then
  echo "IMPORTANT: Set a secure password for the MySQL 'preshift' user."
  echo "Run the following after this script completes:"
  echo "  mysql -e \"CREATE USER 'preshift'@'127.0.0.1' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';\""
  echo "  mysql -e \"GRANT ALL PRIVILEGES ON preshift.* TO 'preshift'@'127.0.0.1'; FLUSH PRIVILEGES;\""
  echo ""
  echo "Then update /var/www/preshift/api/.env with the password."
  echo ""

  # Create with a placeholder — user MUST change this
  TEMP_PASS="CHANGE_ME_$(openssl rand -hex 12)"
  mysql -e "CREATE USER 'preshift'@'127.0.0.1' IDENTIFIED BY '${TEMP_PASS}';"
  mysql -e "GRANT ALL PRIVILEGES ON preshift.* TO 'preshift'@'127.0.0.1'; FLUSH PRIVILEGES;"
  echo "Temporary MySQL password: ${TEMP_PASS}"
  echo "SAVE THIS — you will need it for the .env file."
else
  echo "==> MySQL user 'preshift' already exists, skipping."
fi

# ── Create app user ──────────────────────────────────────────────────
if ! id "${APP_USER}" &>/dev/null; then
  echo "==> Creating system user '${APP_USER}'..."
  useradd -r -m -d /home/${APP_USER} -s /bin/bash ${APP_USER}
else
  echo "==> User '${APP_USER}' already exists, skipping."
fi

# ── Create app directory ─────────────────────────────────────────────
echo "==> Setting up application directory..."
mkdir -p ${APP_DIR}
chown ${APP_USER}:${APP_USER} ${APP_DIR}

# ── Create log directory ─────────────────────────────────────────────
mkdir -p ${LOG_DIR}
chown ${APP_USER}:${APP_USER} ${LOG_DIR}

# ── Clone or update repo ─────────────────────────────────────────────
if [ ! -d "${APP_DIR}/.git" ]; then
  echo "==> Repository not found at ${APP_DIR}."
  echo "Clone your repo manually:"
  echo "  sudo -u ${APP_USER} git clone <your-repo-url> ${APP_DIR}"
  echo ""
  echo "Then re-run this script or continue manually."
else
  echo "==> Repository already present at ${APP_DIR}."
fi

# ── PHP-FPM pool configuration ───────────────────────────────────────
echo "==> Configuring PHP-FPM pool for ${APP_USER}..."
cat > /etc/php/${PHP_VERSION}/fpm/pool.d/preshift.conf <<FPMEOF
[preshift]
user = ${APP_USER}
group = ${APP_USER}
listen = /run/php/php${PHP_VERSION}-fpm-preshift.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500

php_admin_value[error_log] = ${LOG_DIR}/php-fpm.error.log
php_admin_flag[log_errors] = on
FPMEOF

# ── Laravel directory permissions ────────────────────────────────────
echo "==> Setting directory permissions..."
if [ -d "${APP_DIR}/api" ]; then
  mkdir -p ${APP_DIR}/api/storage/framework/{sessions,views,cache}
  mkdir -p ${APP_DIR}/api/bootstrap/cache
  chown -R ${APP_USER}:${APP_USER} ${APP_DIR}/api/storage
  chown -R ${APP_USER}:${APP_USER} ${APP_DIR}/api/bootstrap/cache
  chmod -R 775 ${APP_DIR}/api/storage
  chmod -R 775 ${APP_DIR}/api/bootstrap/cache
fi

# ── Nginx configuration ──────────────────────────────────────────────
echo "==> Installing Nginx configuration..."
if [ -f "${APP_DIR}/deploy/nginx/preshift86.conf" ]; then
  cp ${APP_DIR}/deploy/nginx/preshift86.conf /etc/nginx/sites-available/preshift86.conf
  ln -sf /etc/nginx/sites-available/preshift86.conf /etc/nginx/sites-enabled/preshift86.conf
  rm -f /etc/nginx/sites-enabled/default
  nginx -t && systemctl reload nginx
else
  echo "   Nginx config not found at ${APP_DIR}/deploy/nginx/preshift86.conf"
  echo "   You'll need to copy it manually after cloning the repo."
fi

# ── Supervisor configuration ─────────────────────────────────────────
echo "==> Installing Supervisor configuration..."
if [ -d "${APP_DIR}/deploy/supervisor" ]; then
  cp ${APP_DIR}/deploy/supervisor/*.conf /etc/supervisor/conf.d/
  supervisorctl reread
  supervisorctl update
else
  echo "   Supervisor configs not found. Copy manually after cloning."
fi

# ── Firewall (UFW) ──────────────────────────────────────────────────
echo "==> Configuring firewall..."
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# ── Enable services ──────────────────────────────────────────────────
echo "==> Enabling services..."
systemctl enable nginx
systemctl enable php${PHP_VERSION}-fpm
systemctl enable supervisor
systemctl restart php${PHP_VERSION}-fpm
systemctl restart nginx
systemctl restart supervisor

echo ""
echo "============================================"
echo "  Server provisioning complete!"
echo "============================================"
echo ""
echo "Next steps:"
echo "  1. Clone your repo to ${APP_DIR} (if not already done)"
echo "  2. Copy deploy/.env.production.example to ${APP_DIR}/api/.env"
echo "  3. Fill in APP_KEY, DB_PASSWORD, REVERB_APP_KEY, REVERB_APP_SECRET"
echo "  4. cd ${APP_DIR}/api && composer install --no-dev --optimize-autoloader"
echo "  5. php artisan key:generate"
echo "  6. php artisan migrate --force"
echo "  7. php artisan config:cache && php artisan route:cache && php artisan view:cache"
echo "  8. cd ${APP_DIR}/client && npm ci && npm run build"
echo "  9. Set up SSL: certbot --nginx -d preshift86.com -d www.preshift86.com"
echo " 10. supervisorctl restart all"
echo ""
