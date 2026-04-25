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

    public function purchase(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',

            'items' => 'required|array|min:1',
            'items.*.supplier_item_id' => 'required|exists:supplier_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',

            'issued_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $invoice = $this->service->purchaseBulk($data);

        return response()->json([
            'message' => 'Purchase & Invoice created successfully',
            'data' => $invoice
        ]);
    }
    //شراء مواد
// public function purchase(Request $request)
// {
//     $data = $request->validate([
//         'supplier_id' => 'required|exists:suppliers,id',

//         'discount' => 'nullable|numeric|min:0',
//         'currency' => 'nullable|string',
//         'exchange_rate' => 'nullable|numeric',

//         'notes' => 'nullable|string',

//         'items' => 'required|array|min:1',
//         'items.*.item_id' => 'required|exists:items,id',
//         'items.*.quantity' => 'required|integer|min:1',
//         'items.*.purchase_price' => 'required|numeric|min:0',
//         'issued_at' => 'nullable|date',
//     ]);

//     $invoice = $this->service->purchaseBulk($data);

//     return response()->json([
//         'message' => 'Purchase & Invoice created successfully',
//         'data' => $invoice
//     ]);
// }
//صرف مواد  
public function consume(Request $request)
{
    $data = $request->validate([
       // 'treatment_session_id' => 'required|exists:treatment_sessions,id',
        'doctor_id' => 'required|exists:doctors,id',
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
//إرجاع مواد
public function returnItems(Request $request)
{
    $data = $request->validate([
        'doctor_id' => 'required|exists:doctors,id',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string',
    ]);

    $result = $this->service->returnItems($data);

    return response()->json([
        'message' => 'Items returned successfully',
        'data' => $result
    ]);
}

}