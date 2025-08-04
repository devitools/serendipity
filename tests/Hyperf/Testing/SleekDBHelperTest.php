<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing;

use Constructo\Core\Fake\Faker;
use Constructo\Support\Set;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Contract\Adapter\Deserializer;
use Serendipity\Domain\Contract\Adapter\Serializer;
use Serendipity\Hyperf\Testing\SleekDBHelper;
use Serendipity\Infrastructure\Adapter\DeserializerFactory;
use Serendipity\Infrastructure\Adapter\SerializerFactory;
use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use SleekDB\Store;

final class SleekDBHelperTest extends TestCase
{
    private Faker|MockObject $faker;

    private MockObject|SerializerFactory $serializerFactory;

    private DeserializerFactory|MockObject $deserializerFactory;

    private MockObject|SleekDBFactory $factory;

    private MockObject|Store $store;

    private MockObject|Serializer $serializer;

    private Deserializer|MockObject $deserializer;

    private SleekDBHelper $helper;

    private string $resource = 'resource';

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = $this->createMock(Faker::class);
        $this->serializerFactory = $this->createMock(SerializerFactory::class);
        $this->deserializerFactory = $this->createMock(DeserializerFactory::class);
        $this->factory = $this->createMock(SleekDBFactory::class);
        $this->store = $this->createMock(Store::class);
        $this->serializer = $this->createMock(Serializer::class);
        $this->deserializer = $this->createMock(Deserializer::class);

        $this->helper = new SleekDBHelper(
            $this->faker,
            $this->serializerFactory,
            $this->deserializerFactory,
            $this->factory
        );
    }

    public function testTruncateShouldDeleteAllDocumentsFromStore(): void
    {
        // Arrange
        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->store);

        $this->store->expects($this->once())
            ->method('deleteBy')
            ->with(
                [
                    '_id',
                    '>=',
                    0,
                ]
            );

        // Act
        $this->helper->truncate($this->resource);
    }

    public function testSeedShouldInsertFakeDataAndReturnSetUsingCorrectTransformation(): void
    {
        // Arrange
        $type = 'TestEntity';
        $override = ['name' => 'Test Override'];
        $fakerData = [
            'name' => 'Faker Generated',
            'age' => 25,
        ];
        $serializedData = [
            'name' => 'Serialized',
            'age' => 25,
        ];
        $deserializedData = [
            'name' => 'Deserialized',
            'age' => 25,
        ];
        $expectedResult = [
            'name' => 'Test Override',
            'age' => 25,
        ]; // Override + deserialized
        $insertResult = [
            '_id' => 123,
            'name' => 'Test Override',
            'age' => 25,
        ];

        $this->faker->expects($this->once())
            ->method('fake')
            ->with($type)
            ->willReturn(Set::createFrom($fakerData));

        $this->serializerFactory->expects($this->once())
            ->method('make')
            ->with($type)
            ->willReturn($this->serializer);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($fakerData)
            ->willReturn($serializedData);

        $this->deserializerFactory->expects($this->once())
            ->method('make')
            ->with($type)
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedData)
            ->willReturn($deserializedData);

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->store);

        $this->store->expects($this->once())
            ->method('insert')
            ->with($expectedResult)
            ->willReturn($insertResult);

        // Act
        $result = $this->helper->seed($type, $this->resource, $override);

        // Assert
        $this->assertEquals($insertResult, $result->toArray());
    }

    public function testSeedShouldRespeitarOverrideNosCamposFornecidos(): void
    {
        // Arrange
        $type = 'TestEntity';
        $override = ['name' => 'Nome Sobrescrito'];
        $fakerData = [
            'name' => 'Nome Original',
            'email' => 'email@teste.com',
            'age' => 30,
        ];
        $serializedData = [
            'name' => 'Nome Serializado',
            'email' => 'email@teste.com',
            'age' => 30,
        ];
        $deserializedData = [
            'name' => 'Nome Deserializado',
            'email' => 'email@teste.com',
            'age' => 30,
        ];
        $expectedResult = [
            'name' => 'Nome Sobrescrito',
            'email' => 'email@teste.com',
            'age' => 30,
        ];
        $insertResult = [
            '_id' => 456,
            'name' => 'Nome Sobrescrito',
            'email' => 'email@teste.com',
            'age' => 30,
        ];

        $this->faker->expects($this->once())
            ->method('fake')
            ->with($type)
            ->willReturn(Set::createFrom($fakerData));

        $this->serializerFactory->expects($this->once())
            ->method('make')
            ->with($type)
            ->willReturn($this->serializer);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($fakerData)
            ->willReturn($serializedData);

        $this->deserializerFactory->expects($this->once())
            ->method('make')
            ->with($type)
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedData)
            ->willReturn($deserializedData);

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->store);

        $this->store->expects($this->once())
            ->method('insert')
            ->with($expectedResult)
            ->willReturn($insertResult);

        // Act
        $result = $this->helper->seed($type, $this->resource, $override);

        // Assert
        $this->assertEquals($insertResult, $result->toArray());
    }

    public function testCountShouldReturnNumberOfDocuments(): void
    {
        // Arrange
        $filters = ['status' => 'active'];
        $documents = [
            [
                '_id' => 1,
                'name' => 'Document 1',
                'status' => 'active',
            ],
            [
                '_id' => 2,
                'name' => 'Document 2',
                'status' => 'active',
            ],
        ];

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->store);

        $this->store->expects($this->once())
            ->method('findBy')
            ->with($filters)
            ->willReturn($documents);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(2, $count);
    }

    public function testCountShouldHandleEmptyResults(): void
    {
        // Arrange
        $filters = ['status' => 'inactive'];
        $documents = [];

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->store);

        $this->store->expects($this->once())
            ->method('findBy')
            ->with($filters)
            ->willReturn($documents);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(0, $count);
    }

    public function testSeedShouldHandleMultipleFieldsCorrectly(): void
    {
        // Arrange
        $type = 'ComplexEntity';
        $fakerData = [
            'id' => 1,
            'name' => 'Original',
            'created_at' => '2023-01-01',
        ];
        $serializedData = [
            'id' => 1,
            'name' => 'Serialized',
            'created_at' => '2023-01-01',
        ];
        $deserializedData = [
            'id' => 1,
            'name' => 'Final',
            'created_at' => '2023-01-01',
            'is_active' => true,
        ];
        $insertResult = [
            '_id' => 789,
            'id' => 1,
            'name' => 'Final',
            'created_at' => '2023-01-01',
            'is_active' => true,
        ];

        $this->faker->expects($this->once())
            ->method('fake')
            ->with($type)
            ->willReturn(Set::createFrom($fakerData));

        $this->serializerFactory->expects($this->once())
            ->method('make')
            ->with($type)
            ->willReturn($this->serializer);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($fakerData)
            ->willReturn($serializedData);

        $this->deserializerFactory->expects($this->once())
            ->method('make')
            ->with($type)
            ->willReturn($this->deserializer);

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedData)
            ->willReturn($deserializedData);

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->store);

        $this->store->expects($this->once())
            ->method('insert')
            ->with($deserializedData)
            ->willReturn($insertResult);

        // Act
        $result = $this->helper->seed($type, $this->resource);

        // Assert
        $this->assertEquals($insertResult, $result->toArray());
    }
}
