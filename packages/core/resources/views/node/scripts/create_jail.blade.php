{{ '#!/bin/bash' }}
# Exit on error
set -e

JAILUSER="{{ $userName }}"
JAILBASE="{{ $userRootDir }}"
HOMEDIR="{{ $userHomeDir }}"
GUID="{{ $userGuid }}"

echo "Creating jail for user $JAILUSER at $JAILBASE"

if getent group "$JAILUSER" >/dev/null; then
    EXISTING_GID="$(getent group "$JAILUSER" | cut -d: -f3)"
    if [ "$EXISTING_GID" != "$GUID" ]; then
        echo "Group $JAILUSER already exists with GID $EXISTING_GID, expected $GUID" >&2
        exit 1
    fi
elif getent group "$GUID" >/dev/null; then
    EXISTING_GROUP="$(getent group "$GUID" | cut -d: -f1)"
    echo "GID $GUID already belongs to group $EXISTING_GROUP" >&2
    exit 1
fi

if getent passwd "$JAILUSER" >/dev/null; then
    EXISTING_UID="$(getent passwd "$JAILUSER" | cut -d: -f3)"
    if [ "$EXISTING_UID" != "$GUID" ]; then
        echo "User $JAILUSER already exists with UID $EXISTING_UID, expected $GUID" >&2
        exit 1
    fi
elif getent passwd "$GUID" >/dev/null; then
    EXISTING_USER="$(getent passwd "$GUID" | cut -d: -f1)"
    echo "UID $GUID already belongs to user $EXISTING_USER" >&2
    exit 1
fi

# Create base structure
mkdir -p "$JAILBASE"
chown root:root "$JAILBASE"
chmod 755 "$JAILBASE"

if ! getent group "$JAILUSER" >/dev/null; then
    groupadd -g "$GUID" "$JAILUSER"
fi

if ! getent passwd "$JAILUSER" >/dev/null; then
    useradd -u "$GUID" -g "$JAILUSER" -d "$HOMEDIR" -m -s /bin/bash "$JAILUSER"
fi

# Initialize jail with basic shells, editors, netutils and transfer tools.
jk_init -j "$JAILBASE" basicshell jk_lsh editors netutils sftp scp rsync

# Create user inside jail
jk_jailuser -m -j "$JAILBASE" "$JAILUSER"

# Mount a dedicated proc filesystem for the jail.
if ! mountpoint -q "$JAILBASE/proc"; then
mkdir -p "$JAILBASE/proc"
mount -t proc proc "$JAILBASE/proc" -o nosuid,nodev,noexec
fi

# dev/pts mount for interactive sessions
if ! mountpoint -q "$JAILBASE/dev/pts"; then
mkdir -p "$JAILBASE/dev/pts"
mount -t devpts devpts "$JAILBASE/dev/pts"
fi

# create dir layout
mkdir -p "$JAILBASE/tmp"
chmod 1777 "$JAILBASE/tmp"
mkdir -p "$HOMEDIR/web"
mkdir -p "$HOMEDIR/logs"
chown -R "$JAILUSER:$JAILUSER" "$HOMEDIR/web"
chown -R "$JAILUSER:$JAILUSER" "$HOMEDIR/logs"
