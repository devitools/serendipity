<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Support;

use Constructo\Factory\DefaultTypesFactory;
use Hyperf\Contract\ConfigInterface;

use function Constructo\Cast\arrayify;

readonly class HyperfTypesFactory extends DefaultTypesFactory
{
    public function __construct(ConfigInterface $config)
    {
        $types = $config->get('schema.types', []);
        parent::__construct(arrayify($types));
    }
}
