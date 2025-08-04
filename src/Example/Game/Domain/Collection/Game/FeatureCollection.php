<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Domain\Collection\Game;

use Constructo\Type\Collection;
use Serendipity\Example\Game\Domain\Entity\Game\Feature;

/**
 * @extends Collection<Feature>
 */
class FeatureCollection extends Collection
{
    public function current(): Feature
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): Feature
    {
        return ($datum instanceof Feature)
            ? $datum
            : throw $this->exception(Feature::class, $datum);
    }
}
