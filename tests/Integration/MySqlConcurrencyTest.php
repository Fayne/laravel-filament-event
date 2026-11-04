<?php

namespace Tests\Integration;

use App\Models\User;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Exceptions\InsufficientStockException;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;
use Fayne\Event\Services\ReservationService;
use Fayne\StoreLocator\Models\Store;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\PackageDatabase;
use Tests\TestCase;
use Illuminate\Database\QueryException;

class MySqlConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        if (! getenv('CMS_TEST_MYSQL_CONFIG')) {
            $this->markTestSkipped('Dedicated MySQL configuration is required.');
        }
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('pcntl is required for concurrent workers.');
        }
        parent::setUp();
        Schema::dropAllTables();
        PackageDatabase::prepare();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $config = json_decode(base64_decode(getenv('CMS_TEST_MYSQL_CONFIG')), true, flags: JSON_THROW_ON_ERROR);
        if (! preg_match('/^cms_test_[a-f0-9]{12}$/', $config['database'] ?? '')) {
            throw new \LogicException('Only a dedicated cms_test database may be used.');
        }
        $app['config']->set('database.connections.mysql', $config);
        $app['config']->set('database.connections.mysql.strict', true);
        $app['config']->set('database.default', 'mysql');
    }

    public function test_concurrent_reservations_never_oversell_event_store_or_timeslot_stock(): void
    {
        foreach ([EventStockType::Event, EventStockType::Store, EventStockType::Timeslot] as $type) {
            $event = Event::factory()->create(['stock_type' => $type, 'stock' => 1]);
            $store = Store::create(['name' => 'Concurrency Store ' . $type->value]);
            $event->stores()->attach($store, ['stock' => 1]);
            $slot = EventTimeslot::create(['event_id' => $event->id, 'store_id' => $store->id, 'date' => '2026-12-01', 'start_time' => '10:00', 'end_time' => '11:00', 'stock' => 1]);
            $user = User::factory()->create();

            $results = $this->workers(function () use ($event, $store, $slot, $user) {
                try {
                    app(ReservationService::class)->reserve($event, $user, ['quantity' => 1, 'store_id' => $store->id, 'timeslot_id' => $slot->id]);

                    return 'reserved';
                } catch (InsufficientStockException) {
                    return 'sold-out';
                }
            });

            sort($results);
            self::assertSame(['reserved', 'sold-out'], $results);
            self::assertSame(1, Order::where('event_id', $event->id)->count());
        }
    }

    public function test_concurrent_cancellations_restore_stock_only_once(): void
    {
        $event = Event::factory()->create(['stock_type' => EventStockType::Event, 'stock' => 1]);
        $user = User::factory()->create();
        $order = app(ReservationService::class)->reserve($event, $user, ['quantity' => 1]);

        $results = $this->workers(function () use ($order, $user) {
            DB::listen(function (QueryExecuted $query) {
                if (str_starts_with($query->sql, 'select') && str_contains($query->sql, '`orders`')) {
                    usleep(300000);
                }
            });
            app(ReservationService::class)->cancel($order, $user);

            return 'cancelled';
        });

        self::assertSame(['cancelled', 'cancelled'], $results);
        self::assertSame(1, $event->fresh()->stock);
    }

    public function test_failed_order_insert_rolls_back_stock_deduction(): void
    {
        $event = Event::factory()->create(['stock_type' => EventStockType::Event, 'stock' => 1]);
        $user = User::factory()->create();
        try {
            app(ReservationService::class)->reserve($event, $user, ['quantity' => 1, 'area_code' => str_repeat('x', 1000)]);
            self::fail('The oversized order field should fail in MySQL strict mode.');
        } catch (QueryException) {
            self::assertSame(1, $event->fresh()->stock);
            self::assertSame(0, Order::count());
        }
    }

    private function workers(\Closure $work): array
    {
        $directory = sys_get_temp_dir() . '/cms-concurrency-' . bin2hex(random_bytes(6));
        mkdir($directory);
        DB::disconnect();
        $pids = [];
        $start = microtime(true) + 0.2;
        for ($worker = 0; $worker < 2; $worker++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                throw new \RuntimeException('Unable to start concurrent worker.');
            }
            if ($pid === 0) {
                while (microtime(true) < $start) {
                    usleep(1000);
                }
                try {
                    file_put_contents($directory . '/' . $worker, $work());
                    exit(0);
                } catch (\Throwable $exception) {
                    file_put_contents($directory . '/' . $worker, $exception::class);
                    exit(1);
                }
            }
            $pids[] = $pid;
        }
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        $results = [];
        foreach ([0, 1] as $worker) {
            $results[] = file_get_contents($directory . '/' . $worker);
            unlink($directory . '/' . $worker);
        }
        rmdir($directory);

        return $results;
    }
}
