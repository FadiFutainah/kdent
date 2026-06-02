<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function show()
    {
        return response()->json([
            'success' => true,
            'data' => $this->profileService->getProfile()
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . auth()->id(),
            'phone_number' => 'sometimes|nullable|unique:users,phone_number,' . auth()->id(),
            'date_of_birth' => 'sometimes|nullable|date',

            'gender' => 'sometimes|nullable|in:male,female',
            'address' => 'sometimes|nullable|string|max:255',
            'occupation' => 'sometimes|nullable|string|max:255',
        ]);

        $user = $this->profileService->updateProfile(
            $request->all()
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }
}