<?php namespace EloquentVersioned\Tests;

use Illuminate\Database\Eloquent\Model as Eloquent;

class VersionedTest extends FunctionalTestCase
{

    protected $modelPrefix = "\\EloquentVersioned\\Tests\\Models\\";

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
    }

    /**
     * Test that creating our versioned model works
     *
     * @param  array $data
     * @return bool
     * @dataProvider createDataProvider
     */
    public function testCreateModel($data)
    {
        $className = $this->modelPrefix.$data['name'];
        $object = $className::create($data);
        return true;
    }

    /**
     * Provides objects to use by tests
     *
     * @return array
     */
    public function createDataProvider()
    {
        return array(
            array(
                array('name' => 'Widget', 'gadget_id' => 1, 'doodad_id' => 1),
                array('name' => 'Gadget', 'widget_id' => 1, 'doodad_id' => 1),
                array('name' => 'Doodad', 'widget_id' => 1, 'gadget_id' => 1)
            )
        );
    }

}