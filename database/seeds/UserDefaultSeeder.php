<?php

use Illuminate\Database\Seeder;

use App\User;
use App\UserWebsite;
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
        $user->phone = "082214573088";
        $user->user_level = "admin";
        $user->address = "Jl. Holis No. 260 Bandung";
        $user->status = "active";
        $user->save();

        $web = new Website;
        $web->domain = "undefine.com";
        $web->subdomain = "undefine";
        $web->db_name = env("TENANT_DB_NAME");
        $web->db_user = env("TENANT_DB_USER");
        $web->db_password = env("TENANT_DB_PASSWORD");
        $web->status = "active";
        $web->save();

        $uWeb = new UserWebsite;
        $uWeb->id_user = 1;
        $uWeb->id_website = 1;
        $uWeb->save();
    }
}
