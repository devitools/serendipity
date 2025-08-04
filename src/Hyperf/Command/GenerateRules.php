<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Command;

use Hyperf\Command\Command as HyperfCommand;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Serendipity\Infrastructure\File\RulesGenerator;
use Symfony\Component\Console\Input\InputArgument;

use function assert;
use function Constructo\Cast\stringify;
use function sprintf;

class GenerateRules extends HyperfCommand
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('dev:rules {source}');
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
        $this->getOutput()
            ?->title('Exporting rules');
        $source = stringify($this->input?->getArgument('source'));
        $this->line(sprintf("Generating rules for '%s'. Please wait...", $source));
        $this->newLine();

        $generator = $this->container->get(RulesGenerator::class);
        assert($generator instanceof RulesGenerator);
        $output = $generator->generate($source);
        if (! $output) {
            $this->error(sprintf("It was not possible to generate rules for the source '%s'", $source));
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
                'source',
                InputArgument::REQUIRED,
                'The source to generate rules for. It can be a full qualified name or a file path.',
            ],
        ];
    }
}
