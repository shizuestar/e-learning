<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($user) {
            return [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'address' => $user->role === 'teacher' ? $user->teacher->address : ($user->role === 'student' ? $user->student->address : null),
                'phone' => $user->role === 'teacher' ? $user->teacher->phone : ($user->role === 'student' ? $user->student->phone : null),
                'nip' => $user->role === 'teacher' ? $user->teacher->nip : null,
                'nis' => $user->role === 'student' ? $user->student->nis : null,
                'type' => $user->role === 'teacher' ? $user->teacher->type : null,
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            ];
        })->all();
    }
}
