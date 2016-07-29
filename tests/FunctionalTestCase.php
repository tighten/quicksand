<?php

use Illuminate\Database\Capsule\Manager as DB;

abstract class FunctionalTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->configureDatabase();
    }

    protected function configureDatabase()
    {
        $db = new DB;
        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();
    }
}
