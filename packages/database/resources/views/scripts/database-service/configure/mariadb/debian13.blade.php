#!/usr/bin/env bash
set -euo pipefail

mkdir -p /etc/mysql/mariadb.conf.d

cat > /etc/mysql/mariadb.conf.d/60-froxlor.cnf <<'EOF'
[mysqld]
bind-address = {{ $databaseServer->host }}
port = {{ $databaseServer->port }}
skip-name-resolve

[client]
host = {{ $databaseServer->host }}
port = {{ $databaseServer->port }}
user = {{ $databaseServer->admin_username ?? 'root' }}
EOF
