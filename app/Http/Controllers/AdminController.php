<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminService;

class AdminController extends Controller
{
     protected $service;

    public function __construct(AdminService $service)
    {
        $this->service = $service;
    }

    public function createEmployee(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:doctor,accountant,secretary,storekeeper',
            'specialization_id' => 'nullable|exists:specializations,id',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        return response()->json(
            $this->service->createEmployee($data)
        );
    }
}

