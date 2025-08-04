<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Output;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Entity\Entity;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Output\Ok;
use stdClass;

class OkTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    public function testShouldHaveNoContent(): void
    {
        $word = $this->generator()
            ->word();
        $properties = ['word' => $word];
        $output = Ok::createFrom(properties: $properties);
        $this->assertNull($output->content());
        $this->assertEquals(
            $properties,
            $output->properties()
                ->toArray()
        );
    }

    #[TestWith([1])]
    #[TestWith([1.1])]
    #[TestWith(['word'])]
    #[TestWith([['word' => 'word']])]
    #[TestWith([null])]
    #[TestWith([true])]
    #[TestWith([new stdClass()])]
    public function testShouldHandleMixedContent(mixed $content): void
    {
        $output = Ok::createFrom($content);
        $this->assertEquals($content, $output->content());
        $this->assertEquals([],
            $output->properties()
                ->toArray());
    }

    public function testShouldHandleMessage(): void
    {
        $message = Ok::createFrom('message', ['id' => 1234567890]);
        $output = Ok::createFrom($message);
        $this->assertEquals('message', $output->content());
        $this->assertEquals(['id' => 1234567890],
            $output->properties()
                ->toArray());
    }

    public function testShouldHandleExportable(): void
    {
        $entity = new class extends Entity {
            public string $value = 'none';
        };
        $output = Ok::createFrom($entity);
        $this->assertEquals('none', $output->content()->value);
    }
}
