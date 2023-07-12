<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('calendar')) {
            return;
        }
        Schema::create('calendar', function (Blueprint $table) {
            $table->char('id', 36)->index()->primary();
            $table->char('parent_id', 36)->index()->nullable();
            $table->char('form_id', 36)->index()->nullable();
            $table->char('asset_id', 36)->index()->nullable();
            $table->char('usage_id', 36)->index()->nullable();
            $table->string('slug');
            $table->string('title');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->tinyInteger('pending')->default('0');
            $table->tinyInteger('allow')->default('0');
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
        Schema::dropIfExists('calendar');
    }
}
