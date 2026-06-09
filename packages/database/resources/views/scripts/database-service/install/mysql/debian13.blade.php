#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y default-mysql-server default-mysql-client
systemctl enable mysql || systemctl enable mysqld
systemctl start mysql || systemctl start mysqld
