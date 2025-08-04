<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\File;

use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Infrastructure\File\RulesGenerator;

class RulesGeneratorTest extends TestCase
{
    use MakeExtension;

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
}
