<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing;

use Constructo\Core\Fake\Faker;
use Constructo\Support\Set;
use Hyperf\Contract\Arrayable;
use MongoDB\Collection;
use MongoDB\InsertOneResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Contract\Adapter\Deserializer;
use Serendipity\Domain\Contract\Adapter\Serializer;
use Serendipity\Hyperf\Testing\MongoHelper;
use Serendipity\Infrastructure\Database\Document\MongoFactory;
use Serendipity\Infrastructure\Repository\Adapter\MongoDeserializerFactory;
use Serendipity\Infrastructure\Repository\Adapter\MongoSerializerFactory;
use stdClass;

final class MongoHelperTest extends TestCase
{
    private Faker|MockObject $faker;

    private MockObject|MongoSerializerFactory $serializerFactory;

    private MockObject|MongoDeserializerFactory $deserializerFactory;

    private MockObject|MongoFactory $factory;

    private Collection|MockObject $collection;

    private MockObject|Serializer $serializer;

    private Deserializer|MockObject $deserializer;

    private MongoHelper $helper;

    private string $resource = 'resource';

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = $this->createMock(Faker::class);
        $this->serializerFactory = $this->createMock(MongoSerializerFactory::class);
        $this->deserializerFactory = $this->createMock(MongoDeserializerFactory::class);
        $this->factory = $this->createMock(MongoFactory::class);
        $this->collection = $this->createMock(Collection::class);
        $this->serializer = $this->createMock(Serializer::class);
        $this->deserializer = $this->createMock(Deserializer::class);

        $this->helper = new MongoHelper(
            $this->faker,
            $this->serializerFactory,
            $this->deserializerFactory,
            $this->factory
        );
    }

    public function testTruncateShouldDeleteAllDocumentsFromCollection(): void
    {
        // Arrange
        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->collection);

        $this->collection->expects($this->once())
            ->method('deleteMany')
            ->with([]);

        // Act
        $this->helper->truncate($this->resource);
    }

    public function testSeedShouldInsertFakeDataAndReturnSetUsingCorrectTransformation(): void
    {
        // Arrange
        $type = stdClass::class;
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
        $objectId = $this->createMock(InsertOneResult::class);

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
            ->willReturn($this->collection);

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($expectedResult)
            ->willReturn($objectId);

        // Act
        $result = $this->helper->seed($type, $this->resource, $override);

        // Assert
        $this->assertEquals(
            array_merge($expectedResult, ['_id' => $objectId]),
            $result->toArray()
        );
    }

    public function testSeedShouldRespeitarOverrideNosCamposFornecidos(): void
    {
        // Arrange
        $type = stdClass::class;
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
        $objectId = $this->createMock(InsertOneResult::class);

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
            ->willReturn($this->collection);

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($expectedResult)
            ->willReturn($objectId);

        // Act
        $result = $this->helper->seed($type, $this->resource, $override);

        // Assert
        $this->assertEquals(
            array_merge($expectedResult, ['_id' => $objectId]),
            $result->toArray()
        );
    }

    public function testCountShouldReturnNumberOfDocuments(): void
    {
        // Arrange
        $filters = ['status' => 'active'];
        $resultCursor = $this->createMock(Arrayable::class);
        $documents = [
            [
                '_id' => '1',
                'name' => 'Document 1',
            ],
            [
                '_id' => '2',
                'name' => 'Document 2',
            ],
        ];

        $resultCursor->expects($this->once())
            ->method('toArray')
            ->willReturn($documents);

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->collection);

        $this->collection->expects($this->once())
            ->method('find')
            ->with($filters)
            ->willReturn($resultCursor);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(2, $count);
    }

    public function testCountShouldHandleEmptyResults(): void
    {
        // Arrange
        $filters = ['status' => 'inactive'];
        $resultCursor = $this->createMock(Arrayable::class);
        $documents = [];

        $resultCursor->expects($this->once())
            ->method('toArray')
            ->willReturn($documents);

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->resource)
            ->willReturn($this->collection);

        $this->collection->expects($this->once())
            ->method('find')
            ->with($filters)
            ->willReturn($resultCursor);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(0, $count);
    }

    public function testSeedShouldHandleMultipleFieldsCorrectly(): void
    {
        // Arrange
        $type = stdClass::class;
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
        $objectId = $this->createMock(InsertOneResult::class);

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
            ->willReturn($this->collection);

        $this->collection->expects($this->once())
            ->method('insertOne')
            ->with($deserializedData)
            ->willReturn($objectId);

        // Act
        $result = $this->helper->seed($type, $this->resource);

        // Assert
        $this->assertEquals(
            array_merge($deserializedData, ['_id' => $objectId]),
            $result->toArray()
        );
    }
}
