#!/usr/bin/env bash
set -euo pipefail

mkdir -p /etc/postgresql/16/main/conf.d

cat > /etc/postgresql/16/main/conf.d/60-froxlor.conf <<'EOF'
listen_addresses = '{{ $databaseServer->host }}'
port = {{ $databaseServer->port }}
EOF
