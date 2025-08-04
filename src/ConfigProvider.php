<?php

declare(strict_types=1);

namespace Serendipity;

use Constructo\Contract\Reflect\SpecsFactory;
use Constructo\Contract\Reflect\TypesFactory;
use Serendipity\Hyperf\Command\GenerateRules;
use Serendipity\Hyperf\Support\HyperfSpecsFactory;
use Serendipity\Hyperf\Support\HyperfTypesFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                TypesFactory::class => HyperfTypesFactory::class,
                SpecsFactory::class => HyperfSpecsFactory::class,
            ],
            'commands' => [
                GenerateRules::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
