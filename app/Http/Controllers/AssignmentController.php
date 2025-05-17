<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Answer;
use App\Models\Course;
use App\Models\Option;
use App\Models\Result;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Question;
use App\Models\Assignment;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function getUserAssignments()
    {
        $user = Auth::user();

        if ($user->role === 'student') {
            $student = Student::where('user_id', $user->id)->first();
            if (!$student) {
                return response()->json(['error' => 'User tidak ditemukan sebagai student.'], 403);
            }

            $studentClassIds = DB::table('school_class_students')
                ->where('student_id', $student->id)
                ->pluck('school_class_id');

            $courseIds = DB::table('course_school_classes')
                ->whereIn('school_class_id', $studentClassIds)
                ->pluck('course_id');

            $assignments = Assignment::whereIn('course_id', $courseIds)
                ->with([
                    'results' => fn($query) => $query->where('student_id', $student->id),
                    'course.schoolClasses'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'description' => $assignment->description,
                        'slug' => $assignment->slug,
                        'created_date' => Carbon::parse($assignment->created_at)->toDateString(),
                        'created_at' => $assignment->created_at->format('Y-m-d H:i:s'),
                        'has_submitted' => $assignment->results->isNotEmpty(),
                        'total_score' => $assignment->results->first()->total_score ?? null,
                        'status' => $assignment->results->first()->status ?? 'not attempted',
                        'course_name' => $assignment->course->name,
                        'course_slug' => $assignment->course->slug,
                        'class_name' => $assignment->schoolClass->name
                    ];
                });

            return response()->json([
                'role' => 'student',
                'data' => $assignments
            ]);
        } elseif ($user->role === 'teacher') {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                return response()->json(['error' => 'User tidak ditemukan sebagai teacher.'], 403);
            }

            // Ambil semua courses yang dimiliki guru ini
            $courses = Course::with(['assignments', 'schoolClasses'])
                ->where('teacher_id', $teacher->id)
                ->get();

            // Format response per class
            $grouped = [];

            foreach ($courses as $course) {
                foreach ($course->schoolClasses as $class) {
                    $className = $class->name;

                    if (!isset($grouped[$className])) {
                        $grouped[$className] = [];
                    }

                    foreach ($course->assignments as $assignment) {
                        $grouped[$className][] = [
                            'id' => $assignment->id,
                            'title' => $assignment->title,
                            'description' => $assignment->description,
                            'slug' => $assignment->slug,
                            'created_at' => $assignment->created_at->format('Y-m-d H:i:s'),
                            'course_name' => $course->name,
                            'course_slug' => $course->slug,
                        ];
                    }
                }
            }

            $formatted = collect($grouped)->map(function ($assignments, $className) {
                return [
                    'class_name' => $className,
                    'assignments' => $assignments
                ];
            })->values();

            return response()->json([
                'role' => 'teacher',
                'data' => $formatted
            ]);
        }
        return response()->json(['error' => 'Role tidak dikenali.'], 403);
    }
    public function getAssignmentsByCourse(Course $course)
    {
        $user = Auth::user();

        if ($user->role === 'teacher') {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                return response()->json(['error' => 'Teacher tidak ditemukan'], 404);
            }

            // check teacher login mapel 
            if ($course->teacher_id !== $teacher->id) {
                return response()->json(['error' => 'Unauthorized akses course ini.'], 403);
            }

            // Ambil semua kelas yang terkait dengan course ini
            $classes = $course->schoolClasses()->get();

            $result = $classes->map(function ($class) use ($course) {
                return [
                    'class_name' => $class->name,
                    'assignments' => $course->assignments
                        ->sortByDesc('created_at')
                        ->map(function ($assignment) {
                            return [
                                'id' => $assignment->id,
                                'title' => $assignment->title,
                                'description' => $assignment->description,
                                'slug' => $assignment->slug,
                                'corrected' => $assignment->corrected,
                                'created_at' => $assignment->created_at->format('Y-m-d'),
                            ];
                        })->values(), // pastikan indeks rapi
                ];
            });
            return response()->json([
                'course' => [
                    'name' => $course->name,
                    'slug' => $course->slug,
                ],
                'class_assignments' => $result,
            ]);
        }
        // Untuk student, tampilkan semua assignment dari course ini
        elseif ($user->role === 'student') {
            $assignments = $course->assignments
                ->sortByDesc('created_at')
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'description' => $assignment->description,
                        'slug' => $assignment->slug,
                        'corrected' => $assignment->corrected,
                        'created_at' => $assignment->created_at->format('Y-m-d'),
                        'course_name' => $assignment->course->name,
                        'course_slug' => $assignment->course->slug,
                    ];
                })->values(); // pastikan indeks rapi

            return response()->json([
                'assignments' => $assignments
            ]);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }


    public function getDataCreate(Course $course)
    {
        $course->load('schoolClasses');

        $response = [
            'id' => $course->id,
            'name' => $course->name,
            'slug' => $course->slug,
            'classes' => $course->schoolClasses->map(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                ];
            }),
        ];

        return response()->json([
            'course' => $response,
        ]);
    }

    public function addQuestionsBatch(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'corrected' => 'required|string',
            'school_class_id' => 'required|exists:school_classes,id',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:multiple_choice,essay',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.correct_option' => 'required_if:questions.*.question_type,multiple_choice|required_if:corrected,system',
            'questions.*.point' => 'required|integer',
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'school_class_id' => $request->school_class_id,
            'description' => $request->description,
            'corrected' => $request->corrected
        ]);

        foreach ($request->questions as $q) {
            $question = Question::create([
                'assignment_id' => $assignment->id,
                'question_text' => $q['question_text'],
                'question_type' => $q['question_type'],
                'point' => $q['point'],
            ]);

            if ($q['question_type'] === 'multiple_choice') {
                $correctOptionText = $q['options'][$q['correct_option']] ?? null;
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => json_encode($q['options']), // simpan array pilihan sebagai JSON
                    'correct_option' => $correctOptionText, // Simpan jawaban yang benar
                ]);
            } else {
                if ($assignment->corrected === 'system') {
                    Option::create([
                        'question_id' => $question->id,
                        'correct_option' => (string) $q['correct_option'], // Simpan jawaban yang benar
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Assignment dan soal berhasil ditambahkan!',
            'assignment_id' => $assignment->id,
        ]);
    }

    public function showQuestions($slug)
    {
        try {
            $assignment = Assignment::where('slug', $slug)->with(['questions.options'])->firstOrFail();
            return response()->json([
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'corrected' => $assignment->corrected,
                    'questions' => $assignment->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'question_type' => $question->question_type,
                            'options' => $question->options->isNotEmpty()
                                ? json_decode($question->options->first()->option_text, true)
                                : [],
                            'correct_option' => $question->question_type === 'multiple_choice' ? optional($question->options->first())->correct_option : null,
                            'point' => $question->point,
                        ];
                    })
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitAssignment(Request $request, Assignment $assignment)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'answers' => 'required|array|min:1',
                'answers.*' => 'required|string'
            ]);
            // return response()->json(['data' => $request->all()]);
            $student = Student::where('user_id', Auth::id())->first();
            if (!$student) {
                return response()->json(['error' => 'User tidak ditemukan sebagai student.'], 403);
            }
            $studentId = $student->id;
            $answers = array_values($request->input('answers'));;
            $totalScore = 0;
            $totalPossibleScore = 0;
            $points = [];

            Answer::create([
                'student_id' => $studentId,
                'assignment_id' => $assignment->id,
                'student_answer' => json_encode($answers), // Simpan jawaban siswa dalam JSON
            ]);
            $questions = Question::where('assignment_id', $assignment->id)->with('options')->get();

            if ($assignment->corrected === 'system') {
                foreach ($questions as $index => $question) {
                    $totalPossibleScore += $question->point;
                    $userAnswer = $answers[$index] ?? null;
                    $pointEarned = 0;

                    if ($userAnswer !== null) {
                        $correctAnswerText = optional($question->options->first())->correct_option;

                        if ($question->question_type === 'multiple_choice' && $correctAnswerText === $userAnswer) {
                            $pointEarned = $question->point;
                        } elseif ($question->question_type === 'essay' && $correctAnswerText !== null) {
                            if (strtolower($correctAnswerText) === strtolower($userAnswer)) {
                                $pointEarned = $question->point;
                            }
                        }
                    }
                    $totalScore += $pointEarned;
                    $points[] = $pointEarned;
                }
                $status = 'completed';
            } else {
                $hasEssay = false;
                foreach ($questions as $index => $question) {
                    $totalPossibleScore += $question->point;
                    $userAnswer = $answers[$index] ?? null;
                    $pointEarned = 0;

                    if ($userAnswer !== null) {
                        if ($question->question_type === 'multiple_choice') {
                            $correctAnswerText = optional($question->options->first())->correct_option;
                            if ($correctAnswerText !== null && trim(strtolower($userAnswer)) === trim(strtolower($correctAnswerText))) {
                                $pointEarned = $question->point;
                            }
                        } elseif ($question->question_type === 'essay') {
                            $hasEssay = true;
                            $pointEarned = 0; // nilai diset 0 dulu, nanti dikoreksi guru
                        }
                    }
                    $totalScore += $pointEarned;
                    $points[] = $pointEarned;
                }
                $status = $hasEssay ? 'pending' : 'completed';
            }
            Result::create([
                'student_id' => $studentId,
                'assignment_id' => $assignment->id,
                'points' => json_encode($points),
                'total_score' => $totalScore,
                'status' => $status
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Jawaban berhasil dikirim!',
                'total_score' => $totalScore,
                'status' => $status
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Terjadi kesalahan saat menyimpan jawaban.', 'details' => $e->getMessage()], 500);
        }
    }

    public function viewAnswers(Assignment $assignment)
    {
        $assignment->load('schoolClass.students');
        $students = $assignment->schoolClass->students;

        $results = Result::where('assignment_id', $assignment->id)
            ->get()
            ->keyBy('student_id');

        // Gabungkan data siswa dan hasil pengerjaan
        $data = $students->map(function ($student) use ($results) {
            $result = $results->get($student->id);

            return [
                'nis' => $student->nis,
                'student_name' => $student->user->name,
                'status' => $result->status ?? 'not_submitted',
                'total_score' => $result->total_score ?? null,
            ];
        });

        return response()->json([
            'assignment' => [
                'title' => $assignment->title,
                'slug' => $assignment->slug,
            ],
            'students' => $data,
        ]);
    }

    public function viewStudentAnswers(Request $request, Assignment $assignment, $nis)
    {
        try {
            $student = Student::where('nis', $nis)->firstOrFail();
            $answer = Answer::where('student_id', $student->id)
                ->where('assignment_id', $assignment->id)
                ->first();

            if (!$answer) {
                return response()->json(['error' => 'Jawaban siswa tidak ditemukan.'], 404);
            }

            $result = Result::where('student_id', $student->id)
                ->where('assignment_id', $assignment->id)
                ->first();

            $studentAnswers = json_decode($answer->student_answer, true);
            $points = $result ? json_decode($result->points, true) : [];

            $assignment->load(['questions.options']);

            $data = [
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'corrected' => $assignment->corrected,
                    'questions' => $assignment->questions->map(function ($question, $index) use ($studentAnswers, $points) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'question_type' => $question->question_type,
                            'options' => $question->options->isNotEmpty()
                                ? json_decode($question->options->first()->option_text, true)
                                : [],
                            'correct_option' => $question->question_type === 'multiple_choice'
                                ? optional($question->options->first())->correct_option
                                : null,
                            'student_answer' => $studentAnswers[$index] ?? null,
                            'point' => $question->point,
                            'earned_point' => $points[$index] ?? null,
                        ];
                    }),
                ],
                'student' => [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'nis' => $student->nis,
                ]
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data jawaban siswa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function gradeAssignment(Request $request, Assignment $assignment, Answer $answer)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'points' => 'required|array|min:1',
                'points.*' => 'required|numeric'
            ]);

            $result = Result::where('student_id', $answer->student_id)
                ->where('assignment_id', $assignment->id)
                ->first();

            if (!$result) {
                return response()->json(['error' => 'Hasil tidak ditemukan.'], 404);
            }

            // Update points dan total_score
            $points = $request->points;
            $totalScore = array_sum($points);

            $result->update([
                'points' => json_encode($points),
                'total_score' => $totalScore,
                'status' => 'completed'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Jawaban berhasil dikoreksi!',
                'total_score' => $totalScore,
                'status' => 'completed'
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Terjadi kesalahan saat mengoreksi jawaban.', 'details' => $e->getMessage()], 500);
        }
    }

    public function viewSubmited(Assignment $assignment)
    {
        $student = Student::where('user_id', Auth::id())->first();

        $result = Result::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$result) {
            return response()->json(['message' => 'Belum mengumpulkan tugas ini'], 404);
        }

        return response()->json([
            'submitted' => true,
            'total_score' => $result->total_score,
            'status' => $result->status
        ]);
    }

    public function viewUserResult(Assignment $assignment)
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (!$student) {
            return response()->json(['error' => 'User tidak ditemukan sebagai student.'], 403);
        }
        $answer = Answer::where('student_id', $student->id)
            ->where('assignment_id', $assignment->id)
            ->first();

        if (!$answer) {
            return response()->json(['error' => 'Jawaban tidak ditemukan.'], 404);
        }

        // Ambil hasil koreksi
        $result = Result::where('student_id', $student->id)
            ->where('assignment_id', $assignment->id)
            ->first();

        if (!$result) {
            return response()->json(['error' => 'Hasil tidak ditemukan.'], 404);
        }

        // Ambil semua pertanyaan beserta opsi jawabannya
        $questions = Question::where('assignment_id', $assignment->id)
            ->with('options')
            ->get();

        return response()->json([
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
            ],
            // 'questions' => $questions,
            'questions' => $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'point' => $question->point,
                    'options' => $question->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'option_text' => $option->option_text,
                            'correct_option' => $option->correct_option ?? null,
                        ];
                    })
                ];
            }),
            'answer' => [
                'student_answer' => json_decode($answer->student_answer, true),
            ],
            'result' => [
                'points' => json_decode($result->points, true),
                'total_score' => $result->total_score,
                'status' => $result->status
            ]
        ]);
    }
}
