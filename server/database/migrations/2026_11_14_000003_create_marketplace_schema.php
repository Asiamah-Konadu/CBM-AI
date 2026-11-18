<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException('Custo.AI marketplace migrations require PostgreSQL with PostGIS enabled.');
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name', 40)->unique();
        });

        DB::table('roles')->insert([
            ['name' => 'buyer'],
            ['name' => 'seller'],
            ['name' => 'reviewer'],
            ['name' => 'admin'],
        ]);

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('applies_to', 20);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_applies_to_check CHECK (applies_to IN ('business', 'listing', 'both'))");

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('primary_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->text('description');
            $table->string('business_status', 24)->default('draft');
            $table->string('verification_status', 24)->default('unsubmitted');
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('verification_expires_at')->nullable();
            $table->string('phone', 40);
            $table->string('whatsapp_phone', 40)->nullable();
            $table->text('website_url')->nullable();
            $table->text('address_line');
            $table->string('neighborhood', 120)->nullable();
            $table->string('city', 120);
            $table->string('region', 120)->nullable();
            $table->char('country_code', 2)->default('GH');
            $table->char('currency_code', 3)->default('GHS');
            $table->jsonb('opening_hours')->default('{}');
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index('owner_user_id');
            $table->index(['business_status', 'verification_status'], 'businesses_public_status_idx');
            $table->index(['country_code', 'city', 'neighborhood'], 'businesses_city_idx');
        });
        DB::statement('ALTER TABLE businesses ADD COLUMN location geography(Point, 4326) NULL');
        DB::statement('CREATE INDEX businesses_location_gist_idx ON businesses USING gist (location)');
        DB::statement("ALTER TABLE businesses ADD CONSTRAINT businesses_status_check CHECK (business_status IN ('draft', 'pending_review', 'published', 'suspended', 'archived'))");
        DB::statement("ALTER TABLE businesses ADD CONSTRAINT businesses_verification_status_check CHECK (verification_status IN ('unsubmitted', 'pending', 'verified', 'changes_requested', 'rejected', 'expired'))");
        DB::statement("ALTER TABLE businesses ADD CONSTRAINT businesses_verified_at_check CHECK (verification_status <> 'verified' OR verified_at IS NOT NULL)");
        DB::statement("ALTER TABLE businesses ADD CONSTRAINT businesses_published_at_check CHECK (business_status <> 'published' OR published_at IS NOT NULL)");

        Schema::create('business_categories', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['business_id', 'category_id']);
        });
        DB::statement('CREATE UNIQUE INDEX business_categories_one_primary_idx ON business_categories (business_id) WHERE is_primary = true');

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('listing_type', 16);
            $table->string('title', 180);
            $table->string('slug', 200);
            $table->text('description');
            $table->text('keywords')->nullable();
            $table->string('listing_status', 16)->default('draft');
            $table->string('price_type', 20)->default('quote');
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->char('currency_code', 3)->default('GHS');
            $table->string('availability_status', 20)->default('unknown');
            $table->unsignedInteger('available_quantity')->nullable();
            $table->unsignedInteger('minimum_order_quantity')->nullable();
            $table->string('condition', 16)->nullable();
            $table->timestampTz('last_confirmed_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['business_id', 'slug']);
            $table->index(['business_id', 'listing_status']);
            $table->index(['category_id', 'listing_status']);
            $table->index(['currency_code', 'price_amount'], 'listings_price_idx');
            $table->index(['availability_status', 'last_confirmed_at'], 'listings_availability_idx');
        });
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_type_check CHECK (listing_type IN ('product', 'service'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_status_check CHECK (listing_status IN ('draft', 'published', 'archived'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_price_type_check CHECK (price_type IN ('fixed', 'starting_from', 'quote', 'not_listed'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_price_amount_check CHECK ((price_type IN ('fixed', 'starting_from') AND price_amount IS NOT NULL) OR (price_type IN ('quote', 'not_listed') AND price_amount IS NULL))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_availability_check CHECK (availability_status IN ('in_stock', 'limited', 'made_to_order', 'out_of_stock', 'unknown'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_condition_check CHECK (condition IS NULL OR condition IN ('new', 'used', 'refurbished', 'not_applicable'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_published_at_check CHECK (listing_status <> 'published' OR published_at IS NOT NULL)");
        DB::statement('ALTER TABLE listings ADD CONSTRAINT listings_quantity_check CHECK (available_quantity IS NULL OR available_quantity >= 0)');
        DB::statement('ALTER TABLE listings ADD CONSTRAINT listings_minimum_order_check CHECK (minimum_order_quantity IS NULL OR minimum_order_quantity > 0)');
        DB::statement("ALTER TABLE listings ADD COLUMN search_document tsvector GENERATED ALWAYS AS (to_tsvector('simple'::regconfig, coalesce(title, '') || ' ' || coalesce(description, '') || ' ' || coalesce(keywords, ''))) STORED");
        DB::statement('CREATE INDEX listings_search_gin_idx ON listings USING gin (search_document)');

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purpose', 24);
            $table->string('storage_disk', 60);
            $table->text('storage_key')->unique();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('byte_size');
            $table->boolean('is_public')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['business_id', 'purpose', 'sort_order'], 'media_business_purpose_idx');
            $table->index(['listing_id', 'sort_order'], 'media_listing_idx');
        });
        DB::statement("ALTER TABLE media ADD CONSTRAINT media_purpose_check CHECK (purpose IN ('logo', 'business_gallery', 'listing_image', 'verification_document'))");
        DB::statement("ALTER TABLE media ADD CONSTRAINT media_private_verification_check CHECK (purpose <> 'verification_document' OR is_public = false)");
        DB::statement("ALTER TABLE media ADD CONSTRAINT media_listing_image_check CHECK (purpose <> 'listing_image' OR listing_id IS NOT NULL)");
        DB::statement('ALTER TABLE media ADD CONSTRAINT media_byte_size_check CHECK (byte_size > 0)');

        Schema::create('verification_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('pending');
            $table->text('seller_note')->nullable();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reviewer_note')->nullable();
            $table->timestampTz('submitted_at')->useCurrent();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'submitted_at'], 'verification_queue_idx');
            $table->index(['business_id', 'submitted_at'], 'verification_business_idx');
        });
        DB::statement("ALTER TABLE verification_submissions ADD CONSTRAINT verification_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'changes_requested'))");
        DB::statement("ALTER TABLE verification_submissions ADD CONSTRAINT verification_reviewed_check CHECK ((status = 'pending' AND reviewed_at IS NULL) OR (status <> 'pending' AND reviewed_at IS NOT NULL AND reviewer_user_id IS NOT NULL))");

        Schema::create('verification_documents', function (Blueprint $table) {
            $table->foreignId('submission_id')->constrained('verification_submissions')->cascadeOnDelete();
            $table->foreignId('media_id')->unique()->constrained('media')->restrictOnDelete();
            $table->string('document_type', 60);
            $table->primary(['submission_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('verification_submissions');
        Schema::dropIfExists('media');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('business_categories');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'is_active']);
        });
    }
};
