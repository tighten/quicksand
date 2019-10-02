<?php

use Illuminate\Support\Facades\Log;
use Models\GlobalScopedThing;
use Models\Person;
use Models\Place;
use Models\Thing;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Orchestra\Testbench\TestCase;
use Tightenco\Quicksand\DeleteOldSoftDeletes;

class QuicksandDeleteTest extends TestCase
{
    public $defaultQuicksandConfig = [
        'days' => 30,
        'log' => false,
        'models' => [],
        'pivot_tables' => [],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->withFactories(__DIR__ . '/database/factories');
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('quicksand', $this->defaultQuicksandConfig);
    }

    /** @test */
    public function it_deletes_old_records()
    {
        factory(Person::class, 15)->state('deleted_old')->create();

        $this->setQuicksandConfig([
            'models' => [
                Person::class,
            ],
        ]);

        $this->deleteOldSoftDeletes();

        $this->assertEquals(0, Person::withTrashed()->count());
    }

    /** @test */
    public function it_deletes_old_records_for_pivot_tables()
    {
        $person = factory(Person::class)->create();
        $thing = factory(Thing::class)->create();

        $person->things()->attach($thing);

        DB::table('person_thing')
            ->where('person_id', $person->id)
            ->where('thing_id', $thing->id)
            ->update(['deleted_at' => now()->subDays(50)]);

        $this->setQuicksandConfig([
            'pivot_tables' => [
                'person_thing'
            ],
        ]);

        $this->assertEquals(1, DB::table('person_thing')->where('person_id', $person->id)->where('thing_id', $thing->id)->count());

        $this->deleteOldSoftDeletes();

        $this->assertEquals(0, DB::table('person_thing')->where('person_id', $person->id)->where('thing_id', $thing->id)->count());
    }

    /** @test */
    public function it_does_not_delete_newer_records()
    {
        factory(Person::class, 15)->state('deleted_recent')->create();

        $this->setQuicksandConfig([
            'models' => [
                Person::class,
            ],
        ]);

        $this->deleteOldSoftDeletes();

        $this->assertEquals(15, Person::withTrashed()->count());
    }

    /** @test */
    public function it_deletes_not_delete_newer_records_for_pivot_tables()
    {
        $person = factory(Person::class)->create();
        $thing = factory(Thing::class)->create();

        $person->things()->attach($thing);

        DB::table('person_thing')
            ->where('person_id', $person->id)
            ->where('thing_id', $thing->id)
            ->update(['deleted_at' => now()]);

        $this->setQuicksandConfig([
            'pivot_tables' => [
                'person_thing'
            ],
        ]);

        $this->assertEquals(1, DB::table('person_thing')->where('person_id', $person->id)->where('thing_id', $thing->id)->count());

        $this->deleteOldSoftDeletes();

        $this->assertEquals(1, DB::table('person_thing')->where('person_id', $person->id)->where('thing_id', $thing->id)->count());
    }

    /** @test */
    public function it_can_combine_pivot_tables_with_models()
    {
        factory(Person::class, 15)->state('deleted_recent')->create();
        factory(Thing::class, 2)->state('deleted_old')->create();

        $person = factory(Person::class)->create();
        $thing = factory(Thing::class)->create();

        $person->things()->attach($thing);

        DB::table('person_thing')
            ->where('person_id', $person->id)
            ->where('thing_id', $thing->id)
            ->update(['deleted_at' => now()->subdays(50)]);

        $this->setQuicksandConfig([
            'models' => [
                Person::class,
                Thing::class,
            ],
            'pivot_tables' => [
                'person_thing'
            ],
        ]);

        $this->assertEquals(1, DB::table('person_thing')->where('person_id', $person->id)->where('thing_id', $thing->id)->count());

        $this->assertEquals(16, Person::withTrashed()->count());
        $this->assertEquals(3, Thing::withTrashed()->count());

        $this->deleteOldSoftDeletes();

        $this->assertEquals(0, DB::table('person_thing')->where('person_id', $person->id)->where('thing_id', $thing->id)->count());

        $this->assertEquals(16, Person::withTrashed()->count());
        $this->assertEquals(1, Thing::withTrashed()->count());
    }

     /** @test */
    public function it_deletes_records_with_a_global_scope()
    {
        factory(GlobalScopedThing::class, 15)->states('global_scope_condition_met', 'deleted_old')->create();
        factory(GlobalScopedThing::class, 15)->state('deleted_old')->create();

        $this->setQuicksandConfig([
            'models' => [
                GlobalScopedThing::class,
            ],
        ]);

        $this->deleteOldSoftDeletes();

        $this->assertEquals(0, GlobalScopedThing::withoutGlobalScopes()->withTrashed()->count());
    }

    /** @test */
    public function it_throws_exception_if_soft_deletes_are_not_enabled_on_model()
    {
        $this->setQuicksandConfig([
            'models' => [
                Place::class,
            ],
        ]);

        try {
            $this->deleteOldSoftDeletes();
        } catch (Exception $e) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('It should throw an exception if soft deletes are not enabled');
    }

    /** @test */
    public function it_will_delete_rows_from_multiple_tables_if_config_is_set_for_it()
    {
        $this->setQuicksandConfig([
            'models' => [
                Person::class,
                Thing::class,
            ],
        ]);

        factory(Person::class, 15)->state('deleted_old')->create();
        factory(Thing::class, 15)->state('deleted_old')->create();

        $this->deleteOldSoftDeletes();

        $this->assertEquals(0, Person::withTrashed()->count());
        $this->assertEquals(0, Thing::withTrashed()->count());
    }

    /** @test */
    public function it_does_not_delete_anything_if_days_before_deletion_is_not_set()
    {
        $this->setQuicksandConfig([
            'days' => '',
            'models' => [
                Person::class,
            ],
        ]);

        factory(Person::class, 15)->state('deleted_old')->create();

        $this->deleteOldSoftDeletes();

        $this->assertEquals(15, Person::withTrashed()->count());
    }

    /** @test */
    public function it_writes_to_logs_if_entries_are_deleted()
    {
        $this->mockLogger();
        $this->setQuicksandConfig([
            'log' => true,
            'models' => [
                Person::class,
            ],
        ]);

        factory(Person::class)->state('deleted_old')->create();

        $this->deleteOldSoftDeletes();

        $expectedLogOutput = sprintf(
            'Tightenco\Quicksand\DeleteOldSoftDeletes force deleted these number of rows: %s',
            print_r(['Models\Person' => 1], true)
        );

        $this->assertSame($expectedLogOutput, $this->getLastLogMessage('quicksand'));
    }

    /** @test */
    public function it_does_not_write_to_logs_if_log_is_set_to_false()
    {
        $this->mockLogger();
        $this->setQuicksandConfig([
            'log' => false,
            'models' => [
                Person::class,
            ],
        ]);

        factory(Person::class)->state('deleted_old')->create();

        $this->deleteOldSoftDeletes();

        $this->assertEquals(0, Person::withTrashed()->count());
        $this->assertNull($this->getLastLogMessage('quicksand'));
    }

    /** @test */
    public function it_does_not_write_to_logs_if_there_are_no_deletable_records()
    {
        $this->mockLogger();
        $this->setQuicksandConfig([
            'log' => true,
            'models' => [
                Person::class,
            ],
        ]);

        factory(Person::class)->state('deleted_recent')->create();

        $this->deleteOldSoftDeletes();

        $this->assertNull($this->getLastLogMessage('quicksand'));
    }

    private function deleteOldSoftDeletes()
    {
        (new DeleteOldSoftDeletes($this->app['config']))->handle();
    }

    private function getLastLogMessage($channel)
    {
        return Log::channel($channel)
                ->getLogger()
                ->getHandlers()[0]
                ->getRecords()[0]['message'] ?? null;
    }

    private function mockLogger()
    {
        $this->app->config->set('logging.channels', [
            'quicksand' => [
                'driver' => 'custom',
                'via' => function () {
                    $monolog = new Logger('test');
                    $monolog->pushHandler(new TestHandler());
                    return $monolog;
                },
            ]
        ]);
    }

    private function setQuicksandConfig($overrides = [])
    {
        $this->app['config']->set('quicksand', array_merge($this->defaultQuicksandConfig, $overrides));
    }
}
