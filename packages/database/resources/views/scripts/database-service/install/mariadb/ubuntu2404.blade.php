#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y mariadb-server mariadb-client
systemctl enable mariadb
systemctl start mariadb
