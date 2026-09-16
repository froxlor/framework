{{ '#!/bin/bash' }}
# Exit on error
set -euo pipefail

JAILUSER={!! escapeshellarg($userName) !!}
JAILBASE={!! escapeshellarg($userRootDir) !!}
HOMEDIR={!! escapeshellarg($userHomeDir) !!}
GUID={!! escapeshellarg((string) $userGuid) !!}

# Keep stdout exclusively for the success marker, including on verbose Jailkit versions.
exec 3>&1
exec 1>&2

# Refuse adoption of existing host identities, even with matching numeric IDs.
! getent passwd "$JAILUSER" >/dev/null || exit 1
! getent group "$JAILUSER" >/dev/null || exit 1

# Every ancestor must be an actual root-owned directory, not a customer-controlled link.
parent=$(dirname "$JAILBASE")
while [ "$parent" != / ]; do
  if [ -e "$parent" ] || [ -L "$parent" ]; then
    [ -d "$parent" ] && [ ! -L "$parent" ] && [ "$(stat -c %u "$parent")" = 0 ] || exit 1
    mode=$(stat -c %a "$parent")
    (( (8#$mode & 8#022) == 0 )) || exit 1
  fi
    parent=$(dirname "$parent")
done
mkdir -p "$(dirname "$JAILBASE")"

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
mkdir "$JAILBASE"
chown root:root "$JAILBASE"
chmod 755 "$JAILBASE"
printf %s {!! escapeshellarg($creationToken) !!} > "$JAILBASE/.froxlor-creation-token"
chmod 600 "$JAILBASE/.froxlor-creation-token"

if ! getent group "$JAILUSER" >/dev/null; then
    groupadd -g "$GUID" "$JAILUSER"
fi

if ! getent passwd "$JAILUSER" >/dev/null; then
    useradd -u "$GUID" -g "$JAILUSER" -d "$HOMEDIR" -m -s /bin/bash "$JAILUSER"
fi

# Initialize jail with basic shells, editors, netutils and transfer tools.
jk_init -j "$JAILBASE" basicshell jk_lsh editors netutils sftp scp rsync

# Create user inside jail. The account already has its home below the jail
# (useradd -m above), so --move would try to copy the home onto itself.
jk_jailuser -j "$JAILBASE" "$JAILUSER"

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

printf FROXLOR_JAIL_CREATED >&3
