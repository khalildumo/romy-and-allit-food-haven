<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        Schema::create('menu_products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('category', 80);
            $table->string('description', 255)->default('');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('image_path')->default('');
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('stock_enabled')->default(false);
            $table->timestamps();
            $table->index(['category', 'is_available']);
        });

        Schema::create('canteen_profile', function (Blueprint $table) {
            $table->smallInteger('id')->primary();
            $table->string('canteen_name', 160)->default('Romy and Allit Food Haven');
            $table->string('operating_hours', 160)->default('Monday - Saturday, 6:00 AM - 5:00 PM');
            $table->string('contact_info')->default('');
            $table->string('pickup_location')->default('Main pickup counter');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number', 30)->unique();
            $table->string('student_name', 120);
            $table->string('student_id', 60)->default('');
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('pay_on_pickup');
            $table->string('fulfillment_type', 30)->default('pickup');
            $table->string('preferred_time', 40)->default('');
            $table->string('special_instructions', 500)->default('');
            $table->string('order_status', 30)->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->json('items_json');
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('code_hash', 255);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });

        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->index();
            $table->string('otp_hash');
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
        Schema::dropIfExists('email_otps');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('canteen_profile');
        Schema::dropIfExists('menu_products');
        Schema::dropIfExists('product_categories');
    }
};
