<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use DateTimeImmutable;
use SensitiveParameter;
use Constructo\Support\Entity;
use Serendipity\Domain\Support\Reflective\Attribute\Define;
use Serendipity\Domain\Support\Reflective\Attribute\Pattern;
use Serendipity\Domain\Support\Reflective\Definition\Type;
use Serendipity\Test\Testing\Stub\Type\Gender;
use Serendipity\Test\Testing\Stub\Type\Sensitive;

class Command extends Entity
{
    /**
     * @SuppressWarnings(ExcessiveParameterList)
     * @SuppressWarnings(ShortVariable)
     */
    public function __construct(
        #[Define(Type::EMAIL)]
        public readonly string $email,
        #[Define(Type::IP_V4, Type::IP_V6)]
        public readonly string $ipAddress,
        public readonly DateTimeImmutable $signupDate,
        public readonly Gender $gender,
        #[Define(Type::FIRST_NAME)]
        public readonly string $firstName,
        #[Define(new Sensitive())]
        public readonly string $password,
        #[SensitiveParameter]
        public readonly ?string $address = null,
        #[Pattern('/^[a-zA-Z]{1,255}$/')]
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $zip = null,
        public readonly ?string $phone = null,
        public readonly ?string $leadId = null,
        public readonly ?string $birthday = null,
        public readonly ?DateTimeImmutable $dob = null,
        public readonly ?string $c1 = null,
        public readonly ?string $hid = null,
        public readonly ?string $carMake = null,
        public readonly ?string $carModel = null,
        public readonly ?int $carYear = null,
    ) {
    }
}
