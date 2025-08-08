<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing\Mock;

use Serendipity\Hyperf\Testing\Extension\InputExtension;
use Serendipity\Presentation\Input;
use Serendipity\Testing\FailException;

class InputExtensionMock
{
    use InputExtension;

    private array $registeredTearDowns = [];

    private bool $makeWasCalled = false;

    private string $makeClass = '';

    private array $makeArgs = [];

    public function __construct(private readonly mixed $makeReturn = null)
    {
    }

    public function exposeSetUpInput(): void
    {
        $this->setUpInput();
    }

    public function exposeTearDownInput(bool $isRequestSetUp = false): void
    {
        $this->tearDownInput($isRequestSetUp);
    }

    /**
     * @template T of mixed
     * @param class-string<T> $class
     * @param array<string, array<string>|string> $headers
     * @param array<string, mixed> $args
     * @return T
     */
    public function exposeInput(
        string $class,
        array $parsedBody = [],
        array $queryParams = [],
        array $params = [],
        array $headers = [],
        array $args = [],
    ): mixed {
        return $this->input($class, $parsedBody, $queryParams, $params, $headers, $args);
    }

    /**
     * @param array<string, array<string>|string> $headers
     */
    public function exposeSetUpRequestContext(
        array $parsedBody = [],
        array $queryParams = [],
        array $params = [],
        array $headers = [],
        string $method = 'POST',
        string $uri = '/',
    ): void {
        $this->setUpRequestContext($parsedBody, $queryParams, $params, $headers, $method, $uri);
    }

    public function exposeInvoke(callable $action, Input $input, array $values = [], int $columns = 80): mixed
    {
        return $this->invoke($action, $input, $values, $columns);
    }

    public function getRegisteredTearDowns(): array
    {
        return $this->registeredTearDowns;
    }

    public function getMakeClass(): string
    {
        return $this->makeClass;
    }

    public function getMakeArgs(): array
    {
        return $this->makeArgs;
    }

    public function wasMakeCalled(): bool
    {
        return $this->makeWasCalled;
    }

    public function getIsRequestSetUp(): bool
    {
        return $this->isRequestSetUp;
    }

    /**
     * @throws FailException
     */
    public static function fail(string $message = ''): never
    {
        throw new FailException(
            $message
                ?: 'Test failure'
        );
    }

    protected function registerTearDown(callable $callback): void
    {
        $this->registeredTearDowns[] = $callback;
    }

    protected function make(string $class, array $args = []): mixed
    {
        $this->makeWasCalled = true;
        $this->makeClass = $class;
        $this->makeArgs = $args;
        return $this->makeReturn;
    }
}
