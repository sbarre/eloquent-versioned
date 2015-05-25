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
	    $model = $className::create($data)->fresh();

	    $this->assertInstanceOf('Illuminate\\Database\\Eloquent\\Model', $model);
	    $this->assertEquals(1, $model->id);
	    $this->assertEquals(1, $model->version);
	    $this->assertEquals(1, $model->is_current_version);
    }

	/**
	 * @depends testCreateModel
	 * @dataProvider createDataProvider
	 */
	public function testVersioningModel($data)
	{
		$className = $this->modelPrefix.$data['name'];
		$model = $className::create($data)->fresh();

		$model->name = 'Updated Widget';
		$model->save();

		$this->assertEquals(1, $model->id);
		$this->assertEquals(2, $model->version);
		$this->assertEquals(1, $model->is_current_version);

	}

    /**
     * Provides objects to use by tests
     *
     * @return array
     */
    public function createDataProvider()
    {
        return array(
	        array(array('name' => 'Widget', 'gadget_id' => 1, 'doodad_id' => 1)),
	        array(array('name' => 'Gadget', 'widget_id' => 1, 'doodad_id' => 1)),
	        array(array('name' => 'Doodad', 'widget_id' => 1, 'gadget_id' => 1))
        );
    }

}