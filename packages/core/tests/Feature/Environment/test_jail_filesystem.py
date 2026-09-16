"""Run as root ONLY on the isolated Docker node. Creates/removes its own temp trees."""
import base64
import json
import os
from pathlib import Path
import shutil
import tempfile
import subprocess
import uuid
import pwd
import grp
import types
import unittest
from unittest.mock import patch

jail = types.ModuleType("jail_helper")
exec(compile(base64.b64decode(os.environ["FROXLOR_JAIL_HELPER_B64"]), "reconcile_jail.py", "exec"), jail.__dict__)


class JailFilesystemTest(unittest.TestCase):
    def setUp(self):
        self.base = Path(tempfile.mkdtemp(prefix="froxlor-jail-test-", dir="/var/lib"))
        self.root = self.base / "01K00000000000000000000000"
        self.root.mkdir()
        self.stage = self.base / "stage"
        self.stage.mkdir()
        self.payload = {"root": str(self.root), "user": "usr1", "guid": 10001,
                        "plan": {"binaries": [], "users": {}, "providers": {}}}
        (self.root / "etc").mkdir()
        (self.root / "etc/passwd").write_text("root:x:0:0::/root:/bin/bash\nusr1:!:10001:10001::/home:/bin/bash\n")
        (self.root / "etc/group").write_text("root:x:0:\nusr1:x:10001:\n")

    def tearDown(self):
        for line in Path('/proc/self/mountinfo').read_text().splitlines():
            if line.split()[4].startswith(str(self.base) + '/'):
                raise RuntimeError('Preserving test tree with remaining mount: ' + str(self.base))
        shutil.rmtree(self.base)

    def staged(self, path="/usr/bin/example", contents=b"binary v1"):
        target = self.stage / path.lstrip("/")
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(contents)
        target.chmod(0o755)
        self.payload["plan"]["binaries"] = [path]
        return target

    def user(self, uid=20001, shell="/bin/bash"):
        self.payload["plan"]["users"] = {"worker": {"name": "worker", "uid": uid, "gid": uid, "home": "/home/worker", "shell": shell}}

    def apply(self):
        jail.apply_state(self.root, self.payload, self.stage)

    def test_binary_add_update_delete_and_idempotence(self):
        source = self.staged()
        self.apply()
        self.apply()
        target = self.root / "usr/bin/example"
        self.assertEqual(target.read_bytes(), b"binary v1")
        source.write_bytes(b"binary v2")
        self.apply()
        self.assertEqual(target.read_bytes(), b"binary v2")
        source.unlink()
        self.payload["plan"]["binaries"] = []
        self.apply()
        self.assertFalse(target.exists())

    def test_borrowed_base_binary_is_never_overwritten_or_deleted(self):
        source = self.staged()
        target = self.root / "usr/bin/example"
        target.parent.mkdir(parents=True)
        target.write_bytes(b"base")
        self.apply()
        self.assertEqual(target.read_bytes(), b"base")
        source.unlink()
        self.payload["plan"]["binaries"] = []
        self.apply()
        self.assertEqual(target.read_bytes(), b"base")

    def test_managed_drift_rejects_removal(self):
        source = self.staged()
        self.apply()
        target = self.root / "usr/bin/example"
        target.write_bytes(b"manually changed")
        source.unlink()
        self.payload["plan"]["binaries"] = []
        with self.assertRaisesRegex(RuntimeError, "drift"):
            self.apply()
        self.assertTrue(target.exists())

    def test_symlink_parent_escape_is_rejected(self):
        self.staged()
        (self.root / "usr").symlink_to(self.base)
        with self.assertRaisesRegex(RuntimeError, "escapes"):
            self.apply()

    def test_writable_parent_is_rejected(self):
        self.staged()
        (self.root / "usr").mkdir(mode=0o777)
        (self.root / "usr").chmod(0o777)
        with self.assertRaisesRegex(RuntimeError, "root-owned"):
            self.apply()

    def test_account_add_change_remove_preserves_customer_home(self):
        home = self.root / "home/worker"
        home.mkdir(parents=True)
        (home / "data").write_text("keep")
        self.user()
        self.apply()
        self.apply()
        self.assertIn("worker:!:20001:20001:", (self.root / "etc/passwd").read_text())
        self.user(20002, "/usr/sbin/nologin")
        self.apply()
        self.assertIn("worker:!:20002:20002:", (self.root / "etc/passwd").read_text())
        self.payload["plan"]["users"] = {}
        self.apply()
        self.assertNotIn("worker:", (self.root / "etc/passwd").read_text())
        self.assertEqual((home / "data").read_text(), "keep")
        self.assertIn("usr1:", (self.root / "etc/passwd").read_text())

    def test_unmanaged_or_primary_accounts_cannot_be_adopted(self):
        self.user()
        with (self.root / "etc/passwd").open("a") as file:
            file.write("worker:!:20001:20001::/:/bin/bash\n")
        with self.assertRaisesRegex(RuntimeError, "Unmanaged"):
            self.apply()
        self.user(10001)
        with self.assertRaisesRegex(RuntimeError, "Protected"):
            self.apply()

    def test_account_database_symlinks_are_rejected(self):
        self.user()
        (self.root / "etc/shadow").symlink_to(self.base / "outside")
        with self.assertRaisesRegex(RuntimeError, "Symlink account"):
            self.apply()

    def test_write_ahead_journal_allows_retry_after_partial_write(self):
        self.user()
        self.staged()
        original = jail.atomic_write
        def failing(path, contents, mode):
            if path.name == "group":
                raise RuntimeError("simulated interruption")
            original(path, contents, mode)
        with patch.object(jail, "atomic_write", failing):
            with self.assertRaisesRegex(RuntimeError, "interruption"):
                self.apply()
        self.apply()
        self.assertIn("worker:", (self.root / "etc/group").read_text())
        self.assertTrue((self.root / "usr/bin/example").exists())

    def test_shared_dependency_lives_until_last_consumer_is_removed(self):
        first = self.staged("/usr/bin/first")
        second = self.staged("/usr/bin/second")
        library = self.staged("/usr/lib/libexample.so")
        self.payload["plan"]["binaries"] = ["/usr/bin/first", "/usr/bin/second"]
        self.apply()
        first.unlink()
        self.payload["plan"]["binaries"] = ["/usr/bin/second"]
        self.apply()
        self.assertTrue((self.root / "usr/lib/libexample.so").exists())
        second.unlink()
        library.unlink()
        self.payload["plan"]["binaries"] = []
        self.apply()
        self.assertFalse((self.root / "usr/lib/libexample.so").exists())

    def test_real_jailkit_copy_with_dependencies(self):
        self.payload["plan"]["binaries"] = ["/usr/bin/printf"]
        jail.stage_binaries(self.payload["plan"]["binaries"], self.stage)
        self.apply()
        self.assertTrue((self.root / "usr/bin/printf").exists())
        self.assertGreater(len(json.loads((self.root / ".froxlor-jail-state.json").read_text())["files"]), 1)

    @unittest.skipUnless(os.environ.get("FROXLOR_JAIL_CREATE_SCRIPT_B64"), "Rendered creation template required")
    def test_real_create_reconcile_update_remove_and_delete(self):
        # A distinct sibling root, no database or existing jail is touched.
        root = self.base / "01k00000000000000000000001"
        name = "fjt" + uuid.uuid4().hex[:10]
        used = {entry.pw_uid for entry in pwd.getpwall()} | {entry.gr_gid for entry in grp.getgrall()}
        guid = next(value for value in range(410000, 420000) if value not in used)
        payload = {"root": str(root), "user": name, "guid": guid,
                   "plan": {"binaries": ["/usr/bin/printf"], "users": {}, "providers": {"test/package:runtime": "1"}}}
        script = base64.b64decode(os.environ["FROXLOR_JAIL_CREATE_SCRIPT_B64"]).decode()
        script = script.replace("TEST_JAIL_ROOT", str(root)).replace("TEST_JAIL_HOME", str(root / "home"))
        script = script.replace("TEST_JAIL_USER", name).replace("123456789", str(guid))
        try:
            result = subprocess.run(["/bin/bash", "-se"], input=script, text=True, capture_output=True, timeout=180)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(result.stdout, "FROXLOR_JAIL_CREATED")
            self.assertEqual(pwd.getpwnam(name).pw_uid, guid)
            jail.reconcile(payload)
            payload["plan"]["users"] = {"worker": {"name": "worker", "uid": guid + 1, "gid": guid + 1, "home": "/home/worker", "shell": "/bin/bash"}}
            jail.reconcile(payload)
            self.assertIn("worker:", (root / "etc/passwd").read_text())
            payload["plan"]["users"]["worker"]["shell"] = "/usr/sbin/nologin"
            jail.reconcile(payload)
            self.assertIn("/home/worker:/usr/sbin/nologin", (root / "etc/passwd").read_text())
            payload["plan"]["users"] = {}
            payload["plan"]["binaries"] = []
            jail.reconcile(payload)
            self.assertNotIn("worker:", (root / "etc/passwd").read_text())
            self.assertTrue((root / "home/web").is_dir())
        finally:
            jail.delete_jail({**payload, "cleanup_token": "test-creation-token"})
        self.assertFalse(root.exists())
        with self.assertRaises(KeyError):
            pwd.getpwnam(name)


unittest.main()
