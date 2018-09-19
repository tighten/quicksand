<?php

use Illuminate\Support\Facades\Log;
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
    ];

    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
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
