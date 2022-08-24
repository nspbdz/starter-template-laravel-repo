<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('plate_no')->nullable();
            $table->string('province_and_city')->nullable();
            $table->string('brand_and_type')->nullable();
            $table->string('vehicle_year')->nullable();
            $table->integer('bpkb')->nullable();
            $table->integer('rumah')->nullable();
            $table->integer('pajak')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
