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
        Schema::create('course', function (Blueprint $table) {
            $table->id('courseid');
            $table->string('coursename');
            $table->string('coursecode');
            $table->integer('credits');
            $table->enum('semester', ['Fall', 'Spring', 'Summer', 'Fall-Spring', 'Fall-Summer', 'Spring-Summer', 'Fall-Spring-Summer']);
            $table->enum('coursetype', ['Core', 'Major',  'Major Elective', 'General Elective', 'General Education']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course');
    }
};
