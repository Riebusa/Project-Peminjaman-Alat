<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ilham Mamduh Al Ghifari',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('pasword123'),
                'role' => 'admin',
                'no_hp' => '081234567890',
                'alamat' => 'Baleendah, Bandung',
            ],
            [
                'name' => 'Dimas Raditya Putranto',
                'email' => 'petugas@gmail.com',
                'password' => Hash::make('pasword123'),
                'role' => 'petugas',
                'no_hp' => '082345678901',
                'alamat' => 'Ciparay, Bandung',
            ],
            [
                'name' => 'Mindo Fanbregas Sitorus',
                'email' => 'mindo@gmail.com',
                'password' => Hash::make('pasword123'),
                'role' => 'peminjam',
                'no_hp' => '083456789012',
                'alamat' => 'Ciparay, Bandung',
            ],
            [
                'name' => 'Rasya Pradana Putra',
                'email' => 'pradana@gmail.com',
                'password' => Hash::make('pasword123'),
                'role' => 'peminjam',
                'no_hp' => '084567890123',
                'alamat' => 'Cikawung, Bandung',
            ],
            [
                'name' => 'Richy Apriliano',
                'email' => 'richy@gmail.com',
                'password' => Hash::make('pasword123'),
                'role' => 'peminjam',
                'no_hp' => '085678901234',
                'alamat' => 'Ciparay, Bandung',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
