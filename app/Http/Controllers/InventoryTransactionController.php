<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InventoryTransactionService;

class InventoryTransactionController extends Controller
{
    private InventoryTransactionService $service;

    public function __construct(InventoryTransactionService $service)
    {
        $this->service = $service;
    }

    // public function purchase(Request $request)
    // {
    //     $data = $request->validate([
    //         'item_id' => 'required|exists:items,id',
    //         'supplier_id' => 'required|exists:suppliers,id',
    //         'quantity' => 'required|integer|min:1',
    //         'purchase_price' => 'required|numeric|min:0',
    //         'notes' => 'nullable|string',
    //     ]);

    //     $transaction = $this->service->purchase($data);

    //     return response()->json([
    //         'message' => 'Purchase completed successfully',
    //         'data' => $transaction
    //     ]);
    // }
    public function purchase(Request $request)
{
    $data = $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.purchase_price' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
    ]);

    $transaction = $this->service->purchaseBulk($data);

    return response()->json([
        'message' => 'Purchase completed successfully',
        'data' => $transaction
    ]);
}
//صرف مواد لجلسة محددة 
public function consume(Request $request)
{
    $data = $request->validate([
        'treatment_session_id' => 'required|exists:treatment_sessions,id',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string',
    ]);

    $transactions = $this->service->consumeMultiple($data);

    return response()->json([
        'message' => 'Items consumed successfully',
        'data' => $transactions
    ]);
}

}