<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Faker\Resolver;

use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Support\Reflective\CaseNotation;
use Serendipity\Domain\Support\Reflective\Factory\Target;
use Serendipity\Domain\Support\Set;
use Serendipity\Test\Testing\Stub\Command;
use Serendipity\Test\Testing\Stub\DeepDown;
use Serendipity\Test\Testing\Stub\Variety;
use Serendipity\Testing\Faker\Resolver\FromPreset;
use stdClass;

/**
 * @internal
 */
final class FromPresetTest extends TestCase
{
    public function testShouldResolvePresetWithExactParameterName(): void
    {
        $resolver = new FromPreset(CaseNotation::SNAKE);
        $target = Target::createFrom(Command::class);
        $parameters = $target->getReflectionParameters();

        [0 => $emailParameter] = $parameters;

        $presetValue = 'test@example.com';
        $set = Set::createFrom(['email' => $presetValue]);
        $value = $resolver->resolve($emailParameter, $set);

        $this->assertNotNull($value);
        $this->assertEquals($presetValue, $value->content);
    }

    public function testShouldResolvePresetWithCamelCaseParameterName(): void
    {
        $resolver = new FromPreset(CaseNotation::SNAKE);
        $target = Target::createFrom(Command::class);
        $parameters = $target->getReflectionParameters();

        [11 => $leadIdParameter] = $parameters; // leadId em camelCase

        $presetValue = 'ABC123';
        $set = Set::createFrom(['lead_id' => $presetValue]); // snake_case
        $value = $resolver->resolve($leadIdParameter, $set);

        $this->assertNotNull($value);
        $this->assertEquals($presetValue, $value->content);
    }

    public function testShouldResolvePresetWithMultiWordCamelCaseParameterName(): void
    {
        $resolver = new FromPreset(CaseNotation::SNAKE);
        $target = Target::createFrom(Command::class);
        $parameters = $target->getReflectionParameters();

        [1 => $ipAddressParameter] = $parameters; // ipAddress em camelCase

        $presetValue = '192.168.0.1';
        $set = Set::createFrom(['ip_address' => $presetValue]); // snake_case
        $value = $resolver->resolve($ipAddressParameter, $set);

        $this->assertNotNull($value);
        $this->assertEquals($presetValue, $value->content);
    }

    public function testShouldResolveNestedPropertyName(): void
    {
        $resolver = new FromPreset(CaseNotation::SNAKE);
        $target = Target::createFrom(DeepDown::class);
        $parameters = $target->getReflectionParameters();

        [0 => $deepDeepDownParameter] = $parameters; // deepDeepDown em camelCase

        $presetValue = new stdClass();
        $set = Set::createFrom(['deep_deep_down' => $presetValue]); // snake_case
        $value = $resolver->resolve($deepDeepDownParameter, $set);

        $this->assertNotNull($value);
        $this->assertEquals($presetValue, $value->content);
    }

    public function testShouldFallbackToNextResolverWhenPresetNotFound(): void
    {
        $resolver = new FromPreset(CaseNotation::SNAKE);
        $target = Target::createFrom(Variety::class);
        $parameters = $target->getReflectionParameters();

        [0 => $unionParameter] = $parameters;

        $set = Set::createFrom(['other_field' => 'value']); // Preset não contém a chave necessária
        $value = $resolver->resolve($unionParameter, $set);

        $this->assertNull($value);
    }
}
