<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private CurrencyService $service) {}

    /**
     * API-CUR-01: Get supported currencies.
     */
    public function currencies(): JsonResponse
    {
        return $this->success($this->service->getSupportedCurrencies());
    }

    /**
     * API-CUR-02: Convert amount between currencies.
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'from'   => 'required|string|size:3',
            'to'     => 'required|string|size:3',
        ]);

        $result = $this->service->convert(
            (float) $request->amount,
            $request->from,
            $request->to
        );

        if ($result === null) {
            return $this->error('Could not fetch exchange rate. Please try again later.', 503);
        }

        return $this->success($result);
    }

    /**
     * API-CUR-03: Get all exchange rates for a base currency.
     */
    public function rates(Request $request): JsonResponse
    {
        $base = strtoupper($request->get('base', 'LKR'));

        $rates = $this->service->getAllRates($base);

        if ($rates === null) {
            return $this->error('Could not fetch exchange rates.', 503);
        }

        return $this->success([
            'base'  => $base,
            'rates' => $rates,
        ]);
    }
}
