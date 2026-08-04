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
            $payload = [
                'nom_societe' => 'D3 Soft SARL AU',
                'if'          => '12345678',
                'ice'         => '001234567000089',
                'rc'          => '99999',
                'cnss'        => '8912345',
                'patente'     => '34567890',
                'adresse'     => 'Avenue de la Paix, Imm Chourouk Bloc B3, 1er étage, App 5, Tanger 90000',
                'updated_at'  => now(),
            ];

            $exists = DB::table('societes')->where('user_id', $user->id)->exists();

            if ($exists) {
                DB::table('societes')->where('user_id', $user->id)->update($payload);
            } else {
                DB::table('societes')->insert($payload + [
                    'user_id'    => $user->id,
                    'created_at' => now(),
                ]);
            }
        }
    }
}
