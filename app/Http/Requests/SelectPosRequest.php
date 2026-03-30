<?php

namespace App\Http\Requests;

use App\Enums\CardType;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installment' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
            'card_type' => ['required', 'string', Rule::enum(CardType::class)],
            'card_brand' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
