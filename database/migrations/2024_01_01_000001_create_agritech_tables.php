<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Agregar columna role a la tabla users que ya crea Laravel
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'technician', 'viewer'])->default('viewer')->after('password');
        });

        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('area_hectares', 8, 2)->nullable();
            $table->string('location_description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['humidity', 'temperature', 'nutrients']);
            $table->string('unit');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->timestamps();
        });

        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained()->onDelete('cascade');
            $table->decimal('value', 8, 2);
            $table->string('unit');
            $table->enum('status', ['optimal', 'alert', 'critical'])->default('optimal');
            $table->timestamps();
            $table->index(['sensor_id', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plot_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['daily', 'weekly', 'full']);
            $table->string('file_path')->nullable();
            $table->json('data_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('readings');
        Schema::dropIfExists('sensors');
        Schema::dropIfExists('plots');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};