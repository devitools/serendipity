<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Hyperf\Command\GenerateRules;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateRulesTest extends TestCase
{
    private function createMockSchema(array $rules): object
    {
        return new class($rules) {
            public function __construct(private array $rules) {}
            public function rules(): array { return $this->rules; }
        };
    }

    private function createMockReflectorFactory(array $rules = []): object
    {
        $schema = $this->createMockSchema($rules);
        return new class($schema) {
            public function __construct(private object $schema) {}
            public function make(): object {
                return new class($this->schema) {
                    public function __construct(private object $schema) {}
                    public function reflect(string $class): object {
                        return $this->schema;
                    }
                };
            }
        };
    }

    public function testConfigure(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new GenerateRules($container);
        $this->assertSame('dev:rules {entity}', $command->getName());
        $this->assertSame('Export the rules to validate an entity', $command->getDescription());
    }

    public function testHandleNoEntityProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $container = $this->createMock(ContainerInterface::class);
        $command = new GenerateRules($container);
        $input = new ArrayInput([]);

        $command->setInput($input);
        $command->handle();
    }

    public function testHandleEntityDoesNotExist(): void
    {
        // Arrange
        $container = $this->createMock(ContainerInterface::class);
        $command = new GenerateRules($container);

        $messages = [];
        $output = $this->createMock(SymfonyStyle::class);
        $output->method('title')
            ->willReturnSelf();
        $output->method('writeln')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('error')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('newLine')
            ->willReturnSelf();
        $command->setOutput($output);

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getArgument')
            ->willReturn('NonExistentClass');
        $command->setInput($input);

        // Act
        $command->handle();

        // Assert
        $this->assertStringContainsString(
            'It was not possible to generate rules for the entity',
            implode('|', $messages),
        );
    }

    public function testHandleGenerateRulesForValidEntity(): void
    {
        // Arrange
        $reflectorFactory = $this->createMockReflectorFactory(['name' => 'required|string', 'level' => 'required|integer']);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('Constructo\Factory\ReflectorFactory')
            ->willReturn($reflectorFactory);

        $command = new GenerateRules($container);

        $messages = [];
        $output = $this->createMock(SymfonyStyle::class);
        $output->method('title')
            ->willReturnSelf();
        $output->method('writeln')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('info')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('newLine')
            ->willReturnSelf();
        $command->setOutput($output);

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getArgument')
            ->willReturn(GameCommand::class);
        $command->setInput($input);

        // Act
        $command->handle();

        // Assert
        $this->assertStringContainsString('Rules generated successfully', implode('|', $messages));
    }

    public function testHandleGenerateRulesFromFile(): void
    {
        // Arrange
        $reflectorFactory = $this->createMockReflectorFactory(['name' => 'required|string']);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('Constructo\Factory\ReflectorFactory')
            ->willReturn($reflectorFactory);

        $command = new GenerateRules($container);

        $messages = [];
        $output = $this->createMock(SymfonyStyle::class);
        $output->method('title')
            ->willReturnSelf();
        $output->method('writeln')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('info')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('newLine')
            ->willReturnSelf();
        $command->setOutput($output);

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getArgument')
            ->willReturn('src/Example/Game/Domain/Entity/Command/GameCommand.php');
        $command->setInput($input);

        // Act
        $command->handle();

        // Assert
        $this->assertStringContainsString('Rules generated successfully', implode('|', $messages));
    }

    public function testCantHandleNotMappedFile(): void
    {
        // Arrange
        $container = $this->createMock(ContainerInterface::class);
        $command = new GenerateRules($container);

        $messages = [];
        $output = $this->createMock(SymfonyStyle::class);
        $output->method('title')
            ->willReturnSelf();
        $output->method('writeln')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('error')
            ->willReturnCallback(function (mixed $string) use (&$messages) {
                $messages[] = $string;
            });
        $output->method('newLine')
            ->willReturnSelf();
        $command->setOutput($output);

        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())
            ->method('getArgument')
            ->willReturn('tests/Testing/Stub/Variety.php');
        $command->setInput($input);

        // Act
        $command->handle();

        // Assert
        $this->assertStringContainsString(
            'It was not possible to generate rules for the entity',
            implode('|', $messages)
        );
    }
}
