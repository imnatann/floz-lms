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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type'); // e.g., 'application/pdf', 'image/jpeg'
            $table->bigInteger('file_size'); // in bytes
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->dateTime('submitted_at');
            $table->string('status')->default('submitted'); // submitted, graded, late
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->string('link')->nullable(); // For link submissions
            $table->timestamps();

            // Ensure a student can only submit once per assignment (or handle multiple submissions differently)
            // For now, let's assume one submission record per student per assignment, which they can update.
            // But if we want history, we might remove this unique constraint. 
            // The prompt implies "Status: Belum Dijawab / Sudah Dijawab", so 1-1 mapping is simplest.
            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('submission_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->bigInteger('file_size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_attachments');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignment_attachments');
        Schema::dropIfExists('assignments');
    }
};
