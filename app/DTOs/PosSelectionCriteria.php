<?php

namespace App\DTOs;

use App\Enums\CardType;
use App\Enums\Currency;

final readonly class PosSelectionCriteria
{
    public function __construct(
        public float $amount,
        public int $installment,
        public Currency $currency,
        public CardType $cardType,
        public ?string $cardBrand = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            installment: (int) $data['installment'],
            currency: Currency::from($data['currency']),
            cardType: CardType::from($data['card_type']),
            cardBrand: $data['card_brand'] ?? null,
        );
    }
}
