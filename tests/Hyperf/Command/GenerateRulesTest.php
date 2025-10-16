<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\Hyperf\Command\GenerateRules;
use Serendipity\Infrastructure\File\RulesGenerator;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateRulesTest extends TestCase
{
    public function testConfigure(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new GenerateRules($container);
        $this->assertSame('dev:rules {source}', $command->getName());
        $this->assertSame('Export the rules to validate an entity', $command->getDescription());
    }

    public function testHandleNoSourceProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $container = $this->createMock(ContainerInterface::class);
        $command = new GenerateRules($container);
        $input = new ArrayInput([]);

        $command->setInput($input);
        $command->handle();
    }

    public function testHandleSuccessfulRuleGeneration(): void
    {
        // Arrange
        $rulesGenerator = $this->createMock(RulesGenerator::class);
        $rulesGenerator->expects($this->once())
            ->method('generate')
            ->with('TestClass')
            ->willReturn("[\n    'name' => 'required|string',\n]");

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(RulesGenerator::class)
            ->willReturn($rulesGenerator);

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
            ->willReturn('TestClass');
        $command->setInput($input);

        // Act
        $command->handle();

        // Assert
        $this->assertStringContainsString('Rules generated successfully', implode('|', $messages));
        $this->assertStringContainsString('name', implode('|', $messages));
    }

    public function testHandleFailedRuleGeneration(): void
    {
        // Arrange
        $rulesGenerator = $this->createMock(RulesGenerator::class);
        $rulesGenerator->expects($this->once())
            ->method('generate')
            ->with('NonExistentClass')
            ->willReturn(null);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(RulesGenerator::class)
            ->willReturn($rulesGenerator);

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
            'It was not possible to generate rules for the source',
            implode('|', $messages)
        );
    }
}
