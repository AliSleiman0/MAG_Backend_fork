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
        Schema::create('course_prerequisite', function (Blueprint $table) {
            $table->unsignedBigInteger('courseid');
            $table->unsignedBigInteger('prerequisitecourseid')->nullable();
            $table->unsignedBigInteger('corerequisiteid')->nullable();

            $table->foreign('courseid')->references('courseid')->on('course')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('prerequisitecourseid')->references('courseid')->on('course')->onDelete('cascade');
            $table->foreign('corerequisiteid')->references('courseid')->on('course')->onDelete('cascade');

            $table->unique(['courseid', 'prerequisitecourseid', 'corerequisiteid'], 'course_prereq_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_prerequisite');
    }
};
