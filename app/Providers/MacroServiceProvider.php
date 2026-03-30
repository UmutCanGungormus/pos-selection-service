<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerResponseMacros();
        $this->registerHttpMacros();
    }

    private function registerResponseMacros(): void
    {
        Response::macro('success', function (mixed $data = null, ?string $message = null, int $status = 200): JsonResponse {
            $response = ['success' => true];

            if ($message !== null) {
                $response['message'] = $message;
            }

            if ($data !== null) {
                $response['data'] = $data;
            }

            return Response::json($response, $status);
        });

        Response::macro('error', function (string $message, int $status = 400, array $errors = []): JsonResponse {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if (! empty($errors)) {
                $response['errors'] = $errors;
            }

            return Response::json($response, $status);
        });

        Response::macro('created', function (mixed $data = null, ?string $message = null): JsonResponse {
            return Response::success($data, $message, 201);
        });
    }

    private function registerHttpMacros(): void
    {
        Http::macro('posRateApi', function () {
            return Http::timeout(10)
                ->retry(3, 500)
                ->acceptJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                ]);
        });
    }
}
