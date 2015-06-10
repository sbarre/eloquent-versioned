<?php

namespace EloquentVersioned\Tests;

use Illuminate\Database\Eloquent\Model as Eloquent;

class NonIncrementingVersionedTest extends FunctionalTestCase
{
    protected $modelPrefix = "\\EloquentVersioned\\Tests\\Models\\";

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
        $this->resetEvents();
    }

    private function resetEvents()
    {
        // Define the models that have event listeners.
        $models = [$this->modelPrefix . 'Foo'];

        // Reset their event listeners.
        foreach ($models as $model) {
            // Flush any existing listeners.
            call_user_func(array($model, 'flushEventListeners'));

            // Reregister them.
            call_user_func(array($model, 'boot'));
        }
    }

    /**
     * We should be able to create a model
     *
     * @param  array $data
     *
     * @dataProvider createDataProvider
     */
    public function testCreate($data)
    {
        $className = $this->modelPrefix . $data['name'];
        $model = $className::create($data)->fresh();

        // model exists?
        $this->assertInstanceOf($this->modelPrefix . $data['name'], $model);
        $this->assertEquals($data['model_id'], $model->id);
        $this->assertEquals(1, $model->version);
        $this->assertEquals(1, $model->is_current_version);
    }

    /**
     * Using save() should create a new version
     *
     * @param  array $data
     *
     * @dataProvider createDataProvider
     */
    public function testSave($data)
    {
        $className = $this->modelPrefix . $data['name'];
        $model = $className::create($data)->fresh();

        $model->name = 'Updated ' . $data['name'];
        $model->save();

        // model was updated correctly?
        $this->assertEquals($data['model_id'], $model->getOriginal('model_id'));
        $this->assertEquals('Updated ' . $data['name'], $model->name);
        $this->assertEquals(2, $model->version);
        $this->assertEquals(1, $model->is_current_version);

        // old model exists?
        $oldModel = $className::onlyOldVersions()->where('id', $data['id'])->first();
        $this->assertInstanceOf($this->modelPrefix . $data['name'], $oldModel);
        $this->assertEquals(1, $oldModel->version);
        $this->assertEquals(0, $oldModel->is_current_version);

        // one record with scopes applied?
        $models = $className::all();
        $this->assertEquals(1, count($models));

        // two records without scopes applied?
        $models = $className::withOldVersions()->get();
        $this->assertEquals(2, count($models));
    }

    /**
     * Using saveMinor() should not create a new version
     *
     * @param  array $data
     *
     * @dataProvider createDataProvider
     */
    public function testMinorSave($data)
    {
        $className = $this->modelPrefix . $data['name'];
        $model = $className::create($data)->fresh();

        $model->name = 'Updated ' . $data['name'];
        $model->saveMinor();

        // model was updated correctly?
        $this->assertEquals($data['model_id'], $model->id);
        $this->assertEquals('Updated ' . $data['name'], $model->name);
        $this->assertEquals(1, $model->version);
        $this->assertEquals(1, $model->is_current_version);

        // still only one record?
        $models = $className::all();
        $this->assertEquals(1, count($models));
    }

    /**
     * Provides objects to use by tests
     *
     * @return array
     */
    public function createDataProvider()
    {
        return [
            [
                [
                    'name' => 'Foo',
                    'id' => 'b158f851-59aa-4d39-9f2b-969b5db8cfcd',
                    'model_id' => '92a831eb-554c-4a15-9690-31f16a58b761',
                ],
            ],
        ];
    }
}
