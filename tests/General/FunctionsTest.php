<?php

declare(strict_types=1);

namespace General;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testShouldRequireFunctions(): void
    {
        // Require the function files first
        $files = [
            'src/mirror.php',
            'src/polyfill.php',
            'src/runtime.php',
        ];
        foreach ($files as $file) {
            $filename = __DIR__ . '/../../' . $file;
            $this->assertFileExists($filename, sprintf("File '%s' does not exist", $file));
            require $filename;
        }
    }
}
