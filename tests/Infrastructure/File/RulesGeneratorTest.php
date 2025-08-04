<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\File;

use PHPUnit\Framework\TestCase;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Infrastructure\File\RulesGenerator;

class RulesGeneratorTest extends TestCase
{
    use MakeExtension;

    public function testGenerateWithExistingClass(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate(GameCommand::class);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
        $this->assertStringContainsString('slug', $result);
        $this->assertStringContainsString('published_at', $result);
        $this->assertStringContainsString('data', $result);
        $this->assertStringContainsString('features', $result);
    }

    public function testGenerateWithExistingFile(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);
        $filePath = 'src/Example/Game/Domain/Entity/Command/GameCommand.php';

        // Act
        $result = $generator->generate($filePath);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
        $this->assertStringContainsString('slug', $result);
        $this->assertStringContainsString('published_at', $result);
        $this->assertStringContainsString('data', $result);
        $this->assertStringContainsString('features', $result);
    }

    public function testGenerateWithNonExistentClass(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('NonExistentClass');

        // Assert
        $this->assertNull($result);
    }

    public function testGenerateWithNonExistentFile(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('/path/to/nonexistent/file.php');

        // Assert
        $this->assertNull($result);
    }

    public function testGenerateWithInvalidInput(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('');

        // Assert
        $this->assertNull($result);
    }

    public function testGenerateWithFileOutsideProjectStructure(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('/tmp/some-random-file.php');

        // Assert
        $this->assertNull($result);
    }

    public function testGenerateWithFileNotMappedInComposer(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('tests/Infrastructure/File/RulesGeneratorTest.php');

        // Assert
        $this->assertNull($result);
    }

    public function testGenerateWithRelativeFilePath(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('./src/Example/Game/Domain/Entity/Command/GameCommand.php');

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
    }

    public function testGenerateWithAbsoluteFilePath(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);
        $absolutePath = realpath('src/Example/Game/Domain/Entity/Command/GameCommand.php');

        // Act
        $result = $generator->generate($absolutePath);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
    }

    public function testGenerateWithWhitespaceInput(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('   ');

        // Assert
        $this->assertNull($result);
    }

    public function testGenerateWithNullInput(): void
    {
        // Arrange
        $generator = $this->make(RulesGenerator::class);

        // Act
        $result = $generator->generate('null');

        // Assert
        $this->assertNull($result);
    }
}
