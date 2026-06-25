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
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('requested_by_role');
            $table->string('from_role');
            $table->string('to_role');
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();

            // Equals `date` while the request is pending, NULL otherwise. The
            // unique index enforces "at most one pending request per day"
            // (MySQL allows multiple NULLs, so resolved requests don't collide).
            $table->date('active_date')->nullable()->unique();

            $table->timestamps();

            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_requests');
    }
};
