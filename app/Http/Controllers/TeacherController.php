<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Http\Requests\TeacherFormRequest;
use App\Http\Resources\TeacherDetailResource;
use App\Http\Resources\TeachersResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::all();
        return response(TeachersResource::collection($teachers->loadMissing('user')), 200);
    }

    public function store(TeacherFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'teacher'
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
                'address' => $request->address,
                'phone' => $request->phone
            ]);

            DB::commit();
            return response()->json([
                'data' => new TeacherDetailResource($teacher),
                'message' => "Successful Created Teacher"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error storing teacher: " . $e->getMessage());
            return response()->json(['message' => 'Error storing teacher', 'error' => $e->getMessage()], 422);
        }
    }

    public function show(Teacher $teacher)
    {
        return new TeacherDetailResource($teacher->load('user'));
    }

    public function update(TeacherFormRequest $request, Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            $teacher->user->update([
                'name' => $request->name,
                'email' => $request->email
            ]);

            $teacher->update([
                'nip' => $request->nip,
                'address' => $request->address,
                'phone' => $request->phone
            ]);

            DB::commit();
            return response()->json([
                'data' => new TeacherDetailResource($teacher),
                'message' => "Successful Updated Teacher"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating teacher: " . $e->getMessage());
            return response()->json(['message' => 'Error updating teacher', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            $teacher->user->delete();
            $teacher->delete();
            DB::commit();
            return response()->json(['message' => 'Teacher deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error deleting teacher: " . $e->getMessage());
            return response()->json(['message' => 'Error deleting teacher', 'error' => $e->getMessage()], 422);
        }
    }
}