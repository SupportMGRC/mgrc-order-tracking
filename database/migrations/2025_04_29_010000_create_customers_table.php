<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->string('title')->nullable();
            $table->string('name');
            $table->string('gender')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('phoneNo')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('accStatus')->nullable();
            $table->string('cust_group')->nullable();
            $table->string('preferredContactMethod')->nullable();
            $table->string('source')->nullable();
            $table->string('tag')->nullable();
            $table->text('remarks')->nullable();
            $table->date('lastInteractionDate')->nullable();
            $table->dateTime('lastUpdate')->nullable();

            // Foreign key to users
            $table->unsignedBigInteger('userID')->nullable();
            $table->foreign('userID')->references('id')->on('users')->onDelete('set null');

            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
