<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Serendipity\Domain\Support\Reflective\Factory\Ruler;
use Symfony\Component\Console\Input\InputArgument;

use function array_export;
use function class_exists;
use function defined;
use function dirname;
use function file_exists;
use function file_get_contents;
use function Hyperf\Collection\data_get;
use function realpath;
use function Serendipity\Type\Cast\arrayify;
use function Serendipity\Type\Cast\stringify;
use function Serendipity\Type\Json\decode;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

class GenerateRules extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('dev:rules {entity}');
    }

    #[Override]
    public function configure()
    {
        parent::configure();
        $this->setDescription('Export the rules to validate an entity');
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $this->getOutput()?->title('Exporting rules');
        $entity = stringify($this->input?->getArgument('entity'));
        $this->line(sprintf("Generating rules for '%s'. Please wait...", $entity));
        $this->newLine();

        $output = match (true) {
            class_exists($entity) => $this->generateRules($entity),
            file_exists($entity) => $this->generateRulesFromFile($entity),
            default => null,
        };
        if (! $output) {
            $this->error('It was not possible to generate rules for the entity');
            return;
        }
        $this->info('Rules generated successfully');
        $this->line($output);
        $this->newLine();
        $this->line('Copy and paste the rules above into your input file');
        $this->line('--');
    }

    protected function getArguments()
    {
        return [
            [
                'entity',
                InputArgument::REQUIRED,
                'The entity to generate rules for. It can be a full qualified name or a file path.'
            ],
        ];
    }

    /**
     * @param class-string<object> $entity
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    private function generateRules(string $entity): string
    {
        $ruler = $this->container->get(Ruler::class);
        $ruleset = $ruler->ruleset($entity);
        $rules = $ruleset->all();
        return array_export($rules);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    private function generateRulesFromFile(string $filePath): ?string
    {
        $projectRoot = $this->projectRoot();
        $mappings = $this->mappings($projectRoot);

        foreach ($mappings as $namespace => $mappedPath) {
            $realMappedPath = stringify(realpath(sprintf('%s/%s', $projectRoot, stringify($mappedPath))));
            $namespace = stringify($namespace);
            $detected = $this->detect($realMappedPath, $namespace, $filePath);
            if ($detected !== null) {
                return $detected;
            }
        }
        return null;
    }


    private function projectRoot(): string
    {
        return stringify(defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
    }

    private function mappings(string $projectRoot): array
    {
        $composerJsonContent = stringify(file_get_contents(sprintf('%s/composer.json', $projectRoot)));
        $target = arrayify(decode($composerJsonContent));
        return arrayify(data_get($target, 'autoload.psr-4', []));
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function detect(string $realMappedPath, string $namespace, string $filePath): ?string
    {
        $realFilePath = stringify(realpath($filePath));
        if (! str_starts_with($realFilePath, $realMappedPath)) {
            return null;
        }
        $relativePath = substr($realFilePath, strlen($realMappedPath) + 1);
        $class = $namespace . str_replace(['/', '.php'], ['\\', ''], $relativePath);
        return (! class_exists($class)) ? null : $this->generateRules($class);
    }
}
