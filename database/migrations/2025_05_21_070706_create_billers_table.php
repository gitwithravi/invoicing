<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billers', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->string('country');
            $table->string('tax_identifier_name')->nullable();
            $table->string('tax_identifier_number')->nullable();
            $table->string('logo', 512)->nullable();
            $table->string('website', 512)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->string('signature_name', 512)->nullable();
            $table->string('signature_image', 512)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billers');
    }
};
