<?php
use App\Services\ModelFactory;

/**
 * Class ModelFactoryTest.
 */
class ModelFactoryTest extends TestCase
{
    /**
     * @var ModelFactory
     */
    private $modelFactoryTest;

    public function setUp()
    {
        parent::setUp();

        $this->modelFactoryTest = $this->app->make('App\Services\ModelFactory');
    }

    /**
     * Verify that the factories produce the same structured objects (values will be different).
     */
    public function testMakeModel()
    {
        $normalFactory = factory(\App\Models\TestEntity::class)->make()->toArray();

        $serviceCreatedFactory = $this->modelFactoryTest->get(\App\Models\TestEntity::class)->toArray();

        $this->assertEquals(array_keys($normalFactory), array_keys($serviceCreatedFactory));
    }

    /**
     * Verify that the *named* factories produce the same structured objects (values will be different).
     */
    public function testMakeNamedModel()
    {
        $normalFactory = factory(App\Models\TestEntity::class, 'custom')->make()->toArray();

        $serviceCreatedFactory = $this->modelFactoryTest->get(\App\Models\TestEntity::class, 'custom')->toArray();

        $this->assertEquals(array_keys($normalFactory), array_keys($serviceCreatedFactory));

        $this->assertEquals($normalFactory['varchar'], $serviceCreatedFactory['varchar']);
    }

    /**
     * Test that valid json is returned.
     */
    public function testJsonModel()
    {
        $serviceJson = $this->modelFactoryTest->json(App\Models\TestEntity::class);

        $this->assertJson($serviceJson);

        $decoded = json_decode($serviceJson, true);

        $this->assertArrayHasKey('multiWordColumnTitle', $decoded); //assert that keys have been camel cased by transfomer
        $this->assertArrayHasKey('_self', $decoded);
    }

    /**
     * Test that call can restrict the columns returned for a single entity.
     */
    public function testPropertyLimitWhitelistEntity()
    {
        $retrieveOnly = ['varchar', 'hash'];

        $serviceModel = $this->modelFactoryTest->get(App\Models\TestEntity::class)
            ->showOnly($retrieveOnly)
            ->toArray();

        $this->assertEquals($retrieveOnly, array_keys($serviceModel));
    }

    /**
     * Test that call can restrict the columns returned for a group of entities.
     */
    public function testPropertyLimitWhitelistCollection()
    {
        $retrieveOnly = ['varchar', 'hash'];

        $serviceModel = $this->modelFactoryTest->get(App\Models\TestEntity::class)
            ->count(2)
            ->showOnly($retrieveOnly)
            ->toArray();

        $this->assertEquals($retrieveOnly, array_keys($serviceModel[0]));
    }

    /**
     * Test that call can make a normally hidden column visible.
     */
    public function testHiddenPropertyShowing()
    {
        $showProperty = 'hidden';

        $user = new \App\Models\TestEntity();
        $this->assertContains($showProperty, $user->getHidden());

        $serviceModel = $this->modelFactoryTest->get(App\Models\TestEntity::class)
            ->makeVisible([$showProperty])
            ->toArray();

        $this->assertArrayHasKey($showProperty, $serviceModel);
    }

    /**
     * Test that the $factory->make() method returns eloquent models.
     */
    public function testFactoryMakesEloquent()
    {
        $entity = $this->modelFactoryTest->make(App\Models\TestEntity::class);

        $this->assertInstanceOf(Illuminate\Database\Eloquent\Model::class, $entity);
    }

    public function testModelFactoryFullChain()
    {
        $fixture = [
            'varchar'              => 'fixed-varchar',
            'multiWordColumnTitle' => 'fixed-value',
            'hidden'               => false,
            '#appends'             => [
                'some' => 'value',
            ],
        ];

        $collection = [$fixture, $fixture];

        $serviceCreatedFactoryJson = $this->modelFactoryTest->get(\App\Models\TestEntity::class, 'custom')
            ->customize(['varchar' => $fixture['varchar'], 'multi_word_column_title' => $fixture['multiWordColumnTitle'], 'hidden' => false])
            ->makeVisible(['hidden'])
            ->showOnly(['varchar', 'multi_word_column_title', 'hidden'])
            ->append('#appends', $fixture['#appends'])
            ->count(2)
            ->setTransformer(App\Http\Transformers\EloquentModelTransformer::class)
            ->json();

        $this->assertJson($serviceCreatedFactoryJson);
        $compareArray = json_decode($serviceCreatedFactoryJson, true);
        foreach ($compareArray as &$value) {
            $this->assertArrayHasKey('_self', $value);
            unset($value['_self']);
        }

        $this->assertEquals($collection, $compareArray);
    }

    public function testModelFactoryInstanceArrayableAndJsonable()
    {
        $serviceCreatedFactoryInstance = $this->modelFactoryTest->get(\App\Models\TestEntity::class);

        $this->assertInstanceOf(Illuminate\Contracts\Support\Arrayable::class, $serviceCreatedFactoryInstance);
        $this->assertInstanceOf(Illuminate\Contracts\Support\Jsonable::class, $serviceCreatedFactoryInstance);
        $this->assertJson($serviceCreatedFactoryInstance->toJson());
    }
}
