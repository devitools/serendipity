<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Input;

use Constructo\Testing\FakerExtension;
use Serendipity\Hyperf\Testing\Extension\InputExtension;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Input;
use Serendipity\Presentation\Input\Params;
use Serendipity\Test\Testing\ExtensibleCase;

final class ParamsTest extends ExtensibleCase
{
    use MakeExtension;
    use InputExtension;
    use FakerExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInput();
    }

    public function testShouldGetValueFromParams(): void
    {
        $param = $this->generator()
            ->uuid();
        $params = ['param' => $param];
        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'param' => 'required|string',
                ],
            ]
        );

        $this->setUpRequestContext(params: $params);

        $paramsResolver = new Params($input);
        $data = [];
        $resolvedData = $paramsResolver->resolve($data);

        $this->assertArrayHasKey('param', $resolvedData);
        $this->assertEquals($param, $resolvedData['param']);
    }

    public function testShouldNotOverrideExistingParamValue(): void
    {
        $paramValue = 'existing-value';
        $routeParamValue = 'route-value';
        $params = ['param' => $routeParamValue];

        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'param' => 'required|string',
                ],
            ]
        );

        $this->setUpRequestContext(params: $params);

        $paramsResolver = new Params($input);
        $data = ['param' => $paramValue];
        $resolvedData = $paramsResolver->resolve($data);

        $this->assertArrayHasKey('param', $resolvedData);
        $this->assertEquals($paramValue, $resolvedData['param']);
        $this->assertNotEquals($routeParamValue, $resolvedData['param']);
    }

    public function testShouldSkipParamIfNotFoundInRoute(): void
    {
        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'nonExistentParam' => 'required|string',
                ],
            ]
        );

        $this->setUpRequestContext();

        $paramsResolver = new Params($input);
        $data = [];
        $resolvedData = $paramsResolver->resolve($data);

        $this->assertArrayNotHasKey('nonExistentParam', $resolvedData);
    }
}
