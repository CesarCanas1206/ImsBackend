<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('booking')) {
            return;
        }
        Schema::create('booking', function (Blueprint $table) {
            $table->char('id', 36)->index();
            $table->increments('application_id')->index();
            $table->char('form_id', 36)->nullable();
            $table->char('parent_id', 36)->index()->nullable();
            $table->char('hirer_id', 36)->index()->nullable();
            $table->string('type')->default('');
            $table->string('name')->default('');
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
        Schema::dropIfExists('booking');
    }
}
