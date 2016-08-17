<?php

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\Log;
use Models\Person;
use Models\Place;
use Tightenco\Quicksand\DeleteOldSoftDeletes;

class QuicksandDeleteTest extends PHPUnit_Framework_TestCase
{
    private $configMock;
    private $manager;

    public function setUp()
    {
        $this->configMock = Mockery::mock(Repository::class);
        $this->configMock->shouldIgnoreMissing();

        $this->configureDatabase();
        $this->migrate();
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
        $this->manager->schema()->create('people', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        $this->manager->schema()->create('places', function ($table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    public function act()
    {
        (new DeleteOldSoftDeletes($this->configMock))->handle();
    }

    public function test_it_deletes_old_records()
    {
        $person = new Person(['name' => 'Benson']);
        $person->deleted_at = Carbon::now()->subYear();
        $person->save();

        $this->configMock->shouldReceive('get')
            ->with('quicksand.models')
            ->andReturn(Person::class);
        $this->configMock->shouldReceive('get')
            ->with('quicksand.days')
            ->andReturn(1);

        $this->act();

        $lookup = Person::withTrashed()->find($person->id);

        $this->assertNull($lookup);
    }

    public function test_it_doesnt_delete_newer_records()
    {
        $person = new Person(['name' => 'Benson']);
        $person->deleted_at = Carbon::now();
        $person->save();

        $this->configMock->shouldReceive('get')
            ->with('quicksand.models')
            ->andReturn(Person::class);

        $this->configMock->shouldReceive('get')
            ->with('quicksand.days')
            ->andReturn(1);

        $this->act();

        $lookup = Person::withTrashed()->find($person->id);

        $this->assertNotNull($lookup);
    }

    public function test_it_throws_exception_if_soft_deletes_are_not_enabled_on_modeL()
    {
        $this->configMock->shouldReceive('get')
            ->with('quicksand.models')
            ->andReturn(Place::class);

        $this->configMock->shouldReceive('get')
            ->with('quicksand.days')
            ->andReturn(1);

        $this->expectException(Exception::class);

        $this->act();
    }
}
