<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Facade;
use Models\Person;
use Models\Place;
use Models\Thing;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tightenco\Quicksand\DeleteOldSoftDeletes;

class QuicksandDeleteTest extends TestCase
{
    private $configMock;
    private $manager;
    private $oneYearAgo;
    private $now;

    public function setUp()
    {
        $this->configMock = Mockery::mock(Repository::class)->makePartial();
        $this->oneYearAgo = (new DateTime)->sub(new DateInterval('P1Y'))->format('Y-m-d H:i:s');
        $this->now = (new DateTime)->format('Y-m-d H:i:s');

        $this->configureDatabase();
        $this->migrate();
        $this->configureApp();
    }

    private function configureDatabase()
    {
        $this->manager = new Manager;
        $this->manager->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->manager->setAsGlobal();
        $this->manager->bootEloquent();
    }

    private function migrate()
    {
        $this->manager->schema()->create('people', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->manager->schema()->create('places', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->manager->schema()->create('things', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function configureApp()
    {
        $app = new Container;
        $app->singleton('app', Container::class);
        Facade::setFacadeApplication($app);
        $app->instance(LoggerInterface::class, Mockery::spy(Logger::class));
    }

    private function mockConfiguration($configuration = null)
    {
        $this->configMock->allows()
            ->get('quicksand.days')
            ->andReturn($configuration['days'] ?? 1);

        $this->configMock->allows()
            ->get('quicksand.log', false)
            ->andReturn($configuration['log'] ?? true);

        $this->configMock->allows()
            ->get('quicksand.custom_log_file', false)
            ->andReturn($configuration['custom_log_file'] ?? false);

        $this->configMock->allows()
            ->get('quicksand.models')
            ->andReturn($configuration['models'] ?? [Person::class]);
    }

    private function createOldDeletedPerson()
    {
        return $this->createPerson(true);
    }

    private function createNewlyDeletedPerson()
    {
        return $this->createPerson(false);
    }

    private function createPerson($old = true)
    {
        $person = new Person(['name' => 'Jose']);
        $person->deleted_at = $old ? $this->oneYearAgo : $this->now;
        $person->save();
        return $person;
    }

    private function createOldDeletedThing()
    {
        $thing = new Thing(['name' => 'Coffee']);
        $thing->deleted_at = $this->oneYearAgo;
        $thing->save();
        return $thing;
    }

    private function act()
    {
        (new DeleteOldSoftDeletes($this->configMock))->handle();
    }

    /** @test */
    public function it_deletes_old_records()
    {
        $this->mockConfiguration();

        $person = $this->createOldDeletedPerson();

        $this->assertNotNull(Person::withTrashed()->find($person->id));

        $this->act();

        $this->assertNull(Person::withTrashed()->find($person->id));
    }

    /** @test */
    public function it_does_not_delete_newer_records()
    {
        $person = $this->createNewlyDeletedPerson();

        $this->act();

        $this->assertNotNull(Person::withTrashed()->find($person->id));
    }

    /** @test */
    public function it_throws_exception_if_soft_deletes_are_not_enabled_on_model()
    {
        $this->mockConfiguration(['models' => [Place::class]]);

        $this->expectException(Exception::class);

        $this->act();
    }

    /** @test */
    public function it_writes_to_logs_if_entries_are_deleted()
    {
        $this->mockConfiguration();
        $spy = Mockery::spy(Logger::class);
        $expectedLogOutput = sprintf(
            'Tightenco\Quicksand\DeleteOldSoftDeletes force deleted these number of rows: %s',
            print_r(['Models\Person' => 1], true)
        );

        $this->createOldDeletedPerson();

        $this->act();

        $spy->allows()->info($expectedLogOutput);
    }

    /** @test */
    public function it_will_delete_rows_from_multiple_tables_if_config_is_set_for_it()
    {
        $this->mockConfiguration(['models' => [Person::class, Thing::class]]);

        $person = $this->createOldDeletedPerson();
        $this->assertNotNull(Person::withTrashed()->find($person->id));

        $thing = $this->createOldDeletedThing();
        $this->assertNotNull(Thing::withTrashed()->find($thing->id));

        $this->act();

        $this->assertNull(Person::withTrashed()->find($person->id));
        $this->assertNull(Thing::withTrashed()->find($thing->id));
    }

    /** @test */
    public function it_does_not_delete_anything_if_days_before_deletion_is_not_set()
    {
        $this->mockConfiguration(['days' => '']);

        $person = $this->createOldDeletedPerson();
        $this->assertNotNull(Person::withTrashed()->find($person->id));

        $this->act();

        $this->assertNotNull(Person::withTrashed()->find($person->id));
    }

    /** @test */
    public function it_does_not_write_to_logs_if_there_are_no_deletable_records()
    {
        $this->mockConfiguration();

        $mock = Mockery::mock(Logger::class);
        $mock->shouldNotReceive('info');

        $this->createNewlyDeletedPerson();

        $this->act();
    }

    /** @test */
    public function it_sends_logs_to_a_custom_log_file()
    {
        $this->mockConfiguration(['custom_log_file' => __DIR__.'/storage/logs/custom.log']);
        $spy = Mockery::spy(Logger::class);
        $expectedLogOutput = sprintf(
            'Tightenco\Quicksand\DeleteOldSoftDeletes force deleted these number of rows: %s',
            print_r(['Models\Person' => 1], true)
        );

        $this->createOldDeletedPerson();

        $this->act();

        $spy->allows()->info($expectedLogOutput);
    }

    public function tearDown()
    {
        $this->addToAssertionCount(
            Mockery::getContainer()->mockery_getExpectationCount()
        );
    }
}
