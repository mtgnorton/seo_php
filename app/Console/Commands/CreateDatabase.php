<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CreateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建数据库';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createDatabase(
            config('database.connections.mysql.host'),
            config('database.connections.mysql.port'),
            config('database.connections.mysql.database'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'));
    }


    public function createDatabase($host, $port, $database, $username, $password)
    {
        try {
            $link = mysqli_connect($host, $username, $password, null, $port);


        } catch (\Exception $e) {
            throw new \Exception("数据库信息错误,无法连接数据库,具体的错误如下:" . $e->getMessage());
        }

        mysqli_query($link, "CREATE DATABASE " . $database);

        mysqli_close($link);

    }
}
