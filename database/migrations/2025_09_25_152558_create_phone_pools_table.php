<?php

use App\Models\User;
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
        Schema::create('phone_pools', function (Blueprint $table) {
            $table->id();
            // Requested fields:
            $table->string('caller_id', 10);                    // e.g., "8095551234"
            $table->string('area_code', 10);                    // e.g., "809"
            $table->boolean('active')->default(true);           // available to assign?
            $table->dateTime('last_assigned_date')->nullable(); // when last used
            $table->string('last_assigned_campaign', 20)->nullable();
            // $table->foreignIdFor(User::class)->nullable()->constrained();

            $table->index(['area_code', 'active']);
            $table->index('caller_id'); // uniqueness is optional; uncomment if needed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_pools');
    }
};
