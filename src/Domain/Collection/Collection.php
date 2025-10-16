<?php

declare(strict_types=1);

namespace Serendipity\Domain\Collection;

use Constructo\Type\Collection as Constructo;

/**
 * @template T
 * @extends Constructo<T>
 * @deprecated use `Constructo\Type\Collection` directly
 */
abstract class Collection extends Constructo
{
}
