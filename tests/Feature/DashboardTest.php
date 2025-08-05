<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_correct_member_count()
    {
        // Create some test members
        Member::create([
            'uid' => 'TEST001',
            'member_number' => 'MEM001',
            'membership' => 'premium',
            'full_name' => 'Test User 1',
            'mobile_number' => '+1 (555) 123-4567',
            'email' => 'test1@example.com',
        ]);

        Member::create([
            'uid' => 'TEST002',
            'member_number' => 'MEM002',
            'membership' => 'basic',
            'full_name' => 'Test User 2',
            'mobile_number' => '+1 (555) 234-5678',
            'email' => 'test2@example.com',
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('2'); // Should show 2 members
    }

    public function test_dashboard_shows_zero_when_no_members()
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('0'); // Should show 0 members
    }
}
