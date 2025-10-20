<?php

/** @noinspection SqlResolve, SqlWithoutWhere */

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing;

use Constructo\Core\Fake\Faker;
use Constructo\Support\Set;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Contract\Adapter\Deserializer;
use Serendipity\Domain\Contract\Adapter\Serializer;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionFactory;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Hyperf\Testing\PostgresHelper;
use Serendipity\Infrastructure\Database\Relational\Connection;
use Serendipity\Infrastructure\Repository\Adapter\RelationalDeserializerFactory;
use Serendipity\Infrastructure\Repository\Adapter\RelationalSerializerFactory;

final class PostgresHelperTest extends TestCase
{
    use MakeExtension;

    private Faker $faker;

    private RelationalSerializerFactory $serializerFactory;

    private RelationalDeserializerFactory $deserializerFactory;

    private Connection $connection;

    private Serializer $serializer;

    private Deserializer $deserializer;

    private PostgresHelper $helper;

    private string $resource = 'resource';

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = $this->createMock(Faker::class);
        $this->serializerFactory = $this->createMock(RelationalSerializerFactory::class);
        $this->deserializerFactory = $this->createMock(RelationalDeserializerFactory::class);
        $hyperfDatabaseFactory = $this->createMock(HyperfConnectionFactory::class);
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = $this->createMock(Serializer::class);
        $this->deserializer = $this->createMock(Deserializer::class);

        $hyperfDatabaseFactory->expects($this->once())
            ->method('make')
            ->with('postgres')
            ->willReturn($this->connection);

        $this->helper = new PostgresHelper(
            $this->faker,
            $this->serializerFactory,
            $this->deserializerFactory,
            $hyperfDatabaseFactory
        );
    }

    public function testTruncateShouldDeleteAllRecordsFromTable(): void
    {
        // Arrange
        $expectedQuery = 'delete from resource where true';

        $this->connection->expects($this->once())
            ->method('execute')
            ->with($expectedQuery);

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

        $expectedQuery = 'insert into "resource" ("name","age") values (?,?)';
        $expectedBindings = [
            'Test Override',
            25,
        ];

        $this->connection->expects($this->once())
            ->method('execute')
            ->with($expectedQuery, $expectedBindings);

        // Act
        $result = $this->helper->seed($type, $this->resource, $override);

        // Assert
        $this->assertEquals($expectedResult, $result->toArray());
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

        $expectedQuery = 'insert into "resource" ("name","email","age") values (?,?,?)';
        $expectedBindings = [
            'Nome Sobrescrito',
            'email@teste.com',
            30,
        ];

        $this->connection->expects($this->once())
            ->method('execute')
            ->with($expectedQuery, $expectedBindings);

        // Act
        $result = $this->helper->seed($type, $this->resource, $override);

        // Assert
        $this->assertEquals($expectedResult, $result->toArray());
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

        $expectedQuery = 'insert into "resource" ("id","name","created_at","is_active") values (?,?,?,?)';
        $expectedBindings = [
            1,
            'Final',
            '2023-01-01',
            1,
        ];

        $this->connection->expects($this->once())
            ->method('execute')
            ->with($expectedQuery, $expectedBindings);

        // Act
        $result = $this->helper->seed($type, $this->resource);

        // Assert
        $this->assertEquals($deserializedData, $result->toArray());
    }

    public function testCountShouldReturnNumberOfRecordsWithSimpleFilters(): void
    {
        // Arrange
        $filters = ['status' => 'active'];
        $expectedQuery = 'select count(*) as count from "resource" where "status" = ?';
        $expectedBindings = ['active'];
        $queryResult = [['count' => '2']];

        $this->connection->expects($this->once())
            ->method('query')
            ->with($expectedQuery, $expectedBindings)
            ->willReturn($queryResult);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(2, $count);
    }

    public function testCountShouldHandleNullValuesInFilters(): void
    {
        // Arrange
        $filters = [
            'status' => null,
            'type' => 'user',
        ];
        $expectedQuery = 'select count(*) as count from "resource" where "status" is null and "type" = ?';
        $expectedBindings = ['user'];
        $queryResult = [['count' => '3']];

        $this->connection->expects($this->once())
            ->method('query')
            ->with($expectedQuery, $expectedBindings)
            ->willReturn($queryResult);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(3, $count);
    }

    public function testCountShouldReturnZeroWhenResultIsEmpty(): void
    {
        // Arrange
        $filters = ['status' => 'inactive'];
        $expectedQuery = 'select count(*) as count from "resource" where "status" = ?';
        $expectedBindings = ['inactive'];
        $queryResult = [];

        $this->connection->expects($this->once())
            ->method('query')
            ->with($expectedQuery, $expectedBindings)
            ->willReturn($queryResult);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(0, $count);
    }

    public function testCountShouldReturnZeroWhenCountIsNotPresent(): void
    {
        // Arrange
        $filters = ['status' => false];
        $expectedQuery = 'select count(*) as count from "resource" where "status" = ?';
        $expectedBindings = [false];
        $queryResult = [[]];  // Resultado vazio sem a chave count

        $this->connection->expects($this->once())
            ->method('query')
            ->with($expectedQuery, $expectedBindings)
            ->willReturn($queryResult);

        // Act
        $count = $this->helper->count($this->resource, $filters);

        // Assert
        $this->assertEquals(0, $count);
    }

    public function testShouldWorksFine(): void
    {
        $helper = $this->make(PostgresHelper::class);

        $game = $helper->seed(Game::class, 'games');

        $count = $helper->count('games', ['id' => $game->at('id')]);
        $this->assertEquals(1, $count);
        $helper->truncate('games');

        $helper->seed(Game::class, 'games', ['is_active' => false]);

        $count = $helper->count('games', ['is_active' => false]);
        $this->assertEquals(1, $count);
    }
}
