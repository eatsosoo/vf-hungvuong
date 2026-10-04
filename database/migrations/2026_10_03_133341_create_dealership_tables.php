<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('editor')->index();
            $table->boolean('is_active')->default(true)->index();
        });
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('alt')->nullable();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size');
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('segment')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('brochure_url')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('vehicle_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 0)->nullable();
            $table->unique(['vehicle_id', 'name']);
            $table->timestamps();
        });
        Schema::create('vehicle_colors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hex', 7)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unique(['vehicle_id', 'name']);
            $table->timestamps();
        });
        foreach (['categories', 'tags'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->foreignId('share_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });
        Schema::create('post_tag', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });
        Schema::create('post_vehicle', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'vehicle_id']);
        });
        Schema::create('post_redirects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
        Schema::create('promotion_vehicle', function (Blueprint $table): void {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->primary(['promotion_id', 'vehicle_id']);
        });
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('name', 100);
            $table->text('phone');
            $table->text('email')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 100)->default('website')->index();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('preferred_at')->nullable();
            $table->timestamp('appointment_at')->nullable();
            $table->string('location')->nullable();
            $table->decimal('quote_amount', 15, 0)->nullable();
            $table->text('quote_details')->nullable();
            $table->timestamp('consented_at');
            $table->timestamps();
            $table->index(['assigned_to', 'created_at']);
        });
        Schema::create('lead_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('changed_fields');
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'audit_logs', 'settings', 'lead_notes', 'leads', 'pages', 'promotion_vehicle',
            'promotions', 'post_redirects', 'post_vehicle', 'post_tag', 'posts', 'tags', 'categories',
            'vehicle_colors', 'vehicle_variants', 'vehicles', 'media'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
