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
        // 1. Projects Table
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('is_password_protected')->default(false);
            $table->string('password')->nullable();
            $table->boolean('allow_download')->default(true);
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->unsignedBigInteger('hero_photo_id')->nullable();
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_downloads')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('slug');
            $table->index('expires_at');
        });

        // 2. Galleries Table
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('title');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'sort_order']);
        });

        // 3. Photos Table
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained('galleries')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('original_path', 500);
            $table->string('web_path', 500)->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            $table->index(['gallery_id', 'sort_order']);
        });

        // Add foreign key constraint to projects for hero_photo_id
        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('hero_photo_id')->references('id')->on('photos')->onDelete('set null');
        });

        // 4. Project Views Table
        Schema::create('project_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['project_id', 'ip_address', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });

        // 5. Download Logs Table
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('photo_id')->nullable()->constrained('photos')->onDelete('set null');
            $table->enum('type', ['single', 'zip']);
            $table->string('ip_address', 45);
            $table->timestamp('created_at')->nullable();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['hero_photo_id']);
        });

        Schema::dropIfExists('download_logs');
        Schema::dropIfExists('project_views');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('projects');
    }
};
