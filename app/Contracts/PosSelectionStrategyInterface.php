<?php

namespace App\Contracts;

use App\DTOs\PosSelectionCriteria;
use App\DTOs\PosSelectionOutcome;

interface PosSelectionStrategyInterface
{
    public function select(PosSelectionCriteria $criteria): PosSelectionOutcome;
}
