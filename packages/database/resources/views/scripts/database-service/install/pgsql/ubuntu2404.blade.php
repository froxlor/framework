#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y postgresql postgresql-client
systemctl enable postgresql
systemctl start postgresql
