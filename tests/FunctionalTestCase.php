<?php namespace EloquentVersioned\Tests;

use Illuminate\Database\Capsule\Manager as DBM;

class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->configureDatabase();
        $this->migrateTables();
    }

    protected function configureDatabase()
    {
        $db = new DBM;
        $db->addConnection(array(
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ));
        $db->bootEloquent();
        $db->setAsGlobal();
    }

    protected function migrateTables()
    {
        DBM::schema()->create('widgets', function ($table) {
            $table->increments('id');
            $table->integer('model_id')->unsigned()->default(1);
            $table->integer('version')->unsigned()->default(1);
            $table->integer('is_current_version')->unsigned()->default(1);
            $table->integer('gadget_id')->unsigned()->default(0);
            $table->integer('doodad_id')->unsigned()->default(0);
            $table->string('name');
            $table->timestamps();
        });

        DBM::schema()->create('gadgets', function ($table) {
            $table->increments('id');
            $table->integer('model_id')->unsigned()->default(1);
            $table->integer('version')->unsigned()->default(1);
            $table->integer('is_current_version')->unsigned()->default(1);
            $table->integer('widget_id')->unsigned()->default(0);
            $table->integer('doodad_id')->unsigned()->default(0);
            $table->string('name');
            $table->timestamps();
        });

        DBM::schema()->create('doodads', function ($table) {
            $table->increments('id');
            $table->integer('model_id')->unsigned()->default(1);
            $table->integer('version')->unsigned()->default(1);
            $table->integer('is_current_version')->unsigned()->default(1);
            $table->integer('gadget_id')->unsigned()->default(0);
            $table->integer('widget_id')->unsigned()->default(0);
            $table->string('name');
            $table->timestamps();
        });
    }
}
