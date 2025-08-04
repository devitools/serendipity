<?php

declare(strict_types=1);


use Constructo\Support\Metadata\Schema\Field\Formatter\MergeFormatter;
use Constructo\Support\Metadata\Schema\Field\Formatter\PatternFormatter;

return [
    'specs' => [
        # Special setup
        'bail' => [],
        'sometimes' => [],

        # Requirements
        'required_if' => [
            'params' => [
                'field',
                'value',
            ],
        ],
        'required_unless' => [
            'params' => [
                'field',
                'value',
            ],
        ],
        'required_with' => [
            'params' => '...',
        ],
        'required_with_all' => [
            'params' => '...',
        ],
        'required_without' => [
            'params' => '...',
        ],
        'required_without_all' => [
            'params' => '...',
        ],
        'required' => [],
        'nullable' => [],
        'filled' => [],
        'present' => [],

        # Types
        'string' => ['kind' => 'type'],
        'integer' => ['kind' => 'type'],
        'numeric' => ['kind' => 'type'],
        'array' => ['kind' => 'type'],
        'boolean' => ['kind' => 'type'],
        'date' => ['kind' => 'type'],
        'json' => ['kind' => 'type'],
        'file' => ['kind' => 'type'],
        'image' => ['kind' => 'type'],
        'email' => ['kind' => 'type'],
        'url' => ['kind' => 'type'],
        'active_url' => ['kind' => 'type'],
        'uuid' => ['kind' => 'type'],
        'ip' => ['kind' => 'type'],
        'ipv4' => ['kind' => 'type'],
        'ipv6' => ['kind' => 'type'],
        'timezone' => ['kind' => 'type'],

        # Constraints
        ## Numbers constraints
        'min' => ['params' => ['min']],
        'max' => ['params' => ['max']],
        'between' => [
            'params' => [
                'min',
                'max',
            ],
        ],
        'digits' => ['params' => ['digits']],
        'digits_between' => [
            'params' => [
                'min',
                'max',
            ],
        ],
        ## Among fields constraints
        'gt' => ['params' => ['field']],
        'gte' => ['params' => ['field']],
        'lt' => ['params' => ['field']],
        'lte' => ['params' => ['field']],
        'same' => ['params' => ['field']],
        'different' => ['params' => ['field']],
        'confirmed' => [],
        'distinct' => [],
        'in_array' => ['params' => ['field']],
        ## String constraints
        'accepted' => [],
        'alpha' => [],
        'alpha_dash' => [],
        'alpha_num' => [],
        'starts_with' => [],
        ## Date constraints
        'after' => ['params' => ['date']],
        'after_or_equal' => ['params' => ['date']],
        'before' => ['params' => ['date']],
        'before_or_equal' => ['params' => ['date']],
        'date_equals' => ['params' => ['date']],
        'date_format' => ['params' => ['format']],
        ## Multiple types constraints
        'size' => ['params' => ['size']],
        ## File constraints
        'mimes' => ['params' => '...'],
        'mimetypes' => ['params' => '...'],
        'dimensions' => ['params' => '...'],

        # Database
        'unique' => [
            'params' => [
                'table',
                'column',
                'except',
                'id_column',
            ],
        ],
        'exists' => [
            'params' => [
                'table',
                'column',
            ],
        ],

        # Behaviors
        'in' => [
            'formatter' => MergeFormatter::class,
            'params' => ['items'],
        ],
        'not_in' => [
            'formatter' => MergeFormatter::class,
            'params' => ['items'],
        ],
        'regex' => [
            'formatter' => PatternFormatter::class,
            'params' => [
                'pattern',
                'parameters:optional',
            ],
        ],
        'not_regex' => [
            'formatter' => PatternFormatter::class,
            'params' => [
                'pattern',
                'parameters:optional',
            ],
        ],
    ],
    'types' => [
        'DateTime' => 'date',
        'DateTimeImmutable' => 'date',
        'DateTimeInterface' => 'date',
    ],
];
