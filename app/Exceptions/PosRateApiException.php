<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PosRateApiException extends HttpException
{
    public function __construct(string $reason = '', ?\Throwable $previous = null)
    {
        $message = ! empty($reason)
            ? __('pos.pos_rate_api_error_with_reason', ['reason' => $reason])
            : __('pos.pos_rate_api_error');

        parent::__construct(503, $message, $previous);
    }
}
