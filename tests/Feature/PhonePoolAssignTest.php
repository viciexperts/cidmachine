<?php

namespace Tests\Feature;

use App\Models\PhonePool;
use App\Models\PhonePoolReturn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhonePoolAssignTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/phone-pool/assign', [
            'caller_id' => '8095551234',
        ]);

        $response->assertStatus(401);
    }

    public function test_validation_fails_for_short_caller_id(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/phone-pool/assign', [
            'caller_id' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'caller_id must contain at least 10 digits');
    }

    public function test_exact_area_code_match_is_selected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        // Seed pool with multiple numbers
        PhonePool::create([
            'caller_id' => '8091112222',
            'area_code' => '809',
            'active' => true,
            'last_assigned_date' => null,
        ]);
        PhonePool::create([
            'caller_id' => '8293334444',
            'area_code' => '829',
            'active' => true,
            'last_assigned_date' => Carbon::now()->subDay(),
        ]);

        $response = $this->postJson('/api/phone-pool/assign', [
            'caller_id' => '+1 (809) 555-1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'caller_id', 'area_code', 'active', 'last_assigned_date', 'last_assigned_campaign', 'user_id',
                ],
            ])
            ->assertJsonPath('data.area_code', '809')
            ->assertJsonPath('data.caller_id', '8091112222');

        $this->assertDatabaseHas('phone_pool_returns', []); // at least one return row stored

        // Ensure last_assigned_date was updated
        $this->assertNotNull(PhonePool::where('caller_id', '8091112222')->first()->last_assigned_date);
    }

    public function test_closest_area_code_is_selected_when_exact_missing(): void
    {
        Sanctum::actingAs(User::factory()->create());

        // Only 829 and 849 active; request 809 -> closest is 829 (|829-809|=20) vs 849 (|849-809|=40)
        PhonePool::create([
            'caller_id' => '8291112222',
            'area_code' => '829',
            'active' => true,
        ]);
        PhonePool::create([
            'caller_id' => '8491112222',
            'area_code' => '849',
            'active' => true,
        ]);

        $response = $this->postJson('/api/phone-pool/assign', [
            'caller_id' => '8095559999',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.caller_id', '8291112222')
            ->assertJsonPath('data.area_code', '829');
    }

    public function test_404_when_no_active_numbers(): void
    {
        Sanctum::actingAs(User::factory()->create());

        PhonePool::create([
            'caller_id' => '8090000000',
            'area_code' => '809',
            'active' => false,
        ]);

        $response = $this->postJson('/api/phone-pool/assign', [
            'caller_id' => '8095551234',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'No active phone numbers available in the pool');
    }

    public function test_persists_transaction_payload_in_phone_pool_returns(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $record = PhonePool::create([
            'caller_id' => '8091234567',
            'area_code' => '809',
            'active' => true,
        ]);

        $this->postJson('/api/phone-pool/assign', [
            'caller_id' => '8095551234',
        ])->assertOk();

        $this->assertDatabaseCount('phone_pool_returns', 1);

        $ret = PhonePoolReturn::first();
        $this->assertIsArray($ret->data);
        $this->assertSame($record->id, $ret->data['id']);
        $this->assertSame('8091234567', $ret->data['caller_id']);
        $this->assertSame('809', $ret->data['area_code']);
    }
}
