<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\CoursesResource;
use App\Http\Requests\CourseFormRequest;
use App\Http\Resources\CourseDetailResource;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('teacher')->get();
        return response(CoursesResource::collection($courses->loadMissing('teacher', 'assignments', 'schoolClasses')), 200);
    }

    public function store(CourseFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['slug'] = Str::slug(strtolower($request->name . '-' . uniqid()));
            $course = Course::create($data);
            DB::commit();
            return response()->json(new CourseDetailResource($course), 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error storing course: " . $e->getMessage());
            return response()->json(['message' => 'Error storing course', 'error' => $e->getMessage()], 422);
        }
    }

    public function show(Course $course)
    {
        return new CourseDetailResource($course->load('teacher', 'assignments', 'schoolClasses'));
    }

    public function update(CourseFormRequest $request, Course $course)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['slug'] = Str::slug(strtolower($request->name . '-' . uniqid()));
            $course->update($data);
            DB::commit();
            return response()->json(new CourseDetailResource($course), 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating course: " . $e->getMessage());
            return response()->json(['message' => 'Error updating course', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Course $course)
    {
        DB::beginTransaction();
        try {
            $course->delete();
            DB::commit();
            return response()->json(['message' => 'Course deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error deleting course: " . $e->getMessage());
            return response()->json(['message' => 'Error deleting course', 'error' => $e->getMessage()], 422);
        }
    }
    public function getCoursesByTeacher()
    {
        $teacher = Auth::user()->teacher;
        $courses = Course::where('teacher_id', $teacher->id)->with('assignments')->get();

        return response()->json([
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->user->name,
                'nip' => $teacher->nip
            ],
            'courses' => $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'slug' => $course->slug,
                    'assignments' => $course->assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'title' => $assignment->title,
                            'slug' => $assignment->slug,
                        ];
                    })
                ];
            })
        ]);
    }
}
