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
            'phone_number' => ['required', 'unique:users', 'regex:/^09[0-9]{8}$/'],
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:doctor,accountant,secretary,storekeeper',
            'specialization_id' => 'nullable|exists:specializations,id',
            'percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        return response()->json(
            $this->service->createEmployee($data)
        );
    }
    public function employees()
    {
        return response()->json(
            $this->service->getEmployees()
        );
    }
    public function deleteUser($id)
    {
        return response()->json(
            $this->service->deleteUser($id)
        );
    }
    public function restoreUser($id)
    {
        return response()->json(
            $this->service->restoreUser($id)
        );
    }
    public function deletedUsers()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getDeletedUsers()
        ]);
    }
    public function updateDoctorInfo(Request $request, int $doctorId)
    {
        $request->validate([
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'specialization_id' => 'sometimes|exists:specializations,id',
        ]);

        return response()->json([
            'message' => 'Doctor updated successfully',
            'data' => $this->service->updateDoctorInfo(
                $doctorId,
                $request->all()
            )
        ]);
    }

}

