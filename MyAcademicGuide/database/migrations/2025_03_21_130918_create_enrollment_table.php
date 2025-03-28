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
        Schema::create('enrollment', function (Blueprint $table) {
            $table->id('enrollmentid');
            $table->foreignId('studentid')->constrained('student', 'studentid')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('courseid')->constrained('course', 'courseid')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('timetableid')->constrained('time_table', 'timetableid')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('status', ['Passed', 'Failed', 'Registered']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};
