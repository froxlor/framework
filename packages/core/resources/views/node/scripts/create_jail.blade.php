{{ '#!/bin/bash' }}
# Exit on error
set -e

USER="{{ $userName }}"
JAILBASE="{{ $userRootDir }}"
HOMEDIR="{{ $userHomeDir }}"
GUID="{{ $userGuid }}"

echo "Creating jail for user $USER at $JAILBASE"

# Create base structure
mkdir -p $JAILBASE
chown root:root $JAILBASE
chmod 755 $JAILBASE

# Create user with jailed home directory
groupadd -g "$GUID" "$USER"
useradd -u "$GUID" -g "$USER" -d "$HOMEDIR" -m -s /bin/bash "$USER"

# Initialize jail with basic shells, editors, netutils, php etc.
jk_init -j $JAILBASE basicshell jk_lsh editors netutils sftp scp rsync

# Create user inside jail
jk_jailuser -m -j $JAILBASE $USER

# Bind-mount /proc
if ! mountpoint -q $JAILBASE/proc; then
mkdir -p $JAILBASE/proc
mount --bind /proc $JAILBASE/proc
fi

# dev/pts mount for interactive sessions
if ! mountpoint -q $JAILBASE/dev/pts; then
mkdir -p $JAILBASE/dev/pts
mount -t devpts devpts $JAILBASE/dev/pts
fi

# create dir layout
mkdir -p $JAILBASE/tmp
chmod 777 $JAILBASE/tmp
mkdir -p $HOMEDIR/web
mkdir -p $HOMEDIR/logs
chown -R $USER:$USER $HOMEDIR/web
chown -R $USER:$USER $HOMEDIR/logs
