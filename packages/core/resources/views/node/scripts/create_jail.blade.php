{{ '#!/bin/bash' }}
# Exit on error
set -e

JAIL_USER="{{ $userName }}"
JAILBASE="{{ $userRootDir }}"
HOMEDIR="{{ $userHomeDir }}"
GUID="{{ $userGuid }}"

echo "Creating jail for user $JAIL_USER at $JAILBASE"

if getent group "$JAIL_USER" >/dev/null; then
    EXISTING_GID="$(getent group "$JAIL_USER" | cut -d: -f3)"
    if [ "$EXISTING_GID" != "$GUID" ]; then
        echo "Group $JAIL_USER already exists with GID $EXISTING_GID, expected $GUID" >&2
        exit 1
    fi
elif getent group "$GUID" >/dev/null; then
    EXISTING_GROUP="$(getent group "$GUID" | cut -d: -f1)"
    echo "GID $GUID already belongs to group $EXISTING_GROUP" >&2
    exit 1
fi

if getent passwd "$JAIL_USER" >/dev/null; then
    EXISTING_UID="$(getent passwd "$JAIL_USER" | cut -d: -f3)"
    if [ "$EXISTING_UID" != "$GUID" ]; then
        echo "User $JAIL_USER already exists with UID $EXISTING_UID, expected $GUID" >&2
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

if ! getent group "$JAIL_USER" >/dev/null; then
    groupadd -g "$GUID" "$JAIL_USER"
fi

if ! getent passwd "$JAIL_USER" >/dev/null; then
    useradd -u "$GUID" -g "$JAIL_USER" -d "$HOMEDIR" -m -s /bin/bash "$JAIL_USER"
fi

# Initialize jail with basic shells, editors, netutils and transfer tools.
jk_init -j "$JAILBASE" basicshell jk_lsh editors netutils sftp scp rsync

# Create user inside jail
jk_jailuser -m -j "$JAILBASE" "$JAIL_USER"

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
chown -R "$JAIL_USER:$JAIL_USER" "$HOMEDIR/web"
chown -R "$JAIL_USER:$JAIL_USER" "$HOMEDIR/logs"
