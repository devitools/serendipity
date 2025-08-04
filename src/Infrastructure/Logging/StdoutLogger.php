<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Logging;

use Stringable;
use Symfony\Component\Console\Output\OutputInterface;

use function array_export;
use function in_array;
use function Constructo\Cast\stringify;

class StdoutLogger extends AbstractLogger
{
    public function __construct(
        private readonly OutputInterface $output,
        public readonly array $levels,
        public readonly string $format,
        public readonly string $env,
    ) {
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (! in_array($level, $this->levels, true)) {
            return;
        }
        $variables = $this->variables($level, $context);
        $messages = $this->message($this->format, $message, $variables);
        $this->output->writeln(str_replace(PHP_EOL, '\n', $messages));
    }

    protected function variables(mixed $level, array $context): array
    {
        return [
            'env' => $this->env,
            'level' => stringify($level),
            'context' => array_export($context),
        ];
    }
}
