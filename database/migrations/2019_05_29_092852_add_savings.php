<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSavings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('savings')){
            Schema::create('savings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('user_id');
                $table->integer('savings_percentage');
                $table->integer('month');
                $table->integer('year');
                $table->timestamps();
            });
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('savings');
    }
}
