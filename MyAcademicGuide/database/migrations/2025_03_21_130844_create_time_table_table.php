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
        Schema::create('time_table', function (Blueprint $table) {
            $table->id('timetableid');
            $table->foreignId('userid')->constrained('user', 'userid')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('courseid')->constrained('course', 'courseid')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('semester', ['Fall', 'Spring', 'Summer']);
            $table->year('year');
            $table->string('days');
            $table->time('time');
            $table->foreignId('campusid')->constrained('campus', 'campusid')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_table');
    }
};
