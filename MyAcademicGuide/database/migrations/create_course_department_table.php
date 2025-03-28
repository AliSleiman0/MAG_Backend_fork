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
        Schema::create('course_department', function (Blueprint $table) {
            $table->foreignId('courseid')->constrained('course', 'courseid')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('departmentid')->constrained('department', 'departmentid')->onDelete('cascade')->onUpdate('cascade');
            $table->primary(['courseid', 'departmentid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_department');
    }
};
