<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SocieteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupère le premier utilisateur (Maria) pour lui attribuer la société
        $user = DB::table('users')->first();

        if ($user) {
            DB::table('societes')->insert([
                'user_id'     => $user->id,
                'nom_societe' => 'Alpha Digital SARL',
                'if'          => '12345678',
                'ice'         => '001234567000089',
                'rc'          => '99999',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}