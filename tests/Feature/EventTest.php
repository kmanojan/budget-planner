<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_event_under_budget_group_and_direct_attributes()
    {
        $user = User::factory()->create();

        $budget = Budget::create([
            'user_id'         => $user->id,
            'name'            => 'September General Budget',
            'amount'          => 200000,
            'period'          => 'monthly',
            'currency_code'   => 'LKR',
            'start_date'      => '2026-09-01',
            'alert_threshold' => 80,
        ]);

        // 1. Create Event under Budget
        $response = $this->actingAs($user)->postJson('/api/v1/events', [
            'title'      => 'Birthday Party',
            'budget_id'  => $budget->id,
            'event_date' => '2026-09-14',
            'status'     => 'planning',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.title', 'Birthday Party')
            ->assertJsonPath('data.budget_id', $budget->id);

        $eventId = $response->json('data.id');

        // 2. Add Group "Dress"
        $groupResp = $this->actingAs($user)->postJson("/api/v1/events/{$eventId}/groups", [
            'name' => 'Dress',
            'icon' => 'checkroom',
        ]);
        $groupResp->assertStatus(201)
            ->assertJsonPath('data.name', 'Dress');
        $groupId = $groupResp->json('data.id');

        // 3. Add Direct Budget Attributes inside "Dress"
        $this->actingAs($user)->postJson("/api/v1/event-groups/{$groupId}/attributes", [
            'type'            => 'budget',
            'name'            => "Father's Dress",
            'expected_amount' => 8000,
            'actual_amount'   => 7500,
        ])->assertStatus(201);

        $this->actingAs($user)->postJson("/api/v1/event-groups/{$groupId}/attributes", [
            'type'            => 'budget',
            'name'            => "Child's Dress",
            'expected_amount' => 5000,
            'actual_amount'   => 5200,
        ])->assertStatus(201);

        // 4. Check Event details -> totals expected: 13000, actual: 12700
        $eventDetail = $this->actingAs($user)->getJson("/api/v1/events/{$eventId}");
        $eventDetail->assertStatus(200)
            ->assertJsonPath('data.total_expected_budget', 13000)
            ->assertJsonPath('data.total_actual_budget', 12700);

        // 5. Add Direct Notes Attribute and Todo Attribute
        $this->actingAs($user)->postJson("/api/v1/event-groups/{$groupId}/attributes", [
            'type'    => 'notes',
            'name'    => 'Tailor Note',
            'content' => 'Pickup on Sept 10',
        ])->assertStatus(201);

        $this->actingAs($user)->postJson("/api/v1/event-groups/{$groupId}/attributes", [
            'type'    => 'todo',
            'name'    => 'Confirm booking',
            'is_done' => true,
        ])->assertStatus(201);

        // 6. Get report data
        $reportResp = $this->actingAs($user)->getJson("/api/v1/events/{$eventId}/report");
        $reportResp->assertStatus(200)
            ->assertJsonPath('data.expected_total', 13000)
            ->assertJsonPath('data.actual_total', 12700)
            ->assertJsonPath('data.by_group.0.group', 'Dress')
            ->assertJsonPath('data.by_group.0.expected', 13000)
            ->assertJsonPath('data.by_group.0.actual', 12700);
    }
}
