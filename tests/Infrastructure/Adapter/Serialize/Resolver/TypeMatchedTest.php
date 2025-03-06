<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Adapter\Serialize\Resolver;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Exception\Adapter\NotResolved;
use Serendipity\Domain\Support\Reflective\Factory\Target;
use Serendipity\Domain\Support\Set;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Infrastructure\Adapter\Serialize\Resolver\TypeMatched;
use Serendipity\Test\Testing\Stub\Builtin;
use Serendipity\Test\Testing\Stub\Command;
use Serendipity\Test\Testing\Stub\EntityStub;
use Serendipity\Test\Testing\Stub\Intersection;
use Serendipity\Test\Testing\Stub\Native;
use Serendipity\Test\Testing\Stub\NoConstructor;
use Serendipity\Test\Testing\Stub\NotNative;
use Serendipity\Test\Testing\Stub\Stub;
use Serendipity\Test\Testing\Stub\Type\BackedEnumeration;
use Serendipity\Test\Testing\Stub\Type\Enumeration;
use Serendipity\Test\Testing\Stub\Type\Intersected;
use Serendipity\Test\Testing\Stub\Union;
use Serendipity\Test\Testing\Stub\Variety;
use Serendipity\Testing\Extension\FakerExtension;
use stdClass;

/**
 * @internal
 */
final class TypeMatchedTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    public function testTypeMatchedBuiltinSuccessfully(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Builtin::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(6, $parameters);

        $set = Set::createFrom([
            'string' => 'string',
            'int' => 10,
            'float' => 10.1,
            'bool' => true,
            'array' => ['a', 'b', 'c'],
            'null' => null,
        ]);

        [
            $string,
            $int,
            $float,
            $bool,
            $array,
            $null,
        ] = $parameters;

        $value = $resolver->resolve($string, $set);
        $this->assertSame('string', $value->content);

        $value = $resolver->resolve($int, $set);
        $this->assertSame(10, $value->content);

        $value = $resolver->resolve($float, $set);
        $this->assertSame(10.1, $value->content);

        $value = $resolver->resolve($bool, $set);
        $this->assertTrue($value->content);

        $value = $resolver->resolve($array, $set);
        $this->assertSame(['a', 'b', 'c'], $value->content);

        $value = $resolver->resolve($null, $set);
        $this->assertNull($value->content);
    }

    public function testTypeMatchedNativeSuccessfully(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Native::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(5, $parameters);

        $set = Set::createFrom([
            'callable' => fn () => null,
            'std_class' => new stdClass(),
            'date_time_immutable' => new DateTimeImmutable(),
            'date_time' => new DateTime(),
            'date_time_interface' => new DateTime(),
        ]);

        [
            $callable,
            $stdClass,
            $dateTimeImmutable,
        ] = $parameters;

        $value = $resolver->resolve($callable, $set);
        $this->assertIsCallable($value->content);

        $value = $resolver->resolve($stdClass, $set);
        $this->assertInstanceOf(stdClass::class, $value->content);

        $value = $resolver->resolve($dateTimeImmutable, $set);
        $this->assertInstanceOf(DateTimeImmutable::class, $value->content);
    }

    public function testTypeMatchedNotNativeSuccessfully(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(NotNative::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(3, $parameters);

        $set = Set::createFrom([
            'backed' => BackedEnumeration::BAZ,
            'enum' => Enumeration::ONE,
            'stub' => new Stub(
                'string',
                10
            ),
        ]);

        [
            $backed,
            $enumeration,
            $stub,
        ] = $parameters;

        $value = $resolver->resolve($backed, $set);
        $this->assertEquals(BackedEnumeration::BAZ, $value->content);

        $value = $resolver->resolve($enumeration, $set);
        $this->assertEquals(Enumeration::ONE, $value->content);

        $value = $resolver->resolve($stub, $set);
        $this->assertInstanceOf(Stub::class, $value->content);
    }

    public function testTypeMatchedIntersectionSuccessfully(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Intersection::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(1, $parameters);

        $set = Set::createFrom([
            'intersected' => new Intersected(),
        ]);

        [$intersected] = $parameters;

        $value = $resolver->resolve($intersected, $set);
        $this->assertInstanceOf(Intersected::class, $value->content);
    }

    public function testTypeMatchedUnionSuccessfully(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Union::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(3, $parameters);

        $set = Set::createFrom([
            'builtin' => 23,
            'nullable' => null,
            'native' => new stdClass(),
        ]);

        [
            $builtin,
            $nullable,
            $native,
        ] = $parameters;

        $value = $resolver->resolve($builtin, $set);
        $this->assertSame(23, $value->content);

        $value = $resolver->resolve($nullable, $set);
        $this->assertNull($value->content);

        $value = $resolver->resolve($native, $set);
        $this->assertInstanceOf(stdClass::class, $value->content);
    }

    public function testTypeMatchedShouldNotResolveNoValue(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Intersection::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(1, $parameters);

        $set = Set::createFrom([]);

        [$intersected] = $parameters;
        $value = $resolver->resolve($intersected, $set);
        $this->assertInstanceOf(NotResolved::class, $value->content);
    }

    public function testTypeMatchedShouldNotResolveInvalidForIntersection(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Intersection::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(1, $parameters);

        $set = Set::createFrom([
            'intersected' => new stdClass(),
        ]);

        [$intersected] = $parameters;
        $value = $resolver->resolve($intersected, $set);
        $this->assertInstanceOf(NotResolved::class, $value->content);
    }

    public function testTypeMatchedShouldNotResolveInvalidForUnion(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Union::class);
        $parameters = $target->getReflectionParameters();

        $set = Set::createFrom([
            'builtin' => true,
        ]);

        [$builtin] = $parameters;

        $value = $resolver->resolve($builtin, $set);
        $this->assertInstanceOf(NotResolved::class, $value->content);
    }

    public function testTypeMatchedShouldResolveVariety(): void
    {
        $resolver = new TypeMatched();
        $target = Target::createFrom(Variety::class);
        $parameters = $target->getReflectionParameters();

        $this->assertCount(4, $parameters);

        $set = Set::createFrom([
            'union' => 23,
            'intersection' => new Intersected(),
            'nested' => new EntityStub(
                10,
                10.1,
                'string',
                true,
                new NoConstructor(),
                null,
                null
            ),
            'whatever' => new stdClass(),
        ]);

        [
            $union,
            $intersection,
            $nested,
            $whatever,
        ] = $parameters;

        $value = $resolver->resolve($union, $set);
        $this->assertSame(23, $value->content);

        $value = $resolver->resolve($intersection, $set);
        $this->assertInstanceOf(Intersected::class, $value->content);

        $value = $resolver->resolve($nested, $set);
        $this->assertInstanceOf(EntityStub::class, $value->content);

        $value = $resolver->resolve($whatever, $set);
        $this->assertInstanceOf(stdClass::class, $value->content);
    }

    public function testTypeMatchedShouldResolveVNullableFilledWithNull(): void
    {
        $resolver = new TypeMatched(path: ['*']);
        $target = Target::createFrom(Command::class);
        $parameters = $target->getReflectionParameters();

        $set = Set::createFrom([
            'email' => $this->generator()->email(),
            'address' => null,
            'dob' => null,
        ]);

        [
            0 => $email,
            6 => $address,
            13 => $dob,
        ] = $parameters;

        $value = $resolver->resolve($email, $set);
        $this->assertIsString($value->content);

        $value = $resolver->resolve($address, $set);
        $this->assertNull($value->content);

        $value = $resolver->resolve($dob, $set);
        $this->assertNull($value->content);
    }
}
