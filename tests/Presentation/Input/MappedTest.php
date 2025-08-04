<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Input;

use Constructo\Testing\FakerExtension;
use Serendipity\Hyperf\Testing\Extension\InputExtension;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Input;
use Serendipity\Presentation\Input\Mapped;
use Serendipity\Test\Testing\ExtensibleCase;

use function Serendipity\Type\Cast\stringify;

final class MappedTest extends ExtensibleCase
{
    use MakeExtension;
    use InputExtension;
    use FakerExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInput();
    }

    public function testShouldMapStringSourceToTarget(): void
    {
        $mappings = [
            'name' => 'source.0.field',
            'description' => 'source.1.field',
        ];
        $data = [
            'source' => [
                [
                    'field' => 'my value',
                ],
                [
                    'field' => 'nice',
                ],
            ],
        ];
        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'name' => 'required|string',
                    'description' => 'required|string',
                ],
                'mappings' => $mappings,
            ]
        );

        $mapped = new Mapped($input);
        $resolved = $mapped->resolve($data);

        $this->assertArrayHasKey('name', $resolved);
        $this->assertEquals('my value', $resolved['name']);
        $this->assertArrayHasKey('description', $resolved);
        $this->assertEquals('nice', $resolved['description']);
    }

    public function testShouldApplyCallableTransformations(): void
    {
        $mappings = [
            'name' => fn ($data) => strtoupper(stringify($data['source'][0]['field'])),
            'description' => fn ($data) => 'cool: ' . $data['source'][1]['field'],
        ];
        $parsedBody = [
            'source' => [
                [
                    'field' => 'my value',
                ],
                [
                    'field' => 'nice',
                ],
            ],
        ];
        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'name' => 'required|string',
                    'description' => 'required|string',
                ],
                'mappings' => $mappings,
            ]
        );

        $mapped = new Mapped($input);
        $resolved = $mapped->resolve($parsedBody);

        $this->assertArrayHasKey('name', $resolved);
        $this->assertEquals('MY VALUE', $resolved['name']);
        $this->assertArrayHasKey('description', $resolved);
        $this->assertEquals('cool: nice', $resolved['description']);
    }

    public function testShouldSkipNullValuesFromExtraction(): void
    {
        $mappings = [
            'nonexistentTarget' => 'path.that.does.not.exist',
        ];

        $parsedBody = [
            'existing' => 'value',
        ];

        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'nonexistentTarget' => 'nullable|string',
                ],
                'mappings' => $mappings,
            ]
        );

        $mapped = new Mapped($input);
        $parsedBody['preserve'] = 'this value';
        $result = $mapped->resolve($parsedBody);

        $this->assertArrayHasKey('preserve', $result);
        $this->assertEquals('this value', $result['preserve']);
        $this->assertArrayNotHasKey('nonexistentTarget', $result);
    }

    public function testShouldMapNestedTargetPaths(): void
    {
        $mappings = [
            'user.name' => 'source.name',
            'user.profile.age' => 'source.age',
            'user.address.street' => 'source.location.street',
        ];

        $parsedBody = [
            'source' => [
                'name' => 'John Doe',
                'age' => 30,
                'location' => [
                    'street' => 'Main St',
                ],
            ],
        ];

        $input = $this->make(
            Input::class,
            [
                'rules' => [
                    'user.name' => 'string',
                    'user.profile.age' => 'integer',
                    'user.address.street' => 'string',
                ],
                'mappings' => $mappings,
            ]
        );

        $mapped = new Mapped($input);
        $result = $mapped->resolve($parsedBody);

        $this->assertEquals('John Doe', $result['user']['name']);
        $this->assertEquals(30, $result['user']['profile']['age']);
        $this->assertEquals('Main St', $result['user']['address']['street']);
    }
}
