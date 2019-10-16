<?php

use Illuminate\Database\Seeder;

use App\User;
use App\Website;

class UserDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User;
        $user->name = "Faisal Ihsanul Fikri";
        $user->email = "faisalihsanulfikri@gmail.com";
        $user->password = bcrypt('qwerty');
        $user->save();

        $web = new Website;
        $web->user_id = $user->id;
        $web->name = "undefine.co.id";
        $web->database = "skripsi_webhade_tenant";
        $web->username = "root";
        $web->password = "";
        $web->save();
    }
}
