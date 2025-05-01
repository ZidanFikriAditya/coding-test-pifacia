<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $role = \App\Models\Role::query()->where('slug', 'administrator')->first();

        if (!$role) {
            $role = \App\Models\Role::create([
                'name' => 'Administrator',
                'slug' => 'administrator',
                'guard_name' => 'web',
            ]);
        }

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'role_id' => $role->id,
        ]);
    }
}
