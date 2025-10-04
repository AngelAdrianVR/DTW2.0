<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ángel Vázquez',
                'email' => 'angelvazquez470@gmail.com',
                'password' => Hash::make('321321321'),
            ],
            [
                'name' => 'Miguel Vázquez',
                'email' => 'miguelvz26.mv@gmail.com',
                'password' => Hash::make('321321321'),
            ],
            [
                'name' => 'Veronica Rodríguez',
                'email' => 'vero.rg123@gmail.com',
                'password' => Hash::make('321321321'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
