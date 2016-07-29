<?php

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

// @todo move this somewhere
class Person extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name'];
}

class QuicksandDeleteTest extends FunctionalTestCase
{
    function setUp()
    {
        parent::setUp();
        $this->migrate();
    }

    function migrate()
    {
        $this->migratePeopleTable();
    }

    function migratePeopleTable()
    {
        DB::schema()->create('people', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    function act()
    {
        // @todo: Mock Illuminate\Config\Repository and inject:
        // $config = Mock object
        (new Tightenco\Quicksand\DeleteOldSoftDeletes($config))->handle();
    }

    /** @test */
    function it_deletes_old_records()
    {
        $person = new Person(['name' => 'Benson']);
        $person->deleted_at = Carbon::now()->subYear(); 
        $person->save();

        $this->act();

        $lookup = Person::withTrashed()->find($person->id);

        $this->assertNull($lookup);
    }

    /** @test */
    function it_doesnt_delete_newer_records()
    {
        $person = new Person(['name' => 'Benson']);
        $person->deleted_at = Carbon::now();
        $person->save();

        $this->act();

        $lookup = Person::withTrashed()->find($person->id);

        $this->assertNotNull($lookup);
    }
}
