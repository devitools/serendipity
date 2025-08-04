<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Hyperf\Validation\Rule;
use Serendipity\Presentation\Input;

use function Hyperf\Collection\data_get;
use function preg_replace;
use function Serendipity\Type\Cast\stringify;

/**
 * @see https://hyperf.wiki/3.1/#/en/validation?id=form-request-validation
 */
class ComplexInput extends Input
{
    public function rules(): array
    {
        return [
            # ## Required fields
            'quote_rxmg.ef_transaction_id' => [
                'required',
                'string',
            ],
            'quote_rxmg.internal_lead_id' => [
                'required',
                'string',
            ],
            'quote_rxmg.service' => [
                'required',
                Rule::in(
                    [
                        'sms',
                        'email',
                    ]
                ),
            ],
            'email' => [
                'required',
                'email',
            ],
            'ip_address' => [
                'required',
                'ip',
            ],
            'signup_date' => [
                'required',
                'regex:/\d{4}-\d{2}-\d{2}/',
            ],
            # ## Optional fields
            'signup_url' => [
                'sometimes',
                'string',
            ],
            'first_name' => [
                'sometimes',
                'string',
            ],
            'last_name' => [
                'sometimes',
                'string',
            ],
            'address' => [
                'sometimes',
                'string',
            ],
            'city' => [
                'sometimes',
                'string',
            ],
            'state' => [
                'sometimes',
                'regex:/[A-Z]{2}/',
            ],
            'zip' => [
                'sometimes',
                'numeric',
            ],
            'phone' => [
                'sometimes',
                'string',
            ],
            'lead_id' => [
                'sometimes',
                'string',
            ],
            'sex' => [
                'sometimes',
                Rule::in(
                    [
                        'm',
                        'f',
                    ]
                ),
            ],
            'birthday' => [
                'sometimes',
                'regex:/\d{2}-\d{2}/',
            ],
            'dob' => [
                'sometimes',
                'regex:/\d{4}-\d{2}-\d{2}/',
            ],
            'c1' => [
                'sometimes',
                'string',
            ],
            'hid' => [
                'sometimes',
                'string',
            ],
            'car_make' => [
                'sometimes',
                'string',
            ],
            'car_model' => [
                'sometimes',
                'string',
            ],
            'car_year' => [
                'sometimes',
                'regex:/\d{4}/',
            ],
        ];
    }

    /**
     * @return array<string, callable(array $data):mixed|string>
     */
    public function mappings(): array
    {
        return [
            # ## QuoteRxmgCommand fields
            'quote_rxmg.ef_transaction_id' => 'ef_transaction_id',
            'quote_rxmg.internal_lead_id' => 'internal_lead_id',
            'quote_rxmg.service' => 'service',
            # ## RxmgCommand fields
            'car_make' => 'cars.0.make',
            'car_model' => 'cars.0.model',
            'car_year' => 'cars.0.year',
            'phone' => fn (array $data) => preg_replace('/\D/', '', stringify(data_get($data, 'phone'))),
            'sex' => fn (array $data) => stringify(data_get($data, 'drivers.0.gender')) === 'Male'
                ? 'm'
                : 'f',
        ];
    }
}
