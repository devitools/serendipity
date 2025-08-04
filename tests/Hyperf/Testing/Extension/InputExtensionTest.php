<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing\Extension;

use Hyperf\Context\Context;
use Hyperf\HttpServer\Router\Dispatched;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Serendipity\Hyperf\Testing\Mock\InputExtensionMock;
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
        Context::destroy(ServerRequestInterface::class);
        Context::destroy('http.request.parsedData');
        parent::tearDown();
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
}
