<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCategoriesToIncomeExpenseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasTable('income_expenses')){
            Schema::table('income_expenses', function (Blueprint $table) {                
                if(!Schema::hasColumn('income_expenses', 'categories')){
                    $table->string('categories')->after('balance')->nullable();
                }
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
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropColumn('categories');
        });
    }
}
