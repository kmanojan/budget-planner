<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Supported currencies with their details.
     */
    public const CURRENCIES = [
        'LKR' => ['name' => 'Sri Lankan Rupee',    'symbol' => 'Rs'],
        'USD' => ['name' => 'US Dollar',            'symbol' => '$'],
        'EUR' => ['name' => 'Euro',                 'symbol' => '€'],
        'GBP' => ['name' => 'British Pound',        'symbol' => '£'],
        'INR' => ['name' => 'Indian Rupee',         'symbol' => '₹'],
        'AUD' => ['name' => 'Australian Dollar',    'symbol' => 'A$'],
        'CAD' => ['name' => 'Canadian Dollar',      'symbol' => 'C$'],
        'JPY' => ['name' => 'Japanese Yen',         'symbol' => '¥'],
        'CNY' => ['name' => 'Chinese Yuan',         'symbol' => '¥'],
        'SGD' => ['name' => 'Singapore Dollar',     'symbol' => 'S$'],
        'AED' => ['name' => 'UAE Dirham',           'symbol' => 'د.إ'],
        'SAR' => ['name' => 'Saudi Riyal',          'symbol' => '﷼'],
        'MYR' => ['name' => 'Malaysian Ringgit',    'symbol' => 'RM'],
        'THB' => ['name' => 'Thai Baht',            'symbol' => '฿'],
        'KRW' => ['name' => 'South Korean Won',     'symbol' => '₩'],
        'CHF' => ['name' => 'Swiss Franc',          'symbol' => 'CHF'],
        'NZD' => ['name' => 'New Zealand Dollar',   'symbol' => 'NZ$'],
        'ZAR' => ['name' => 'South African Rand',   'symbol' => 'R'],
        'BDT' => ['name' => 'Bangladeshi Taka',     'symbol' => '৳'],
        'PKR' => ['name' => 'Pakistani Rupee',      'symbol' => '₨'],
    ];

    /**
     * Get the list of supported currencies.
     */
    public function getSupportedCurrencies(): array
    {
        $list = [];
        foreach (self::CURRENCIES as $code => $info) {
            $list[] = [
                'code'   => $code,
                'name'   => $info['name'],
                'symbol' => $info['symbol'],
            ];
        }
        return $list;
    }

    /**
     * Convert an amount from one currency to another.
     */
    public function convert(float $amount, string $from, string $to): ?array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return [
                'from'           => $from,
                'to'             => $to,
                'amount'         => $amount,
                'converted'      => round($amount, 2),
                'rate'           => 1.0,
                'last_updated'   => now()->toIso8601String(),
            ];
        }

        $rate = $this->getRate($from, $to);

        if ($rate === null) {
            return null;
        }

        return [
            'from'           => $from,
            'to'             => $to,
            'amount'         => $amount,
            'converted'      => round($amount * $rate, 2),
            'rate'           => $rate,
            'last_updated'   => now()->toIso8601String(),
        ];
    }

    /**
     * Get exchange rate from cache or API.
     * Uses exchangerate-api.com free tier (no key needed).
     */
    public function getRate(string $from, string $to): ?float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, 3600, function () use ($from, $to) {
            try {
                // Try primary API: exchangerate-api.com (free, no key)
                $response = Http::timeout(10)
                    ->get("https://open.er-api.com/v6/latest/{$from}");

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates'][$to])) {
                        return (float) $data['rates'][$to];
                    }
                }

                // Fallback: try frankfurter.app (free, no key)
                $response = Http::timeout(10)
                    ->get("https://api.frankfurter.app/latest", [
                        'from' => $from,
                        'to'   => $to,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['rates'][$to])) {
                        return (float) $data['rates'][$to];
                    }
                }

                Log::warning("Could not fetch exchange rate for {$from} -> {$to}");
                return null;
            } catch (\Exception $e) {
                Log::error("Exchange rate API error: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get all rates for a base currency.
     */
    public function getAllRates(string $baseCurrency): ?array
    {
        $baseCurrency = strtoupper($baseCurrency);
        $cacheKey = "exchange_rates_all_{$baseCurrency}";

        return Cache::remember($cacheKey, 3600, function () use ($baseCurrency) {
            try {
                $response = Http::timeout(10)
                    ->get("https://open.er-api.com/v6/latest/{$baseCurrency}");

                if ($response->successful()) {
                    $data = $response->json();
                    $allRates = $data['rates'] ?? [];

                    // Filter to only supported currencies
                    $filtered = [];
                    foreach (self::CURRENCIES as $code => $info) {
                        if (isset($allRates[$code])) {
                            $filtered[$code] = [
                                'rate'   => (float) $allRates[$code],
                                'name'   => $info['name'],
                                'symbol' => $info['symbol'],
                            ];
                        }
                    }

                    return $filtered;
                }

                return null;
            } catch (\Exception $e) {
                Log::error("Exchange rate API error: " . $e->getMessage());
                return null;
            }
        });
    }
}
