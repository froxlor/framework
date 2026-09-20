"""Root-side, package-declared jail reconciliation. No code from inside the jail runs.

The root-owned journal records both sides of a pending write before touching it.
A killed run can converge on retry; drift and unmanaged identities fail closed.
"""
import base64
import fcntl
import hashlib
import json
import os
import pwd
import re
import shutil
import stat
import subprocess
import sys
import tempfile
from pathlib import Path


def require(condition, message):
    if not condition:
        raise RuntimeError(message)


def trusted_directory(path):
    path = Path(path)
    for directory in [path, *path.parents]:
        info = directory.lstat()
        require(stat.S_ISDIR(info.st_mode) and info.st_uid == 0 and not info.st_mode & 0o022,
                "Jail path must consist of root-owned, non-writable real directories")


def fingerprint(path):
    if path.is_symlink():
        return "link:" + os.readlink(path)
    if not path.exists():
        return None
    info = path.stat()
    require(stat.S_ISREG(info.st_mode) and info.st_uid == 0 and not info.st_mode & 0o022,
            "Managed file is not a protected regular file")
    return "sha256:" + hashlib.sha256(path.read_bytes()).hexdigest()


def destination(root, name, create=False):
    require(name.startswith("/") and ".." not in name.split("/") and "\0" not in name,
            "Invalid jail-relative path")
    parent = (root / name.lstrip("/")).parent.resolve()
    require(parent == root or root in parent.parents, "Jail symlink escapes the jail")
    missing = []
    cursor = parent
    while not cursor.exists():
        missing.append(cursor)
        cursor = cursor.parent
    trusted_directory(cursor)
    if create:
        for directory in reversed(missing):
            directory.mkdir(mode=0o755)
    return parent / Path(name).name


def atomic_write(path, contents, mode):
    fd, temporary = tempfile.mkstemp(prefix=".froxlor-", dir=path.parent)
    try:
        with os.fdopen(fd, "wb") as stream:
            stream.write(contents)
            stream.flush()
            os.fchmod(stream.fileno(), mode)
            os.fsync(stream.fileno())
        os.replace(temporary, path)
        directory_fd = os.open(path.parent, os.O_DIRECTORY)
        try:
            os.fsync(directory_fd)
        finally:
            os.close(directory_fd)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def stage_binaries(paths, stage):
    for path in paths:
        require(re.fullmatch(r"/(?:usr/)?s?bin/[a-zA-Z0-9_+.-]+", path) and Path(path).name not in (".", ".."),
                "Invalid executable path")
        source = Path(path).resolve(strict=True)
        trusted_directory(source.parent)
        info = source.stat()
        require(stat.S_ISREG(info.st_mode) and info.st_uid == 0 and info.st_mode & 0o111
                and not info.st_mode & 0o6022, "Unsafe host executable")
        # Jailkit only writes to a new root-owned staging jail, never to customer paths.
        subprocess.run(["/usr/sbin/jk_cp", "-f", "-j", str(stage), path], check=True,
                       stdout=subprocess.DEVNULL, timeout=900)


def account_lines(definition):
    name = definition["name"]
    return {
        "passwd": f'{name}:!:{definition["uid"]}:{definition["gid"]}::{definition["home"]}:{definition["shell"]}',
        "group": f'{name}:x:{definition["gid"]}:',
        "shadow": f"{name}:!:0:0:99999:7:::",
        "gshadow": f"{name}:!::",
    }


def reconcile(payload):
    root = Path(payload["root"])
    require(re.fullmatch(r"/(?:[a-zA-Z0-9_-][a-zA-Z0-9_.-]*/)+[0-9A-HJKMNP-TV-Z]{26}", str(root), re.I),
            "Invalid jail root")
    trusted_directory(root)
    host_user = pwd.getpwnam(payload["user"])
    require(host_user.pw_uid == payload["guid"] and host_user.pw_gid == payload["guid"]
            and Path(host_user.pw_dir).resolve().is_relative_to(root), "Primary host identity does not match jail")
    lock_path = destination(root, "/.froxlor-jail.lock")
    lock_fd = os.open(lock_path, os.O_CREAT | os.O_RDWR | os.O_NOFOLLOW, 0o600)
    try:
        require(os.fstat(lock_fd).st_uid == 0 and os.fstat(lock_fd).st_nlink == 1, "Unsafe jail lock")
        fcntl.flock(lock_fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
        # Jailkit rejects any jail below world-writable /tmp, even a private subdir.
        trusted_directory(Path('/var/lib'))
        with tempfile.TemporaryDirectory(prefix="froxlor-jail-", dir='/var/lib') as temporary:
            stage = Path(temporary)
            stage_binaries(payload["plan"]["binaries"], stage)
            apply_state(root, payload, stage)
    finally:
        os.close(lock_fd)


def delete_jail(payload):
    root = Path(payload["root"])
    require(re.fullmatch(r"/(?:[a-zA-Z0-9_-][a-zA-Z0-9_.-]*/)+[0-9A-HJKMNP-TV-Z]{26}", str(root), re.I), "Invalid jail root")
    require(payload["guid"] > 0 and payload["user"] != "root", "Protected host identity")
    trusted_directory(root if root.exists() else root.parent)
    if payload.get("cleanup_token"):
        marker = root / ".froxlor-creation-token"
        if not marker.exists():
            return
        require(not marker.is_symlink() and marker.stat().st_uid == 0
                and marker.read_text() == payload["cleanup_token"], "Creation cleanup ownership mismatch")
    try:
        user = pwd.getpwnam(payload["user"])
    except KeyError:
        user = None
    if user is not None:
        require(user.pw_uid == payload["guid"] and user.pw_gid == payload["guid"]
                and Path(user.pw_dir).resolve().is_relative_to(root), "Primary host identity does not match jail")
    else:
        require(not root.exists() or (root / ".froxlor-jail-state.json").is_file()
                or (root / ".froxlor-creation-token").is_file(), "Unknown jail without primary account")
    import grp
    try:
        group = grp.getgrnam(payload["user"])
    except KeyError:
        group = None
    require(group is None or group.gr_gid == payload["guid"], "Primary host group does not match jail")
    lock_fd = None
    if root.exists():
        lock_fd = os.open(root / ".froxlor-jail.lock", os.O_CREAT | os.O_RDWR | os.O_NOFOLLOW, 0o600)
        fcntl.flock(lock_fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
    try:
        # No lazy unmount: never recursively delete through still-mounted filesystems.
        mounts = []
        for line in Path("/proc/self/mountinfo").read_text().splitlines():
            target = line.split()[4]
            if target == str(root) or target.startswith(str(root) + "/"):
                require(target in (str(root / "dev/pts"), str(root / "proc")), "Unexpected jail mount; manual intervention required")
                mounts.append(target)
        if user is not None:
            subprocess.run(["pkill", "-u", str(payload["guid"])], check=False)
        for target in sorted(mounts, key=len, reverse=True):
            subprocess.run(["umount", target], check=True, timeout=30)
        if user is not None:
            subprocess.run(["userdel", payload["user"]], check=True, timeout=30)
        # Debian may remove the private group as part of userdel (USERGROUPS_ENAB).
        try:
            group = grp.getgrnam(payload["user"])
        except KeyError:
            group = None
        if group is not None:
            require(group.gr_gid == payload["guid"], "Primary host group changed during deletion")
            subprocess.run(["groupdel", payload["user"]], check=True, timeout=30)
        if root.exists():
            shutil.rmtree(root)
    finally:
        if lock_fd is not None:
            os.close(lock_fd)


def apply_state(root, payload, stage):
    """Separated from transport/staging for Linux filesystem regression tests."""
    state_path = destination(root, "/.froxlor-jail-state.json")
    require(not state_path.is_symlink(), "Unsafe jail journal")
    state = {"version": 1, "files": {}, "users": {}, "entrypoints": [], "directories": []}
    if state_path.exists():
        fingerprint(state_path)
        state = json.loads(state_path.read_text())
        require(state.get("version") == 1, "Unsupported jail journal version")
    plan = payload["plan"]
    desired_users = plan["users"] or {}
    require(isinstance(desired_users, dict), "Invalid user map")
    users = state["users"]
    new_users = {}
    for name, user in desired_users.items():
        require(name == user["name"] and re.fullmatch(r"[a-z_][a-z0-9_-]{0,30}", name)
                and name not in ("root", payload["user"]), "Protected jail user")
        require(all(isinstance(user[key], int) and 0 < user[key] <= 2147483647
                    and user[key] != payload["guid"] for key in ("uid", "gid")), "Protected jail UID/GID")
        for key in ("home", "shell"):
            require(re.fullmatch(r"/[a-zA-Z0-9_./+-]*", user[key]) and ".." not in user[key].split("/"),
                    "Invalid account path")
        new_users[name] = account_lines(user)

    account_writes = {}
    for kind in ("passwd", "group", "shadow", "gshadow"):
        path = destination(root, "/etc/" + kind)
        require(not path.is_symlink(), "Symlink account database")
        fingerprint(path)
        lines = path.read_text().splitlines() if path.exists() else []
        records = {}
        for line in lines:
            name = line.split(":", 1)[0]
            require(name not in records, "Duplicate account database entry")
            records[name] = line
        for name in set(users) | set(new_users):
            current = records.get(name)
            allowed = users.get(name, {}).get(kind, [])
            require(current is None or current in allowed, "Unmanaged or modified jail identity: " + name)
        # Never alias another account's UID/GID, including a shared primary identity.
        if kind in ("passwd", "group"):
            ids = {}
            for name, line in records.items():
                if name not in users:
                    ids[int(line.split(":")[2])] = name
            for name, user in desired_users.items():
                identifier = user["uid" if kind == "passwd" else "gid"]
                require(identifier not in ids or ids[identifier] == name, "Jail UID/GID collision")
                ids[identifier] = name
        for name in users:
            records.pop(name, None)
        for name, definition in new_users.items():
            records[name] = definition[kind]
        # No account changes means do not manufacture missing shadow files.
        if users or new_users:
            account_writes[kind] = (path, ("\n".join(records.values()) + "\n").encode())

    writes = {}
    desired_directories = plan.get("directories", {})
    require(isinstance(desired_directories, dict), "Invalid directory map")
    for name, mode in desired_directories.items():
        require(name.startswith('/') and '..' not in name.split('/') and isinstance(mode, int) and 0 <= mode <= 0o777,
                "Invalid jail directory")
        directory = destination(root, name, create=True)
        if not directory.exists():
            directory.mkdir(mode=mode)
        require(directory.is_dir() and not directory.is_symlink(), "Managed jail directory is unsafe")
        os.chmod(directory, mode)

    desired_files = plan.get("files", {})
    require(isinstance(desired_files, dict), "Invalid file map")
    for name, definition in desired_files.items():
        require(name not in ("/etc/passwd", "/etc/group", "/etc/shadow", "/etc/gshadow")
                and not name.startswith("/.froxlor"), "Protected jail file")
        require(isinstance(definition, dict) and isinstance(definition.get("content"), str)
                and isinstance(definition.get("mode"), int) and 0 <= definition["mode"] <= 0o777,
                "Invalid jail file")
        path = destination(root, name, create=True)
        require(not path.is_symlink(), "Managed jail file is a symlink")
        content = definition["content"].encode()
        previous = fingerprint(path)
        new_fingerprint = "sha256:" + hashlib.sha256(content).hexdigest()
        require(previous is None or previous in state["files"].get(name, []), "Managed file drift: " + name)
        writes[name] = (content, new_fingerprint, False, definition["mode"])
        state["files"].setdefault(name, [])

    environment = plan.get("environment", {})
    require(isinstance(environment, dict), "Invalid environment map")
    if environment:
        lines = [f'{key}="{value.replace(chr(92), chr(92)+chr(92)).replace(chr(34), chr(92)+chr(34))}"' for key, value in sorted(environment.items())]
        path = destination(root, "/etc/environment", create=True)
        content = ("\n".join(lines) + "\n").encode()
        previous = fingerprint(path)
        new_fingerprint = "sha256:" + hashlib.sha256(content).hexdigest()
        require(previous is None or previous in state["files"].get("/etc/environment", []), "Managed environment drift")
        writes["/etc/environment"] = (content, new_fingerprint, False, 0o644)
    staged_names = set()
    protected = {"/etc/passwd", "/etc/group", "/etc/shadow", "/etc/gshadow"}
    for source in sorted(stage.rglob("*")):
        if source.is_dir() and not source.is_symlink():
            continue
        name = "/" + str(source.relative_to(stage))
        staged_names.add(name)
        require(name not in protected and not name.startswith("/.froxlor"), "Protected staged path")
        path = destination(root, name)
        if path.is_symlink():
            resolved = path.resolve()
            require(resolved.is_relative_to(root), "Existing symlink escapes jail")
            trusted_directory(resolved.parent)
        previous = fingerprint(path)
        if source.is_symlink():
            target = os.readlink(source)
            # Absolute symlinks inside a chroot become relative for safe host-side access.
            target_path = root / target.lstrip("/") if target.startswith("/") else path.parent / target
            target_path = target_path.resolve()
            require(target_path == root or root in target_path.parents, "Unsafe staged symlink")
            target = os.path.relpath(target_path, path.parent)
            new_fingerprint = "link:" + target
            contents = target
        else:
            require(source.is_file(), "Unsupported staged file type")
            contents = source.read_bytes()
            new_fingerprint = "sha256:" + hashlib.sha256(contents).hexdigest()
        if previous is not None and name not in state["files"]:
            # Existing base-jail files remain borrowed, never adopted or overwritten.
            continue
        require(previous is None or previous in state["files"].get(name, []), "Managed binary drift: " + name)
        writes[name] = (contents, new_fingerprint, source.is_symlink(), source.stat().st_mode & 0o755 if not source.is_symlink() else 0)

    # The staging tree is the union of dependencies of ALL desired binaries.
    # Only files first introduced by this reconciler can be garbage-collected.
    retired = set(state["files"]) - staged_names - set(desired_files) - ({"/etc/environment"} if environment else set())
    removals = []
    for name in retired:
        if name not in state["files"] or name in writes:
            continue
        path = destination(root, name)
        current = fingerprint(path)
        require(current is None or current in state["files"][name], "Managed binary drift: " + name)
        removals.append(name)
    retired_directories = sorted(set(state.get("directories", [])) - set(desired_directories), key=len, reverse=True)

    # Write-ahead ownership journal: both old and intended contents are valid on retry.
    for name, (_, new_fingerprint, _, _) in writes.items():
        state["files"][name] = sorted(set(state["files"].get(name, []) + [new_fingerprint]))
    for name, definitions in new_users.items():
        for kind, line in definitions.items():
            state["users"].setdefault(name, {}).setdefault(kind, [])
            state["users"][name][kind] = sorted(set(state["users"][name][kind] + [line]))
    state["entrypoints"] = sorted(set(state["entrypoints"]) | set(plan["binaries"]))
    atomic_write(state_path, json.dumps(state, sort_keys=True).encode(), 0o600)

    for name, (contents, _, symlink, mode) in writes.items():
        path = destination(root, name, create=True)
        if symlink:
            with tempfile.TemporaryDirectory(prefix=".froxlor-", dir=path.parent) as temporary:
                link = Path(temporary) / "link"
                link.symlink_to(contents)
                os.replace(link, path)
        else:
            atomic_write(path, contents, mode)
    for name in removals:
        destination(root, name).unlink(missing_ok=True)
        state["files"].pop(name, None)
    for name in retired_directories:
        directory = destination(root, name)
        require(directory.is_dir() and not directory.is_symlink(), "Managed jail directory drift: " + name)
        try:
            directory.rmdir()
        except OSError:
            raise RuntimeError("Managed jail directory is not empty: " + name)
    for kind, (path, contents) in account_writes.items():
        destination(root, "/etc/" + kind, create=True)
        atomic_write(path, contents, 0o600 if kind in ("shadow", "gshadow") else 0o644)

    for name, (_, new_fingerprint, _, _) in writes.items():
        state["files"][name] = [new_fingerprint]
    state["users"] = {name: {kind: [line] for kind, line in definition.items()} for name, definition in new_users.items()}
    state["entrypoints"] = plan["binaries"]
    state["directories"] = sorted(desired_directories)
    state["providers"] = plan["providers"]
    atomic_write(state_path, json.dumps(state, sort_keys=True).encode(), 0o600)


if __name__ == "__main__":
    try:
        payload = json.loads(base64.b64decode(sys.argv[1], validate=True))
        if payload.get("operation") == "delete":
            delete_jail(payload)
        else:
            reconcile(payload)
        print("FROXLOR_JAIL_OK")
    except Exception as error:
        print("Jail reconciliation rejected: " + str(error), file=sys.stderr)
        sys.exit(1)
