<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\StudentsResource;
use App\Http\Requests\StudentFormRequest;
use App\Http\Resources\StudentDetailResource;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::all();
        return response(StudentsResource::collection($students->loadMissing('user')), 200);
    }

    public function store(StudentFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student'
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'nis' => $request->nis,
                'address' => $request->address,
                'phone' => $request->phone
            ]);

            DB::commit();
            return response()->json([
                'data' => new StudentDetailResource($student),
                'message' => "Successful Created Student"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error storing student: " . $e->getMessage());
            return response()->json(['message' => 'Error storing student', 'error' => $e->getMessage()], 422);
        }
    }

    public function show(Student $student)
    {
        return new StudentDetailResource($student->load('user'));
    }

    public function update(StudentFormRequest $request, Student $student)
    {
        DB::beginTransaction();
        try {
            $student->user->update([
                'name' => $request->name,
                'email' => $request->email
            ]);

            $student->update([
                'nis' => $request->nis,
                'address' => $request->address,
                'phone' => $request->phone
            ]);

            DB::commit();
            return response()->json([
                'data' => new StudentDetailResource($student),
                'message' => "Successful Updated Student"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating student: " . $e->getMessage());
            return response()->json(['message' => 'Error updating student', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Student $student)
    {
        DB::beginTransaction();
        try {
            $student->user->delete();
            $student->delete();
            DB::commit();
            return response()->json(['message' => 'Student deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error deleting student: " . $e->getMessage());
            return response()->json(['message' => 'Error deleting student', 'error' => $e->getMessage()], 422);
        }
    }
}