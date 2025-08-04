<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation;

use Constructo\Support\Set;
use Constructo\Testing\FakerExtension;
use Serendipity\Hyperf\Testing\Extension\InputExtension;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Input;
use Serendipity\Test\Testing\ExtensibleCase;

final class InputTest extends ExtensibleCase
{
    use MakeExtension;
    use InputExtension;
    use FakerExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInput();
    }

    public function testShouldAuthorize(): void
    {
        $input = $this->make(Input::class, ['authorize' => false]);

        $this->assertFalse($input->authorize());
    }

    public function testShouldHasGivenRules(): void
    {
        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'test' => [
                        'sometimes',
                        'string',
                    ],
                ],
            ]
        );
        $rules = $input->rules();

        $this->assertArrayHasKey('test', $rules);
        $this->assertEquals(
            [
                'sometimes',
                'string',
            ],
            $rules['test']
        );
    }

    public function testShouldGetValueFromParsedBody(): void
    {
        $parsedBody = ['datum' => 'cool'];
        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'datum' => 'required|string',
                ],
            ]
        );
        $this->setUpRequestContext($parsedBody);
        $this->assertEquals('cool', $input->value('datum'));
        $this->assertEquals(
            $parsedBody,
            $input->values()
                ->toArray()
        );
    }

    public function testShouldGetValueFromValues(): void
    {
        $array = ['datum' => 'cool'];
        /** @var Input $input */
        $input = $this->make(
            Input::class,
            [
                'values' => Set::createFrom($array),
            ]
        );
        $this->assertEquals(
            $array,
            $input->values()
                ->toArray()
        );
        $this->assertSame($input->content(), $input->values());
    }

    public function testShouldGetPropertyFromHeaders(): void
    {
        $headers = ['header' => 'cool'];
        $input = $this->input(class: Input::class, headers: $headers);
        $this->setUpRequestContext(headers: $headers);
        $this->assertEquals(
            $headers,
            $input->properties()
                ->toArray()
        );
    }

    public function testShouldGetPropertyFromProperties(): void
    {
        $array = ['header' => 'cool'];
        $input = $this->make(
            Input::class,
            [
                'properties' => Set::createFrom($array),
            ]
        );
        $this->assertEquals(
            $array,
            $input->properties()
                ->toArray()
        );
    }

    public function testShouldCallValueBehindPost(): void
    {
        $input = $this->make(Input::class, ['values' => Set::createFrom(['test' => 'cool'])]);
        $this->assertEquals('cool', $input->post('test'));
    }

    public function testShouldCallValuesBehindPost(): void
    {
        $input = $this->make(Input::class, ['values' => Set::createFrom(['test' => 'cool'])]);
        $this->assertEquals(['test' => 'cool'], $input->post());
    }

    public function testShouldCallValueBehindInput(): void
    {
        $input = $this->make(Input::class, ['values' => Set::createFrom(['test' => 'cool'])]);
        $this->assertEquals('cool', $input->input('test'));
    }
}
