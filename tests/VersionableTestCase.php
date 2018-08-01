<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;

abstract class VersionableTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->configureDatabase();
        $this->migrateUsersTable();
    }

    protected function configureDatabase()
    {
        $db = new DB;
        $db->addConnection(array(
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));
        $db->setEventDispatcher(new Dispatcher(new Container));
        $db->bootEloquent();
        $db->setAsGlobal();
    }

    protected function createVersionTable($table)
    {
    	$table->increments('version_id');
    	$table->integer('versionable_id');
    	$table->text('versionable_type');
    	$table->integer('user_id')->nullable();
    	$table->binary('model_data');
    	$table->string('reason', 100)->nullable();
    	$table->timestamps();
    }

    public function migrateUsersTable()
    {
        DB::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->datetime('last_login')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $that = $this ;

        DB::schema()->create('versions', function ($table) use ($that) {
            $that->createVersionTable($table);
        });

        DB::schema()->create(DynamicVersionModel::TABLENAME, function ($table) use ($that) {
       	    $that->createVersionTable($table);
       	});

        DB::schema()->create( ModelWithDynamicVersion::TABLENAME, function ($table) {
            $table->increments('id');
            $table->text('name');
     	    $table->timestamps();
        });

    }
}