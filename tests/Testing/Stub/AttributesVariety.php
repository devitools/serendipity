<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Constructo\Support\Reflective\Attribute\Define;
use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Definition\Type;
use DateTimeImmutable;
use SensitiveParameter;
use Serendipity\Domain\Support\Reflective\Attribute\Pattern;
use Serendipity\Test\Testing\Stub\Type\Sensitive;

readonly class AttributesVariety
{
    public function __construct(
        #[Managed('id')]
        public int $id,
        #[Managed('timestamp')]
        public DateTimeImmutable $createdAt,
        #[Define(Type::EMAIL)]
        public string $email,
        #[Pattern('/^[A-Z0-9]+$/')]
        public string $code,
        #[Pattern('/^\d+(\.\d{1,2})?$/')]
        public float $amount,
        #[Pattern('/^\d{2}$/')]
        public int $precision,
        #[Pattern('/^\d+$/')]
        public int|string $variant,
        #[SensitiveParameter]
        public string $cpf,
        #[Define(new Sensitive())]
        public string $sensitive,
        public mixed $noAttribute,
        $notTyped,
    ) {
    }
}
