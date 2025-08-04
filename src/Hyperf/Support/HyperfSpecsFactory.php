<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Support;

use Constructo\Factory\DefaultSpecsFactory;
use Hyperf\Contract\ConfigInterface;

readonly class HyperfSpecsFactory extends DefaultSpecsFactory
{
    public function __construct(ConfigInterface $config)
    {
        $specs = $config->get('schema.specs', []);
        parent::__construct($specs);
    }
}
