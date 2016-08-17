<?php

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager;
use Models\Person;
use Tightenco\Quicksand\DeleteOldSoftDeletes;

class QuicksandDeleteTest extends PHPUnit_Framework_TestCase
{
    private $configSpy;
    private $manager;

    public function setUp()
    {
        $this->configSpy = Mockery::spy(Repository::class);

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
    }

    public function act()
    {
        (new DeleteOldSoftDeletes($this->configSpy))->handle();
    }

    public function test_it_deletes_old_records()
    {
        $person = new Person(['name' => 'Benson']);
        $person->deleted_at = Carbon::now()->subYear();
        $person->save();

        $this->configSpy->shouldReceive('get')
            ->with('quicksand.models')
            ->andReturn(Person::class);
        $this->configSpy->shouldReceive('get')
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

        $this->configSpy->shouldReceive('get')
            ->with('quicksand.models')
            ->andReturn(Person::class);

        $this->configSpy->shouldReceive('get')
            ->with('quicksand.days')
            ->andReturn(1);

        $this->act();

        $lookup = Person::withTrashed()->find($person->id);

        $this->assertNotNull($lookup);
    }
}
