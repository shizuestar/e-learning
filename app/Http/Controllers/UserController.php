<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserFormRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['student', 'teacher'])->get();
        return response(new UserCollection($users), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'role' => 'in:admin,student,teacher',
            'nis' => 'required_if:role,student|string|max:255',
            'nip' => 'required_if:role,teacher|string|max:255',
            'address' => 'required_if:role,student,teacher|string|max:255',
            'phone' => 'required_if:role,student,teacher|string|max:255',
            'type' => 'required_if:role,teacher'
        ]);
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'] ?? 'admin',
            ]);

            if($validated['role'] == 'student') {
                $user->student()->create([
                    'nis' => $validated['nis'],
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                ]);
            } elseif ($validated['role'] == 'teacher') {
                $user->teacher()->create([
                    'nip' => $validated['nip'],
                    'address' => $validated['address'],
                    'phone' => $validated['address'],
                    'type' => $validated['type']
                ]);
            }

            DB::commit();
            return response()->json([
                'data' => new UserResource($user),
                'message' => "Successful Created User"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error storing user: " . $e->getMessage());
            return response()->json(['message' => 'Error storing user', 'error' => $e->getMessage()], 422);
        }
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        return response()->json([
            'message' => $user
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:3',
            'role' => 'in:admin,student,teacher',
            'nis' => 'required_if:role,student|string|max:255',
            'nip' => 'required_if:role,teacher|string|max:255',
            'address' => 'required_if:role,student,teacher|string|max:255',
            'phone' => 'required_if:role,student,teacher|string|max:255',
            'type' => 'required_if:role,teacher|string'
        ]);
        DB::beginTransaction();
        try {
            $user->update([
                'name' => $validated['email'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);

            if ($validated['password'] || $validated['password'] != null) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }
            if($validated['role'] == 'student') {
                $user->student()->update([
                    'nis' => $validated['nis'],
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                ]);
            } elseif ($validated['role'] == 'teacher') {
                $user->teacher()->update([
                    'nip' => $validated['nip'],
                    'address' => $validated['address'],
                    'phone' => $validated['address'],
                    'type' => $validated['type']
                ]);
            }

            DB::commit();
            return response()->json([
                'data' => new UserResource($user),
                'message' => "Successful Updated User"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating user: " . $e->getMessage());
            return response()->json(['message' => 'Error updating user', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            $user->delete();
            if($user->student) {
                $user->student()->delete();
            } elseif ($user->teacher) {
                $user->teacher()->delete();
            }
            DB::commit();
            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error deleting user: " . $e->getMessage());
            return response()->json(['message' => 'Error deleting user', 'error' => $e->getMessage()], 422);
        }
    }
}