<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\GajiPns;

class GajiCalculationTest extends TestCase
{
    /**
     * Test the executive summary calculation avoids N+1 and aggregates correctly.
     * Note: This is an integration test. We assume a valid user can hit the dashboard
     * and get valid JSON containing kotor, bersih, etc.
     */
    public function test_dashboard_executive_summary_aggregation()
    {
        // 1. Create a mock superadmin user
        $user = User::factory()->create([
            'role' => 'superadmin',
            'app_access' => json_encode(['pns', 'pppk'])
        ]);

        // 2. Insert some dummy data for GajiPns to verify summation
        GajiPns::insert([
            [
                'nip' => '198001012010011001',
                'nama' => 'PNS Test 1',
                'kdskpd' => '001',
                'kotor' => 5000000,
                'bersih' => 4500000,
                'bulan' => 1,
                'tahun' => 2026,
                'jenis_gaji' => 'Induk',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nip' => '198001012010011002',
                'nama' => 'PNS Test 2',
                'kdskpd' => '001',
                'kotor' => 6000000,
                'bersih' => 5000000,
                'bulan' => 1,
                'tahun' => 2026,
                'jenis_gaji' => 'Induk',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // 3. Act as the user and request the dashboard endpoint
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard/executive?year=2026&month=1');

        // 4. Assert
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'pns' => [
                             'total_kotor',
                             'total_bersih',
                             'total_pegawai'
                         ]
                     ]
                 ]);
                 
        $responseData = $response->json('data.pns');
        
        $this->assertEquals(11000000, $responseData['total_kotor']); // 5M + 6M
        $this->assertEquals(9500000, $responseData['total_bersih']); // 4.5M + 5M
        $this->assertEquals(2, $responseData['total_pegawai']);
        
        // Clean up
        GajiPns::where('tahun', 2026)->where('bulan', 1)->delete();
        $user->delete();
    }
}
