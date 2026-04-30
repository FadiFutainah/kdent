<?php

namespace App\Services;

use App\Models\Exchange_Rate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    public function getCurrentUsdToSypRate(): Exchange_Rate
    {
        return $this->getCurrentUsdToSypRateWithMeta()['rate'];
    }

    public function getCurrentUsdToSypRateWithMeta(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget('exchange_rate:usd_syp');
        }

        return Cache::remember('exchange_rate:usd_syp', now()->addHours(3), function () {
            $apiRate = $this->fetchUsdToSypFromLiraScope();

            if ($apiRate !== null) {
                $freshRate = Exchange_Rate::create([
                    'base_currency' => 'USD',
                    'target_currency' => 'SYP',
                    'rate' => $apiRate,
                    'source' => 'lirascope',
                    'fetched_at' => now(),
                ]);

                return [
                    'rate' => $freshRate,
                    'is_stale' => false,
                    'warning' => null,
                ];
            }

            $lastStoredRate = Exchange_Rate::where('base_currency', 'USD')
                ->where('target_currency', 'SYP')
                ->orderByDesc('fetched_at')
                ->first();

            if ($lastStoredRate) {
                $fetchedAt = optional($lastStoredRate->fetched_at)->format('Y-m-d H:i:s');

                return [
                    'rate' => $lastStoredRate,
                    'is_stale' => true,
                    'warning' => 'تعذر جلب سعر جديد من API الخارجي، تم استخدام آخر سعر محفوظ بتاريخ ' . $fetchedAt,
                ];
            }

            throw new \Exception('تعذر جلب سعر الصرف من API ولا يوجد سعر محفوظ مسبقا');
        });
    }

    private function fetchUsdToSypFromLiraScope(): ?float
    {
        $baseUrl = rtrim((string) config('services.lirascope.base_url', 'https://lirascope.syria-cloud.sy'), '/');
        $timeout = (int) config('services.lirascope.timeout', 10);

        $candidateUrls = [
            $baseUrl . '/api/v1/rates/latest',
            $baseUrl . '/api/v1/rates/usd-based',
            $baseUrl . '/api/v1/rates/latest?currencies=USD',
            $baseUrl,
        ];

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::timeout($timeout)->acceptJson()->get($url);
                if (!$response->successful()) {
                    continue;
                }

                $payload = $response->json();
                if (is_null($payload)) {
                    continue;
                }

                $rate = $this->extractRate($payload);
                if (!is_null($rate)) {
                    return $rate;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function extractRate(mixed $payload): ?float
    {
        $latestRate = $this->extractUsdRateFromKnownPayload($payload);
        if (!is_null($latestRate)) {
            return $latestRate;
        }

        if (is_numeric($payload)) {
            $number = (float) $payload;
            if ($this->isPlausibleRate($number)) {
                return $number;
            }
            return null;
        }

        if (!is_array($payload)) {
            return null;
        }

        $preferredKeys = [
            'usd_to_syp',
            'usd_syp',
            'usdToSyp',
            'usd',
            'price',
            'rate',
            'sell',
            'buy',
        ];

        foreach ($preferredKeys as $key) {
            if (array_key_exists($key, $payload) && is_numeric($payload[$key])) {
                $number = (float) $payload[$key];
                if ($this->isPlausibleRate($number)) {
                    return $number;
                }
            }
        }

        foreach ($payload as $value) {
            $nested = $this->extractRate($value);
            if (!is_null($nested)) {
                return $nested;
            }
        }

        return null;
    }

    private function extractUsdRateFromKnownPayload(mixed $payload): ?float
    {
        if (!is_array($payload)) {
            return null;
        }

        $sources = ['marketRates', 'cbsRates', 'effectiveRates'];

        foreach ($sources as $sourceKey) {
            if (!array_key_exists($sourceKey, $payload) || !is_array($payload[$sourceKey])) {
                continue;
            }

            foreach ($payload[$sourceKey] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $currency = strtoupper((string) ($row['currency'] ?? ''));
                if ($currency !== 'USD') {
                    continue;
                }

                foreach (['mid', 'sell', 'buy', 'rate'] as $priceKey) {
                    if (array_key_exists($priceKey, $row) && is_numeric($row[$priceKey])) {
                        $value = (float) $row[$priceKey];
                        if ($this->isPlausibleRate($value)) {
                            return $value;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function isPlausibleRate(float $rate): bool
    {
        return $rate > 100 && $rate < 1000000;
    }
}
