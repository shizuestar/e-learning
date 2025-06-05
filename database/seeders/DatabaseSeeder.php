<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@email.com',
                'password' => Hash::make('123'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'John Doe',
                'email' => 'teacher@email.com',
                'password' => Hash::make('123'),
                'role' => 'teacher',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'student@email.com',
                'password' => Hash::make('123'),
                'role' => 'student',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
        $teacherUser = DB::table('users')->where('email', 'teacher@email.com')->first();
        $studentUser = DB::table('users')->where('email', 'student@email.com')->first();
        DB::table('teachers')->insert([
            [
                'user_id' => $teacherUser->id,
                'nip' => '1234567890',
                'address' => 'Jl. Guru No.1',
                'phone' => '081234567890',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        DB::table('students')->insert([
            [
                'user_id' => $studentUser->id,
                'nis' => '9876543210',
                'address' => 'Jl. Pelajar No.2',
                'phone' => '089876543210',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);


        DB::table('courses')->insert([
            [
                'name' => 'Matematika',
                'slug' => 'matematikab1',
                'teacher_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);


        // Tambah guru baru
        $moreTeachers = [
            [
                'name' => 'Kalea Salsabila',
                'email' => 'kalea@email.com',
                'password' => Hash::make('123'),
                'role' => 'teacher',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Xionara Putri',
                'email' => 'putri@email.com',
                'password' => Hash::make('123'),
                'role' => 'teacher',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Xaviera Cahya',
                'email' => 'cahya@email.com',
                'password' => Hash::make('123'),
                'role' => 'teacher',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('users')->insert($moreTeachers);

        // Ambil semua user guru
        $teacherUsers = DB::table('users')->where('role', 'teacher')->get();

        // Buat entry pada tabel teachers
        foreach ($teacherUsers as $i => $teacherUser) {
            DB::table('teachers')->updateOrInsert(
                ['user_id' => $teacherUser->id],
                [
                    'nip' => '100000000' . $i,
                    'address' => 'Alamat Guru ' . ($i + 1),
                    'phone' => '08123456000' . $i,
                    'type' => 'classroomTeacher',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Ambil ID guru untuk dimasukkan sebagai wali kelas (teacher_id)
        $teacherList = DB::table('teachers')->get();

        // Tambah 3 kelas XI RPL A/B/C
        DB::table('school_classes')->insert([
            [
                'name' => 'XI RPL A',
                'slug' => 'xi-rpl-a',
                'code' => 'XIRPLA',
                'teacher_id' => $teacherList[0]->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'XI RPL B',
                'slug' => 'xi-rpl-b',
                'code' => 'XIRPLB',
                'teacher_id' => $teacherList[1]->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'XI RPL C',
                'slug' => 'xi-rpl-c',
                'code' => 'XIRPLC',
                'teacher_id' => $teacherList[2]->id ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // Ambil ulang teacher list (biar aman)
        $teacherList = DB::table('teachers')->get();

        // Buat 5 course (mapel)
        $courseData = [
            ['name' => 'Bahasa Indonesia', 'slug' => 'bahasa-indonesia'],
            ['name' => 'Bahasa Inggris', 'slug' => 'bahasa-inggris'],
            ['name' => 'Pendidikan Pancasila', 'slug' => 'pkn'],
            ['name' => 'Pemrograman Web', 'slug' => 'pemrograman-web'],
            ['name' => 'Basis Data', 'slug' => 'basis-data'],
        ];

        // Masukkan course dengan guru acak dari daftar
        foreach ($courseData as $index => $course) {
            DB::table('courses')->insert([
                'name' => $course['name'],
                'slug' => $course['slug'],
                'teacher_id' => $teacherList[$index % $teacherList->count()]->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $animeNames = [
            'Hinata Shoyo',
            'Kageyama Tobio',
            'Yamaguchi Tadashi',
            'Nishinoya Yuu',
            'Ushijima Wakatoshi',
            'Oikawa Tooru',
            'Iwaizumi Hajime',
            'Akaashi Keiji',
            'Bokuto Koutarou',
            'Aone Takanobu',
            'Kuroo Tetsurou',
            'Kenma Kozume',
            'Ryo Yamada',
            'Kirigaya Kazuto',
            'Asuna Yuuki',
            'Levi Ackerman',
            'Eren Yeager',
            'Mikasa Ackerman',
            'Armin Arlert',
            'Itadori Yuji'
        ];

        foreach ($animeNames as $i => $name) {
            $email = strtolower(str_replace(' ', '', $name)) . '@email.com';

            // Insert user
            $userId = DB::table('users')->insertGetId([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('123'),
                'role' => 'student',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Insert student
            DB::table('students')->insert([
                'user_id' => $userId,
                'nis' => '202500' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'address' => 'Jl. Anime No.' . ($i + 1),
                'phone' => '08990000' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
