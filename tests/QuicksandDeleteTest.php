<?php

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as DB;
use Models\Person;
use Tightenco\Quicksand\DeleteOldSoftDeletes;

{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public function setUp()
    {
        parent::setUp();
        $this->migrate();
    }

    public function migrate()
    {
        $this->migratePeopleTable();
    }

    public function migratePeopleTable()
    {
        DB::schema()->create('people', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function act()
    {
        (new DeleteOldSoftDeletes(new DB, $configSpy))->handle();
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
