<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Fees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('fees')) {
            return;
        }

        Schema::create('fees', function (Blueprint $table) {
            $table->char('id', 36)->index()->primary();
            $table->char('hirer_id', 36)->index()->nullable();
            $table->char('booking_id', 36)->index()->nullable();
            $table->char('asset_id', 36)->index()->nullable();
            $table->char('usage_id', 36)->index()->nullable();
            $table->string('name')->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('end')->nullable();
            $table->string('rate')->nullable();
            $table->string('unit')->nullable();
            $table->string('total')->nullable();
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
        Schema::dropIfExists('fees');
    }
}
