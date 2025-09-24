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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            
            // API Key relationship
            $table->unsignedBigInteger('api_key_id')->nullable();
            $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('set null');
            
            // Request Information
            $table->string('ip_address', 45);  // Support IPv6
            $table->text('user_agent')->nullable();
            $table->string('method', 10);  // GET, POST, PUT, DELETE, etc.
            $table->text('url');  // Full URL including query parameters
            $table->text('route')->nullable();  // Laravel route name
            
            // Request Data
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->json('query_parameters')->nullable();
            
            // Response Information
            $table->integer('response_status')->index();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            
            // Performance & Error Tracking
            $table->integer('execution_time_ms')->nullable();  // Processing time in milliseconds
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();
            
            // Additional Metadata
            $table->string('api_key_hash', 64)->nullable()->index();  // Hashed API key for privacy
            $table->boolean('is_authenticated')->default(false)->index();
            $table->string('request_id', 36)->nullable()->index();  // UUID for request tracking
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('created_at');
            $table->index(['api_key_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['response_status', 'created_at']);
            $table->index(['is_authenticated', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};