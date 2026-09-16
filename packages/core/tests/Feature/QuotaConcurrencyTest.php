<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Plan;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Real committed fixtures for independent connections; only these exact IDs are removed. */
class QuotaConcurrencyTest extends TestCase
{
    public function test_two_bookings_with_preexisting_snapshots_cannot_exceed_one_slot(): void
    {
        $this->assertSame('mariadb', DB::connection()->getDriverName());
        $tenant = $plan = null;
        $users = $processes = $inputs = [];
        try {
            Model::withoutEvents(function () use (&$tenant, &$plan, &$users): void {
                $definition = Resource::query()->where('key', 'users')->where('type', 'tenant')->firstOrFail();
                $plan = Plan::query()->create(['name' => 'Concurrent quota '.str()->ulid()]);
                $plan->resources()->attach($definition, ['limit' => 1]);
                $tenant = Tenant::query()->create(['name' => 'Concurrent quota', 'plan_id' => $plan->id]);
                for ($i = 0; $i < 4; $i++) {
                    $users[] = User::query()->create([
                        'first_name' => 'Concurrent', 'last_name' => 'Quota',
                        'email' => 'quota-race-'.str()->ulid().'@example.test', 'password' => 'test-password',
                    ]);
                }
                foreach (array_slice($users, 0, 2) as $user) {
                    $user->tenants()->attach($tenant, ['role_id' => null]);
                }
            });

            $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$started = false;
try {
    // A caller that owns an outer transaction must retry that entire transaction,
    // not just its quota savepoint, if MariaDB rejects a stale snapshot (1020).
    Illuminate\Support\Facades\DB::transaction(function () use ($argv, &$started) {
        $tenant = Froxlor\Core\Models\Tenant::findOrFail($argv[1]);
        $actor = Froxlor\Core\Models\User::findOrFail($argv[2]);
        $target = Froxlor\Core\Models\User::findOrFail($argv[3]);
        Illuminate\Support\Facades\DB::table('tenant_usage')->where('tenant_id', $tenant->id)->get();
        if (!$started) {
            $started = true;
            echo "READY\n";
            flush();
            fgets(STDIN);
        }
        Froxlor\Core\Support\Resource::addUsage($tenant, $target, $actor);
    }, 3);
    exit(0);
} catch (Froxlor\Core\Exceptions\ResourceLimitException) {
    exit(3);
} catch (Throwable $error) {
    fwrite(STDERR, get_class($error).': '.$error->getMessage()."\n".$error->getTraceAsString());
    exit(4);
}
PHP;
            for ($i = 0; $i < 2; $i++) {
                $inputs[$i] = new InputStream;
                $processes[$i] = new Process([PHP_BINARY, '-r', $script, $tenant->id, $users[$i]->id, $users[$i + 2]->id], base_path());
                $processes[$i]->setTimeout(15)->setInput($inputs[$i])->start();
                $ready = $processes[$i]->waitUntil(fn ($type, $output) => str_contains($output, 'READY'));
                $this->assertTrue($ready, $processes[$i]->getErrorOutput());
            }
            foreach ($inputs as $input) {
                $input->write("GO\n");
                $input->close();
            }
            $codes = array_map(fn (Process $process) => $process->wait(), $processes);
            sort($codes);
            $this->assertSame([0, 3], $codes, implode("\n", array_map(fn (Process $process) => $process->getErrorOutput(), $processes)));
            $this->assertSame(1, DB::table('tenant_usage')->where('tenant_id', $tenant->id)->count());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            if ($tenant !== null) {
                DB::table('tenant_usage')->where('tenant_id', $tenant->id)->delete();
                DB::table('tenant_user')->where('tenant_id', $tenant->id)->delete();
                DB::table('tenants')->where('id', $tenant->id)->delete();
            }
            if ($plan !== null) {
                DB::table('plan_resource')->where('plan_id', $plan->id)->delete();
                DB::table('plans')->where('id', $plan->id)->delete();
            }
            DB::table('users')->whereIn('id', array_map(fn (User $user) => $user->id, $users))->delete();
        }
    }
}
