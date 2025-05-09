<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\SchoolClassResource;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;

class SchoolClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::all();
        return response(SchoolClassResource::collection($classes->loadMissing('teacher', 'students')), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:school_classes,code',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        DB::beginTransaction();
        try {
            $schoolClass = SchoolClass::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']), // generate slug otomatis
                'code' => $validated['code'],
                'teacher_id' => $validated['teacher_id'] ?? null,
            ]);

            DB::commit();
            return response()->json([
                'data' => new SchoolClassResource($schoolClass),
                'message' => "Successful Created School Class"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error storing school class: " . $e->getMessage());
            return response()->json(['message' => 'Error storing school class', 'error' => $e->getMessage()], 422);
        }
    }

    public function show(SchoolClass $schoolClass)
    {
        return new SchoolClassResource($schoolClass->load('teacher'));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:school_classes,code,' . $schoolClass->id,
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        DB::beginTransaction();
        try {
            $schoolClass->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']), // update slug juga otomatis
                'code' => $validated['code'],
                'teacher_id' => $validated['teacher_id'] ?? null,
            ]);

            DB::commit();
            return response()->json([
                'data' => new SchoolClassResource($schoolClass),
                'message' => "Successful Updated School Class"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating school class: " . $e->getMessage());
            return response()->json(['message' => 'Error updating school class', 'error' => $e->getMessage()], 422);
        }
    }

    public function destroy(SchoolClass $schoolClass)
    {
        DB::beginTransaction();
        try {
            $schoolClass->delete();

            DB::commit();
            return response()->json(['message' => 'School Class deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error deleting school class: " . $e->getMessage());
            return response()->json(['message' => 'Error deleting school class', 'error' => $e->getMessage()], 422);
        }
    }

    public function getClasses(Request $request)
    {
        $user = Auth::user();
        if ($user->role == 'teacher') {
            $teacher = $user->teacher;
            if ($teacher->type == 'classroomTeacher') {
                $classes = $teacher->schoolClasses()->with('courses.teacher.user', 'students.user', 'teacher.user')->get();

                $result = $classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'code' => $class->code,
                        'slug' => $class->slug,
                        'teacher' => $class->teacher ? [
                            'id' => $class->teacher->id,
                            'name' => $class->teacher->user->name,
                            'nip' => $class->teacher->nip,
                        ] : null,
                        'students' => $class->students->map(function ($student) {
                            return [
                                'id' => $student->id,
                                'name' => $student->user->name,
                                'nis' => $student->nis,
                            ];
                        }),
                        'courses' => $class->courses->map(function ($course) {
                            return [
                                'id' => $course->id,
                                'name' => $course->name,
                                'teacher_id' => $course->teacher ? $course->teacher->id : null,
                                'teacher' => $course->teacher ? $course->teacher->user->name : null,
                            ];
                        }),
                    ];
                });

                return response()->json(['data' => $result]);
            } else {
                return response()->json(['data' => []]);
            }
        }

        // Jika user login sebagai siswa
        elseif ($user->role == 'student') {
            $student = $user->student;
            $slug = $request->query('slug');

            if ($slug) {
                $class = SchoolClass::with('courses.teacher.user', 'students.user', 'teacher.user')
                    ->where('slug', $slug)
                    ->first();

                if (!$class || !$class->students->contains($student->id)) {
                    return response()->json(['message' => 'Class not found or access denied'], 403);
                }

                $result = [
                    'id' => $class->id,
                    'name' => $class->name,
                    'code' => $class->code,
                    'slug' => $class->slug,
                    'teacher' => $class->teacher ? [
                        'id' => $class->teacher->id,
                        'name' => $class->teacher->user->name,
                        'nip' => $class->teacher->nip,
                    ] : null,
                    'students' => $class->students->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'name' => $student->user->name,
                            'nis' => $student->nis,
                        ];
                    }),
                    'courses' => $class->courses->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'name' => $course->name,
                            'teacher' => $course->teacher ? $course->teacher->user->name : null,
                        ];
                    }),
                ];

                return response()->json(['data' => $result]);
            } else {
                $classes = $student->schoolClasses()->with('courses.teacher.user', 'students.user', 'teacher.user')->get();

                $result = $classes->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'slug' => $class->slug,
                        'teacher' => $class->teacher ? [
                            'id' => $class->teacher->id,
                            'name' => $class->teacher->user->name,
                            'nip' => $class->teacher->nip,
                        ] : null,
                        'courses' => $class->courses->map(function ($course) {
                            return [
                                'id' => $course->id,
                                'name' => $course->name,
                                'teacher' => $course->teacher ? $course->teacher->user->name : null,
                            ];
                        }),
                    ];
                });

                return response()->json(['data' => $result]);
            }
        }

        return response()->json([
            'message' => 'Unauthorized',
        ], 403);
    }

    public function getDataPloting()
    {
        $studentsNotPloted = Student::whereDoesntHave('schoolClasses', null)->with('user')->get();
        $studentsAllSelect = Student::with('user')->get();
        $studentsAll = $studentsAllSelect->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
            ];
        });
        $teachersNotPloted = Teacher::where('type', 'classroomTeacher')
            ->whereDoesntHave('schoolClasses')
            ->with('user')
            ->get();
        $schoolClasses = SchoolClass::with(['students', 'courses'])->get();

        $students = $studentsNotPloted->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'nis' => $student->nis,
            ];
        });
        $teachers = $teachersNotPloted->map(function ($teacher) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->user->name,
                'nip' => $teacher->nip,
            ];
        });
        $classes = $schoolClasses->map(function ($class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'code' => $class->code,
                'teacher' => $class->teacher ? [
                    'id' => $class->teacher->id,
                    'name' => $class->teacher->user->name,
                    'nip' => $class->teacher->nip,
                ] : null,
                'students' => $class->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name,
                        'nis' => $student->nis,
                    ];
                }),
                'courses' => $class->courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'name' => $course->name,
                    ];
                }),
            ];
        });
        $course = Course::with('teacher')->get();
        $course = $course->map(function ($course) {
            return [
                'id' => $course->id,
                'name' => $course->name,
                'teacherName' => $course->teacher ? $course->teacher->user->name : null,
            ];
        });

        return response()->json([
            'studentsNotPloted' => $students,
            'studentsAll' => $studentsAll,
            'classroomTeacher' => $teachers,
            'schoolClasses' => $classes,
            'courses' => $course,
        ]);
    }

    public function deleteStudentFromClass(SchoolClass $schoolClass, Student $student)
    {
        DB::beginTransaction();
        try {
            // Cek apakah siswa ada di kelas ini
            if (!$student->schoolClasses()->where('school_class_id', $schoolClass->id)->exists()) {
                return response()->json(['message' => 'Student does not belong to this class'], 400);
            }

            // Hapus dari pivot
            $student->schoolClasses()->detach($schoolClass->id);

            DB::commit();
            return response()->json(['message' => 'Student removed from class successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error removing student from class: " . $e->getMessage());
            return response()->json([
                'message' => 'Error removing student from class',
                'error' => $e->getMessage()
            ], 422);
        }
    }


    public function addStudentClass(SchoolClass $schoolClass, Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        DB::beginTransaction();
        try {
            $student = Student::findOrFail($validated['student_id']);

            // Tambahkan relasi ke tabel pivot
            $student->schoolClasses()->attach($schoolClass->id);

            DB::commit();
            return response()->json(['message' => 'Student added to class successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error adding student to class: " . $e->getMessage());
            return response()->json(['message' => 'Error adding student to class', 'error' => $e->getMessage()], 422);
        }
    }


    public function addCourseToClass(SchoolClass $schoolClass, Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        // Cek apakah course sudah ada di kelas
        $alreadyExists = $schoolClass->courses()
            ->where('course_id', $validated['course_id'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'Mapel sudah terdaftar di kelas ini.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $schoolClass->courses()->attach($validated['course_id']);
            DB::commit();
            return response()->json(['message' => 'Course added to class successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error adding course to class: " . $e->getMessage());
            return response()->json([
                'message' => 'Error adding course to class',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteCourseFromClass(SchoolClass $schoolClass, Course $course)
    {
        DB::beginTransaction();
        try {
            $schoolClass->courses()->detach($course->id);
            DB::commit();
            return response()->json(['message' => 'Course removed from class successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error removing course from class: " . $e->getMessage());
            return response()->json(['message' => 'Error removing course from class', 'error' => $e->getMessage()], 422);
        }
    }
}
