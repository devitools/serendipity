<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\File;

use Constructo\Factory\ReflectorFactory;
use ReflectionException;

use function array_export;
use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;
use function Hyperf\Collection\data_get;
use function Serendipity\Type\Json\decode;

use const BASE_PATH;

class RulesGenerator
{
    public function __construct(private readonly ReflectorFactory $reflectorFactory)
    {
    }

    /**
     * @throws ReflectionException
     */
    public function generate(string $source): ?string
    {
        return match (true) {
            class_exists($source) => $this->generateRules($source),
            file_exists($source) => $this->generateRulesFromFile($source),
            default => null,
        };
    }

    /**
     * @param class-string<object> $source
     * @throws ReflectionException
     */
    private function generateRules(string $source): string
    {
        $reflector = $this->reflectorFactory->make();
        $schema = $reflector->reflect($source);
        $rules = $schema->rules();
        return array_export($rules, 1);
    }

    /**
     * @throws ReflectionException
     */
    private function generateRulesFromFile(string $filePath): ?string
    {
        $projectRoot = $this->projectRoot();
        $mappings = $this->mappings($projectRoot);

        $detected = null;
        foreach ($mappings as $namespace => $mappedPath) {
            $realMappedPath = stringify(realpath(sprintf('%s/%s', $projectRoot, stringify($mappedPath))));
            $namespace = stringify($namespace);
            $detected = $this->detect($realMappedPath, $namespace, $filePath);
        }
        if ($detected !== null && class_exists($detected)) {
            /** @var class-string<object> $detected */
            return $this->generateRules($detected);
        }
        return null;
    }


    private function projectRoot(): string
    {
        return stringify(
            defined('BASE_PATH')
                ? BASE_PATH
                : dirname(__DIR__, 3)
        );
    }

    private function mappings(string $projectRoot): array
    {
        $composerJsonContent = stringify(file_get_contents(sprintf('%s/composer.json', $projectRoot)));
        $target = arrayify(decode($composerJsonContent));
        return arrayify(data_get($target, 'autoload.psr-4', []));
    }

    private function detect(string $realMappedPath, string $namespace, string $filePath): ?string
    {
        $realFilePath = stringify(realpath($filePath));
        if (! str_starts_with($realFilePath, $realMappedPath)) {
            return null;
        }
        $relativePath = substr($realFilePath, strlen($realMappedPath) + 1);
        $search = [
            '/',
            '.php',
        ];
        $replace = [
            '\\',
            '',
        ];
        $class = $namespace . str_replace($search, $replace, $relativePath);
        return class_exists($class)
            ? $class
            : null;
    }
}
