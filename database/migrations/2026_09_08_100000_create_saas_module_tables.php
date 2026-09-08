<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo SaaS: tabela de planos que o admin cria para vender assinaturas
 * aos infoprodutores que usam a plataforma.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_plans')) {
            Schema::create('saas_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');                           // Ex: "Starter", "Pro", "Business"
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0);      // Preço em BRL
                $table->enum('interval', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
                $table->unsignedInteger('max_products')->nullable();      // null = ilimitado
                $table->unsignedInteger('max_orders_per_month')->nullable(); // null = ilimitado
                $table->unsignedInteger('max_students')->nullable();      // null = ilimitado
                $table->boolean('is_free')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_highlighted')->default(false); // Destaque visual
                $table->text('features')->nullable();               // JSON array de features para exibição
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('saas_subscriptions')) {
            Schema::create('saas_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('saas_plan_id')->constrained('saas_plans')->cascadeOnDelete();
                $table->enum('status', ['active', 'past_due', 'cancelled', 'expired'])->default('active');
                $table->date('starts_at');
                $table->date('expires_at');
                $table->string('gateway')->nullable();              // Ex: 'mercadopago', 'stripe', 'efi'
                $table->string('gateway_subscription_id')->nullable();
                $table->string('gateway_payment_id')->nullable();
                $table->decimal('amount_paid', 10, 2)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['expires_at']);
            });
        }

        if (! Schema::hasTable('saas_usage_counters')) {
            Schema::create('saas_usage_counters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('period', 7);  // Format: "2026-09"
                $table->unsignedInteger('orders_count')->default(0);
                $table->unsignedInteger('products_count')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'period']);
            });
        }

        // Flag para saber se o plano free já foi atribuído ao user
        if (! Schema::hasColumn('users', 'saas_free_plan_assigned')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('saas_free_plan_assigned')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_usage_counters');
        Schema::dropIfExists('saas_subscriptions');
        Schema::dropIfExists('saas_plans');

        if (Schema::hasColumn('users', 'saas_free_plan_assigned')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('saas_free_plan_assigned');
            });
        }
    }
};
