<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (Schema::hasTable('usage')) {
            return;
        }

        Schema::create('usage', function (Blueprint $table) {
            $table->char('id', 36)->index()->primary();
            $table->char('parent_id', 36)->index()->nullable();
            $table->char('form_id', 36)->index()->nullable();
            $table->char('asset_id', 36)->index()->nullable();
            $table->string('title')->default('');
            $table->date('date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start')->nullable();
            $table->time('end')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->char('created_by', 36)->nullable();
            $table->char('updated_by', 36)->nullable();
            $table->char('deleted_by', 36)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usage');
    }
}
