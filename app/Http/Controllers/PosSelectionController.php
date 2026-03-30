<?php

namespace App\Http\Controllers;

use App\Contracts\PosSelectionStrategyInterface;
use App\DTOs\PosSelectionCriteria;
use App\Http\Requests\SelectPosRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class PosSelectionController
{
    public function __construct(
        private readonly PosSelectionStrategyInterface $selectionStrategy,
    ) {}

    public function __invoke(SelectPosRequest $request): JsonResponse
    {
        $outcome = $this->selectionStrategy->select(
            PosSelectionCriteria::fromArray($request->validated()),
        );

        return Response::success($outcome->toArray());
    }
}
