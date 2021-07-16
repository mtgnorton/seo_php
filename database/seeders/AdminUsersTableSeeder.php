<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('admin_users')->delete();
        
        \DB::table('admin_users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'username' => 'admin',
                'password' => '$2y$10$1Zn6rOsPEeAb12Qs4sQqU.VngyQDiY.Ua8fzqz9nDlBGY4dAXHXJa',
                'name' => '111',
                'avatar' => NULL,
                'remember_token' => 'Er8444Hamxl0ma4gi2mMxT8lPNO8F9ZPJqtuE6i6fcJq4VPftBYKzW7p0xkQ',
                'created_at' => '2021-06-04 10:54:43',
                'updated_at' => '2021-07-08 11:47:42',
            ),
            1 => 
            array (
                'id' => 2,
                'username' => 'a_demo',
                'password' => '$2y$10$xH6U3MaP9Z4swmuQWaFkleZ0teubKgFTvSSEU38pblt3xbrCNw7EC',
                'name' => 'aa',
                'avatar' => NULL,
                'remember_token' => NULL,
                'created_at' => '2021-07-10 15:47:41',
                'updated_at' => '2021-07-10 15:47:41',
            ),
        ));
        
        
    }
}