<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface PosRateProviderInterface
{
    /**
     * Fetch POS rates from the external source.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchRates(): Collection;
}
