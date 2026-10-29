<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing\Extension;

use Hyperf\Context\Context;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Serendipity\Hyperf\Testing\Mock\InputExtensionMock;
use Serendipity\Presentation\Input;
use Serendipity\Testing\FailException;

final class InputExtensionTest extends TestCase
{
    private InputExtensionMock $mock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock = new InputExtensionMock('test-return');
    }

    protected function tearDown(): void
    {
        $this->mock->exposeTearDownInput();
        Context::destroy('unrelated-context');
        parent::tearDown();
    }

    public function testReusedInputDiscardsItsCachedValidatorAndScene(): void
    {
        $input = $this->createPartialMock(Input::class, []);
        $this->mock = new InputExtensionMock($input);
        $this->mock->exposeSetUpInput();
        $first = $this->mock->exposeInput(Input::class, ['value' => 'first']);
        $validatorKey = sprintf('%s:%s', spl_object_hash($input), ValidatorInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);
        Context::set($validatorKey, $validator);
        $input->scene('previous-request');
        Context::set('unrelated-context', 'preserved');
        $this->assertSame($validator, Context::get($validatorKey));
        $this->assertSame('previous-request', $input->getScene());

        $second = $this->mock->exposeInput(Input::class, ['value' => 'second']);

        $this->assertSame($first, $second);
        $this->assertNull(Context::get($validatorKey));
        $this->assertNull($input->getScene());
        $this->assertSame(['value' => 'second'], Context::get(ServerRequestInterface::class)->getParsedBody());
        $this->assertSame('preserved', Context::get('unrelated-context'));
    }

    public function testScalarFactoryResultRemainsSupported(): void
    {
        $this->mock = new InputExtensionMock(0);
        $this->mock->exposeSetUpInput();

        $result = $this->mock->exposeInput('TestClass', ['value' => 'scalar']);

        $this->assertSame(0, $result);
        $this->assertSame(['value' => 'scalar'], Context::get(ServerRequestInterface::class)->getParsedBody());
    }

    public function testRegisteredTearDownClearsAllValidationContextsAndPreservesOtherState(): void
    {
        $this->mock->exposeSetUpInput();
        $first = $this->createPartialMock(Input::class, []);
        $second = $this->createPartialMock(Input::class, []);
        $firstKey = sprintf('%s:%s', spl_object_hash($first), ValidatorInterface::class);
        $secondKey = sprintf('%s:%s', spl_object_hash($second), ValidatorInterface::class);
        Context::set($firstKey, $this->createMock(ValidatorInterface::class));
        Context::set($secondKey, $this->createMock(ValidatorInterface::class));
        $first->scene('first');
        $second->scene('second');
        Context::set('unrelated-context', 'preserved');
        Context::set(ServerRequestInterface::class, 'request');
        Context::set('http.request.parsedData', ['stale' => true]);
        $cleanup = $this->mock->getRegisteredTearDowns()[0];

        $cleanup();

        $this->assertFalse($this->mock->getIsRequestSetUp());
        $this->assertNull(Context::get($firstKey));
        $this->assertNull(Context::get($secondKey));
        $this->assertNull($first->getScene());
        $this->assertNull($second->getScene());
        $this->assertNull(Context::get(ServerRequestInterface::class));
        $this->assertNull(Context::get('http.request.parsedData'));
        $this->assertSame('preserved', Context::get('unrelated-context'));
    }

    public function testSetUpInputShouldRegisterTearDown(): void
    {
        // Act
        $this->mock->exposeSetUpInput();

        // Assert
        $this->assertTrue($this->mock->getIsRequestSetUp());
        $this->assertCount(1, $this->mock->getRegisteredTearDowns());
    }

    public function testTearDownInputShouldUpdateStateAndDestroyContext(): void
    {
        // Arrange
        Context::set(ServerRequestInterface::class, 'test-value');
        Context::set('http.request.parsedData', 'test-data');

        // Act
        $this->mock->exposeTearDownInput(true);

        // Assert
        $this->assertTrue($this->mock->getIsRequestSetUp());
        $this->assertNull(Context::get(ServerRequestInterface::class, null));
        $this->assertNull(Context::get('http.request.parsedData', null));
    }

    public function testInputShouldFailWhenRequestNotSetUp(): void
    {
        // Arrange
        $this->mock->exposeTearDownInput(false);

        // Assert
        $this->expectException(FailException::class);
        $this->expectExceptionMessage('Request is not set up.');

        // Act
        $this->mock->exposeInput('TestClass');
    }

    public function testInputShouldSetUpContextAndCallMakeWhenRequestIsSetUp(): void
    {
        // Arrange
        $this->mock->exposeSetUpInput();
        $class = 'TestClass';
        $parsedBody = ['body' => 'test'];
        $queryParams = ['query' => 'param'];
        $params = ['route' => 'param'];
        $headers = ['Content-Type' => 'application/json'];
        $args = ['constructor' => 'arg'];

        // Act
        $result = $this->mock->exposeInput($class, $parsedBody, $queryParams, $params, $headers, $args);

        // Assert
        $this->assertTrue($this->mock->wasMakeCalled());
        $this->assertEquals($class, $this->mock->getMakeClass());
        $this->assertEquals($args, $this->mock->getMakeArgs());
        $this->assertEquals('test-return', $result);

        // Verificar se o contexto foi configurado corretamente
        $request = Context::get(ServerRequestInterface::class);
        $this->assertNotNull($request);
        $this->assertEquals($parsedBody, $request->getParsedBody());
        $this->assertEquals($queryParams, $request->getQueryParams());
        $this->assertArrayHasKey('Content-Type', $request->getHeaders());
    }

    public function testSetUpRequestContextShouldConfigureRequestProperly(): void
    {
        // Arrange
        $parsedBody = ['test' => 'body'];
        $queryParams = ['test' => 'query'];
        $params = ['id' => '123'];
        $headers = ['Authorization' => 'Bearer token'];
        $method = 'GET';
        $uri = '/test';

        // Act
        $this->mock->exposeSetUpRequestContext(
            $parsedBody,
            $queryParams,
            $params,
            $headers,
            $method,
            $uri
        );

        // Assert
        $request = Context::get(ServerRequestInterface::class);
        $this->assertNotNull($request);
        $this->assertEquals($parsedBody, $request->getParsedBody());
        $this->assertEquals($queryParams, $request->getQueryParams());
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals(
            $uri,
            $request->getUri()
                ->__toString()
        );

        // Verificar se os cabeçalhos foram configurados corretamente
        $this->assertArrayHasKey('Authorization', $request->getHeaders());
        $this->assertEquals(['Bearer token'], $request->getHeader('Authorization'));

        // Verificar se os params da rota foram configurados corretamente
        $dispatched = $request->getAttribute(Dispatched::class);
        $this->assertNotNull($dispatched);
        $this->assertEquals($params, $dispatched->params);
    }

    public function testShouldCallInvoke(): void
    {
        $input = $this->createMock(Input::class);

        $action = fn (Input $called) => $called;
        $invoked = $this->mock->exposeInvoke($action, $input);
        $this->assertEquals($input, $invoked);
    }

    public function testShouldFailCallingInvoke(): void
    {
        $input = $this->createMock(Input::class);

        $this->expectException(FailException::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $action = fn (Input $called) => throw new ValidationException($validator);
        $this->mock->exposeInvoke($action, $input);
    }
}
