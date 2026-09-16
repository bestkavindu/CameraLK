<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'sachin kavindu',
                'email' => 'sachin@gmail.com',
                'email_verified_at' => NULL,
                'password' => '$2y$12$16hwqL5avZD2.TSX.cKGguXdc2mZMleCfio9F7IFzyCaK3GJg.Rxi',
                'remember_token' => NULL,
                'created_at' => '2026-08-19 06:53:26',
                'updated_at' => '2026-08-19 06:53:26',
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'two_factor_confirmed_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified_at' => '2026-08-19 14:17:25',
                'password' => '$2y$12$.qO2MsbYhM2v/tosN5sBguwRYmtDz18m/YoM1ico/h6e6seAvTG9m',
                'remember_token' => 'NNfL7IzAqp',
                'created_at' => '2026-08-19 14:17:26',
                'updated_at' => '2026-08-19 14:17:26',
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'two_factor_confirmed_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'sachin kavindu',
                'email' => 'rmskavindu@gmail.com',
                'email_verified_at' => NULL,
                'password' => '$2y$12$16R7IeN6PR8SIakfc9eu4eTnojXEwy8rtpfR.LreQHxvSWAlwMyTy',
                'remember_token' => NULL,
                'created_at' => '2026-09-16 11:50:27',
                'updated_at' => '2026-09-16 11:50:27',
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'two_factor_confirmed_at' => NULL,
            ),
        ));
        
        
    }
}