<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $groups = UserGroup::withCount('users')->get();
        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'app_access' => 'nullable|array',
            'skpd_access' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $group = UserGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User group created successfully',
            'data' => $group
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserGroup $userGroup)
    {
        return response()->json([
            'success' => true,
            'data' => $userGroup
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserGroup $userGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'app_access' => 'nullable|array',
            'skpd_access' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $userGroup->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User group updated successfully',
            'data' => $userGroup
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserGroup $userGroup)
    {
        // Check if group has users
        if ($userGroup->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete group that has users. Please reassign users first.'
            ], 422);
        }

        $userGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'User group deleted successfully'
        ]);
    }
}
