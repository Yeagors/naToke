<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'login' => 'mitrofanov',
                'password' => 'maksim',
                'last_name' => 'Митрофанов',
                'first_name' => 'Максим',
                'middle_name' => 'Вадимович',
                'birth_date' => '2004-09-19',
            ],
            [
                'login' => 'sokolov',
                'password' => 'egor',
                'last_name' => 'Соколов',
                'first_name' => 'Егор',
                'middle_name' => 'Юрьевич',
                'birth_date' => '2004-11-23',
            ],
            [
                'login' => 'nazarov',
                'password' => 'ilya',
                'last_name' => 'Назаров',
                'first_name' => 'Илья',
                'middle_name' => 'Андреевич',
                'birth_date' => '1998-04-22',
            ],
        ];

        foreach ($admins as $data) {
            User::updateOrCreate(
                ['login' => $data['login']],
                [
                    'password' => Hash::make($data['password']),
                    'last_name' => $data['last_name'],
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'birth_date' => $data['birth_date'],
                    'role' => UserRole::Admin,
                    'balance' => 0,
                ]
            );
        }
    }
}
