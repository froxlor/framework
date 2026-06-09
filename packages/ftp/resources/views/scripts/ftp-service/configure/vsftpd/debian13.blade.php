#!/usr/bin/env bash
set -euo pipefail

cat > /etc/vsftpd.conf <<'EOF'
anonymous_enable=NO
listen=YES
listen_address={{ $ftpService->listen_address }}
listen_port={{ $ftpService->port }}
local_enable={{ $ftpService->allow_local_users ? 'YES' : 'NO' }}
write_enable={{ $ftpService->allow_write ? 'YES' : 'NO' }}
chroot_local_user={{ $ftpService->chroot_local_users ? 'YES' : 'NO' }}
allow_writeable_chroot={{ $ftpService->allow_writable_chroot ? 'YES' : 'NO' }}
pasv_enable=YES
pasv_min_port={{ $ftpService->passive_min_port }}
pasv_max_port={{ $ftpService->passive_max_port }}
EOF
