<?php

namespace App\Http\Controllers;

use App\Models\Exchange_Rate;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(private ExchangeRateService $service)
    {
    }

    public function current()
    {
        $result = $this->service->getCurrentUsdToSypRateWithMeta();

        return response()->json([
            'data' => $result['rate'],
            'is_stale' => $result['is_stale'],
            'warning' => $result['warning'],
            'fetched_at' => $result['rate']->fetched_at,
        ]);
    }

    public function history(Request $request)
    {
        $data = $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($data['limit'] ?? 50);

        $rates = Exchange_Rate::where('base_currency', 'USD')
            ->where('target_currency', 'SYP')
            ->orderByDesc('fetched_at')
            ->limit($limit)
            ->get();

        return response()->json($rates);
    }

    public function refresh()
    {
        $result = $this->service->getCurrentUsdToSypRateWithMeta(true);

        return response()->json([
            'data' => $result['rate'],
            'is_stale' => $result['is_stale'],
            'warning' => $result['warning'],
            'fetched_at' => $result['rate']->fetched_at,
        ]);
    }
}
