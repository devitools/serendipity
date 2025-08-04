<?php

declare(strict_types=1);

namespace Serendipity\Domain\Collection;

use Constructo\Type\Collection as Constructo;
use Constructo\Type\Collection\AbstractCollection;

/**
 * @template T
 * @extends AbstractCollection<T>
 */
abstract class Collection extends Constructo
{
}
