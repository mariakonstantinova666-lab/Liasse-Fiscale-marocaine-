<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On crée ton compte utilisateur pour l'application
        User::create([
            'name' => 'Maria',
            'email' => 'mariakonstantinova666@gmail.com',
            'password' => Hash::make('maria123'), // Ton mot de passe sécurisé
        ]);
    }
}