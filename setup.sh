#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

# 1) Minimal MySQL install & start
apt-get update -qq
apt-get install -yqq --no-install-recommends \
  mysql-server-core-8.0 mysql-client-core-8.0 passwd

# 2) Ensure mysql user exists
if ! id -u mysql &>/dev/null; then
  groupadd --system mysql
  useradd --system --gid mysql --home /nonexistent \
    --shell /usr/sbin/nologin mysql
fi

# 3) Prepare data dirs
mkdir -p /var/lib/mysql /var/lib/mysql-files /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/lib/mysql-files /var/run/mysqld
chmod 750 /var/lib/mysql-files

# 4) Initialize & daemonize MySQL
mysqld --initialize-insecure --user=mysql
mysqld --daemonize --user=mysql \
      --socket=/var/run/mysqld/mysqld.sock \
      --pid-file=/var/run/mysqld/mysqld.pid

# 5) Wait for readiness
until mysqladmin --socket=/var/run/mysqld/mysqld.sock ping --silent; do sleep 1; done

# 6) Create root/DBs
mysql --socket=/var/run/mysqld/mysqld.sock -uroot <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS laravel;
CREATE DATABASE IF NOT EXISTS testing;
EOF

# 7) Install PHP CLI and utilities
apt-get install -yqq php-cli php-mbstring unzip curl git

# 8) JS deps, Composer, Flux auth, then call setup.sh
npm install

if ! command -v composer &>/dev/null; then
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
fi

if [[ -n "$FLUX_USER" && -n "$FLUX_PASS" ]]; then
  composer config --global http-basic.composer.fluxui.dev "$FLUX_USER" "$FLUX_PASS"
else
  echo "⚠️  Missing FLUX_USER/FLUX_PASS; private packages may fail"
fi

chmod +x setup.sh
./setup.sh
