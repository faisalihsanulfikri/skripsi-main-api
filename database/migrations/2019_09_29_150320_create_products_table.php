<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Website;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $web = Website::orderBy('id','desc')->get();
        if (count($web) > 0) {
            foreach ($web as $i => $el) {
                Config::set('database.connections.tenant.database', $el['database']);
                Config::set('database.connections.tenant.username', $el['username']);
                Config::set('database.connections.tenant.password', $el['password']);

                DB::purge('tenant');
                DB::reconnect('tenant');

                Schema::connection('tenant')->create('products', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('name');
                    $table->integer('price');
                    $table->integer('qty');
                    $table->timestamps();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $web = Website::orderBy('id','desc')->get();
        if (count($web) > 0) {
            foreach ($web as $i => $el) {
                Config::set('database.connections.tenant.database', $el['database']);
                Config::set('database.connections.tenant.username', $el['username']);
                Config::set('database.connections.tenant.password', $el['password']);

                DB::purge('tenant');
                DB::reconnect('tenant');

                Schema::connection('tenant')->dropIfExists('products');
            }
        }
    }
}
