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
    }
}
