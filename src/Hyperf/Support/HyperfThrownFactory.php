<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Support;

use Hyperf\Contract\ConfigInterface;
use Serendipity\Domain\Exception\Parser\DefaultThrownFactory;

use function Constructo\Cast\arrayify;

class HyperfThrownFactory extends DefaultThrownFactory
{
    public function __construct(private readonly ConfigInterface $config)
    {
        $classification = $this->config->get('exceptions.classification', []);
        parent::__construct(arrayify($classification));
    }
}
