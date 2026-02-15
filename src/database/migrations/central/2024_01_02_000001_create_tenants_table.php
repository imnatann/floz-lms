<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('database_name')->unique();
            $table->string('domain')->unique()->nullable();
            $table->string('education_level', 50);
            $table->string('npsn', 20)->unique()->nullable();
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('subscription_plan', 50)->nullable();
            $table->integer('max_students')->default(100);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('education_level');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};
