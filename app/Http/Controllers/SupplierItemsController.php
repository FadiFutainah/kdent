<?php

namespace App\Http\Controllers;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierItemsController extends Controller
{
      protected $service;
  public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }
//تثبيت موردين في النظام
//     public function store(Request $request)
//     {
//       $data = $request->validate([
//     'name' => 'required|string',
//     'phone' => 'nullable|string',

//     'items' => 'nullable|array',
//     'items.*.name' => 'required|string',
//     'items.*.unit' => 'nullable|string',
// ]);

//         $supplier = $this->service->createSupplierWithItems($data);

//         return response()->json([
//             'message' => 'Supplier created successfully',
//             'data' => $supplier
//         ], 201);
//     }
 public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'notes' => 'nullable|string',

            'items' => 'required|array',
            'items.*' => 'exists:items,id',
        ]);

        $supplier = $this->service->createSupplierWithItems($data);

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ]);
    }
//تثبيت المواد في النظام
    //  public function stores(Request $request)
    // {
    //     $data = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'code' => 'required|string|max:50|unique:items,code',
    //         'unit' => 'required|string|max:50',
    //         'minimum_stock' => 'required|integer|min:0',
    //     ]);

    //     $item = $this->service->createItem($data);

    //     return response()->json([
    //         'message' => 'Item created successfully',
    //         'data' => $item
    //     ]);
    // }
    //تثبيت مواد كدفعة وحدة
    public function stores(Request $request)
{
    $data = $request->validate([
        'items' => 'required|array',
        'items.*.name' => 'required|string|max:255',
        'items.*.code' => 'required|string|max:50|unique:items,code',
        'items.*.unit' => 'required|string|max:50',
        'items.*.minimum_stock' => 'required|integer|min:0',
    ]);

    $items = $this->service->createBulkItems($data['items']);

    return response()->json([
        'message' => 'Items created successfully',
        'data' => $items
    ]);
}
}
