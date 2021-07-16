<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        $this->call(MenuTableSeeder::class);
        // $this->call(TagTableSeeder::class);
        $this->call(ConfigsTableSeeder::class);
        $this->call(TemplateTypesTableSeeder::class);

        $this->update();
    }


    public function update()
    {

        DB::table('admin_menu')->where('title', 'Dashboard')->update([
            'title' => '系统首页',
            'icon'  => '/asset/imgs/icon/1.png',
            'icon_selected'  => '/asset/imgs/icon_default/a9.png',
        ]);
        DB::table('admin_menu')->whereIn('title', [
            'Admin', 'Users', 'Roles', 'Permission', 'Menu', 'Operation log'
        ])->delete();
    }
}
