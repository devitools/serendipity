<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Request;

use Constructo\Support\Set;
use Hyperf\Context\RequestContext;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Support\MessageBag;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Serendipity\Domain\Event\ValidationFailedEvent;
use Serendipity\Hyperf\Request\HyperfFormRequest;
use Swow\Psr7\Message\ResponsePlusInterface;
use Swow\Psr7\Message\ServerRequestPlusInterface;

class HyperfFormRequestTest extends TestCase
{
    private ContainerInterface $container;

    private EventDispatcherInterface $eventDispatcher;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        RequestContext::set($request);

        $response = $this->createMock(ResponsePlusInterface::class);
        ResponseContext::set($response);
    }

    public function testShouldDispatchEventOnValidationFailure(): void
    {
        $errors = $this->createMock(MessageBag::class);
        $errors->method('__toString')
            ->willReturn('{"email":["The email field is required."]}');

        $this->validator->method('errors')
            ->willReturn($errors);

        $this->validator->method('fails')
            ->willReturn(true);

        $validatorFactory = $this->createMock(ValidatorFactoryInterface::class);
        $validatorFactory->method('make')
            ->willReturn($this->validator);

        $this->container->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                EventDispatcherInterface::class => $this->eventDispatcher,
                ValidatorFactoryInterface::class => $validatorFactory,
                default => $this->createMock($name),
            });

        $formRequest = new class ($this->container) extends HyperfFormRequest {
            public function rules(): array
            {
                return ['email' => 'required|email'];
            }

            protected function authorize(): bool
            {
                return true;
            }
        };

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(fn ($event) => $event instanceof ValidationFailedEvent
                    && str_contains($event->resource, 'http::')
                    && $event->values instanceof Set
                    && is_string($event->message))
            );

        $this->expectException(ValidationException::class);

        $formRequest->validateResolved();
    }
}
