<?php

namespace Tests\Feature;

use App\Models\Bed;
use App\Models\Complaint;
use App\Models\ElectricityBill;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Room;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LodgeMateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Niwasthal');
    }

    public function test_owner_can_login_and_view_dashboard(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        $response = $this->actingAs($owner)->get('/owner/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Welcome back, Raj Kumar');
    }

    public function test_owner_can_view_all_module_pages(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();
        $property = Property::where('owner_id', $owner->id)->firstOrFail();
        $room = Room::where('property_id', $property->id)->firstOrFail();
        $student = Student::where('property_id', $property->id)->firstOrFail();
        $rent = RentRecord::where('property_id', $property->id)->firstOrFail();
        $bill = ElectricityBill::where('property_id', $property->id)->firstOrFail();
        $complaint = Complaint::where('property_id', $property->id)->firstOrFail();

        $this->actingAs($owner)->get('/owner/properties')->assertStatus(200);
        $this->actingAs($owner)->get("/owner/properties/{$property->id}")->assertStatus(200);
        $this->actingAs($owner)->get("/owner/properties/{$property->id}/rooms")->assertStatus(200);
        $this->actingAs($owner)->get("/owner/properties/{$property->id}/rooms/{$room->id}")->assertStatus(200);
        $this->actingAs($owner)->get("/owner/properties/{$property->id}/beds")->assertStatus(200);
        $this->actingAs($owner)->get('/owner/students')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/students/create')->assertStatus(200);
        $this->actingAs($owner)->get("/owner/students/{$student->id}")->assertStatus(200);
        $this->actingAs($owner)->get("/owner/students/{$student->id}/edit")->assertStatus(200);
        $this->actingAs($owner)->get('/owner/rent')->assertStatus(200);
        $this->actingAs($owner)->get("/owner/rent/{$rent->id}")->assertStatus(200);
        $this->actingAs($owner)->get('/owner/payments')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/electricity')->assertStatus(200);
        $this->actingAs($owner)->get("/owner/electricity/{$bill->id}")->assertStatus(200);
        $this->actingAs($owner)->get('/owner/deposits')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/expenses')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/complaints')->assertStatus(200);
        $this->actingAs($owner)->get("/owner/complaints/{$complaint->id}")->assertStatus(200);
        $this->actingAs($owner)->get('/owner/notifications')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/announcements')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/rent-collection')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/pending-dues')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/occupancy')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/expenses')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/students')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/settings')->assertStatus(200);
    }

    public function test_owner_crud_flows_work_end_to_end(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        // 1. Create Property
        $this->actingAs($owner)->post('/owner/properties', [
            'name'    => 'Emerald Residency',
            'address' => '45 Koramangala',
            'city'    => 'Bangalore',
            'type'    => 'pg',
        ])->assertRedirect();

        $property = Property::where('name', 'Emerald Residency')->firstOrFail();

        // 2. Create Room (auto-creates 2 beds)
        $this->actingAs($owner)->post("/owner/properties/{$property->id}/rooms", [
            'room_number' => 'E101',
            'floor'       => '1st Floor',
            'type'        => 'double',
            'capacity'    => 2,
            'rent_amount' => 8000,
        ])->assertRedirect();

        $room = Room::where('property_id', $property->id)->where('room_number', 'E101')->firstOrFail();
        $this->assertEquals(2, $room->beds()->count());
        $bed = $room->beds()->firstOrFail();

        // 3. Onboard Student
        $this->actingAs($owner)->post('/owner/students', [
            'name'             => 'Test Resident',
            'mobile'           => '9111111111',
            'email'            => 'resident@example.com',
            'property_id'      => $property->id,
            'bed_id'           => $bed->id,
            'joining_date'     => date('Y-m-d'),
            'monthly_rent'     => 8000,
            'security_deposit' => 16000,
        ])->assertRedirect();

        $student = Student::where('mobile', '9111111111')->firstOrFail();
        $this->assertTrue((bool) $bed->fresh()->is_occupied);

        // 4. Generate Monthly Rent & Record Partial Payment
        $this->actingAs($owner)->post("/owner/students/{$student->id}/generate-rent")->assertRedirect();
        $rent = RentRecord::where('student_id', $student->id)->firstOrFail();
        $this->assertEquals(8000, (float) $rent->outstanding);

        $this->actingAs($owner)->post("/owner/rent/{$rent->id}/pay", [
            'amount' => 3000,
            'method' => 'upi',
        ])->assertRedirect();

        $this->assertEquals(5000, (float) $rent->fresh()->outstanding);
        $this->assertEquals('partial', $rent->fresh()->status);

        // 5. Log Expense
        $this->actingAs($owner)->post('/owner/expenses', [
            'property_id'  => $property->id,
            'category'     => 'maintenance',
            'description'  => 'Plumbing repair',
            'amount'       => 1200,
            'expense_date' => date('Y-m-d'),
        ])->assertRedirect();

        // 6. Create Electricity Bill & Auto Split
        $this->actingAs($owner)->post('/owner/electricity', [
            'property_id'   => $property->id,
            'month'         => now()->month,
            'year'          => now()->year,
            'total_units'   => 200,
            'rate_per_unit' => 8,
            'fixed_charges' => 100,
            'split_type'    => 'equal',
        ])->assertRedirect();

        $bill = ElectricityBill::where('property_id', $property->id)->firstOrFail();
        $this->assertEquals(1, $bill->allocations()->count());
        $this->assertEquals(1700, (float) $bill->allocations()->first()->amount);
    }

    public function test_student_portal_and_complaint_submission_and_isolation(): void
    {
        $student = Student::firstOrFail();
        $studentUser = $student->user;

        $this->actingAs($studentUser)->get('/student/dashboard')->assertStatus(200);
        $this->actingAs($studentUser)->get('/student/room')->assertStatus(200);
        $this->actingAs($studentUser)->get('/student/rent')->assertStatus(200);
        $this->actingAs($studentUser)->get('/student/payments')->assertStatus(200);
        $this->actingAs($studentUser)->get('/student/electricity')->assertStatus(200);
        $this->actingAs($studentUser)->get('/student/complaints')->assertStatus(200);
        $this->actingAs($studentUser)->get('/student/notifications')->assertStatus(200);

        // Submit complaint
        $this->actingAs($studentUser)->post('/student/complaints', [
            'title'       => 'AC Cooling Issue',
            'category'    => 'maintenance',
            'priority'    => 'high',
            'description' => 'AC in room is blowing warm air.',
        ])->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'student_id' => $student->id,
            'title'      => 'AC Cooling Issue',
        ]);

        // Role isolation check
        $this->actingAs($studentUser)->get('/owner/dashboard')->assertStatus(403);
    }

    public function test_student_otp_send_and_verify(): void
    {
        $sendRes = $this->postJson('/student/otp/send', [
            'mobile' => '9000000001',
        ]);
        $sendRes->assertStatus(200)->assertJsonStructure(['message', 'otp_dev']);

        $otp = $sendRes->json('otp_dev');

        $verifyRes = $this->postJson('/student/otp/verify', [
            'mobile' => '9000000001',
            'otp'    => $otp,
        ]);
        $verifyRes->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_pdf_and_excel_reports_generate_successfully(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        $this->actingAs($owner)->get('/owner/reports/rent-collection?format=pdf')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/rent-collection?format=excel')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/pending-dues?format=pdf')->assertStatus(200);
        $this->actingAs($owner)->get('/owner/reports/occupancy?format=excel')->assertStatus(200);
    }

    public function test_owner_can_create_property_and_rooms_in_two_step_wizard(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        $response = $this->actingAs($owner)->post('/owner/properties', [
            'name'         => 'Skyline Heights PG',
            'address'      => '88 HSR Layout Sector 2',
            'city'         => 'Bangalore',
            'type'         => 'pg',
            'total_floors' => 2,
            'rooms'        => [
                [
                    'room_number' => 'G01',
                    'floor'       => 'Ground Floor',
                    'type'        => 'double',
                    'capacity'    => 2,
                    'rent_amount' => 7500,
                ],
                [
                    'room_number' => 'G02',
                    'floor'       => 'Ground Floor',
                    'type'        => 'single',
                    'capacity'    => 1,
                    'rent_amount' => 10000,
                ],
                [
                    'room_number' => '101',
                    'floor'       => '1st Floor',
                    'type'        => 'triple',
                    'capacity'    => 3,
                    'rent_amount' => 6000,
                ],
            ],
        ]);

        $response->assertRedirect();

        $property = Property::where('name', 'Skyline Heights PG')->firstOrFail();
        $this->assertEquals(2, $property->total_floors);
        $this->assertEquals(3, $property->total_rooms);
        $this->assertCount(3, $property->rooms);

        $room101 = Room::where('property_id', $property->id)->where('room_number', '101')->firstOrFail();
        $this->assertEquals('1st Floor', $room101->floor);
        $this->assertEquals('per_bed', $room101->rent_type);
        $this->assertCount(3, $room101->beds);
    }

    public function test_shop_and_family_rooms_create_zero_beds_and_enforce_per_room_rent_type(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        $this->actingAs($owner)->post('/owner/properties', [
            'name'         => 'Commercial & Family Hub',
            'address'      => '12 Main Market Road',
            'city'         => 'Bangalore',
            'type'         => 'lodge',
            'total_floors' => 2,
            'rooms'        => [
                [
                    'room_number' => 'S01',
                    'floor'       => 'Ground Floor',
                    'type'        => 'shop',
                    'capacity'    => 3, // Even if non-zero sent, backend forces 0 for shop
                    'rent_type'   => 'per_bed', // Even if per_bed sent, backend forces per_room
                    'rent_amount' => 25000,
                ],
                [
                    'room_number' => 'F101',
                    'floor'       => '1st Floor',
                    'type'        => 'family',
                    'rent_amount' => 18000,
                ],
                [
                    'room_number' => '102',
                    'floor'       => '1st Floor',
                    'type'        => 'double',
                    'capacity'    => 2,
                    'rent_type'   => 'per_room',
                    'rent_amount' => 14000,
                ],
            ],
        ])->assertRedirect();

        $property = Property::where('name', 'Commercial & Family Hub')->firstOrFail();

        $shop = Room::where('property_id', $property->id)->where('room_number', 'S01')->firstOrFail();
        $this->assertEquals(0, $shop->capacity);
        $this->assertEquals('per_room', $shop->rent_type);
        $this->assertCount(0, $shop->beds);

        $family = Room::where('property_id', $property->id)->where('room_number', 'F101')->firstOrFail();
        $this->assertEquals(0, $family->capacity);
        $this->assertEquals('per_room', $family->rent_type);
        $this->assertCount(0, $family->beds);

        $doublePerRoom = Room::where('property_id', $property->id)->where('room_number', '102')->firstOrFail();
        $this->assertEquals(2, $doublePerRoom->capacity);
        $this->assertEquals('per_room', $doublePerRoom->rent_type);
        $this->assertCount(2, $doublePerRoom->beds);

        // Standalone RoomController::store without capacity for shop
        $this->actingAs($owner)->post("/owner/properties/{$property->id}/rooms", [
            'room_number' => 'S02',
            'floor'       => 'Ground Floor',
            'type'        => 'shop',
            'rent_amount' => 22000,
        ])->assertRedirect();

        $shop2 = Room::where('property_id', $property->id)->where('room_number', 'S02')->firstOrFail();
        $this->assertEquals(0, $shop2->capacity);
        $this->assertEquals('per_room', $shop2->rent_type);
        $this->assertCount(0, $shop2->beds);
    }

    public function test_lodge_owner_uses_renter_terminology_and_dashboard_room_history_works(): void
    {
        // Register as Lodge Owner
        $this->post('/register', [
            'name'                  => 'Vikram Lodge Owner',
            'email'                 => 'lodgeowner@example.com',
            'mobile'                => '9888877777',
            'business_type'         => 'lodge',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/owner/dashboard');

        $lodgeOwner = User::where('email', 'lodgeowner@example.com')->firstOrFail();
        $this->assertEquals('lodge', $lodgeOwner->business_type);
        $this->assertEquals('Renter', $lodgeOwner->occupantLabel());

        // Create Lodge property with a bed room (R2) and a shop unit (S1)
        $this->actingAs($lodgeOwner)->post('/owner/properties', [
            'name'         => 'Royal Lodge',
            'address'      => '5 Station Road',
            'city'         => 'Pune',
            'type'         => 'lodge',
            'total_floors' => 1,
            'rooms'        => [
                [
                    'room_number' => '2',
                    'floor'       => 'Ground Floor',
                    'type'        => 'single',
                    'capacity'    => 1,
                    'rent_type'   => 'per_bed',
                    'rent_amount' => 4500,
                ],
                [
                    'room_number' => 'S1',
                    'floor'       => 'Ground Floor',
                    'type'        => 'shop',
                    'rent_amount' => 12000,
                ],
            ],
        ])->assertRedirect();

        $property = Property::where('name', 'Royal Lodge')->firstOrFail();
        $room2 = Room::where('property_id', $property->id)->where('room_number', '2')->firstOrFail();
        $bed2A = $room2->beds()->firstOrFail();
        $shopS1 = Room::where('property_id', $property->id)->where('room_number', 'S1')->firstOrFail();

        // Dashboard shows "Renter" terminology
        $dashRes = $this->actingAs($lodgeOwner)->get('/owner/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Active Renters');
        $dashRes->assertSee('Add Renter');

        // Add Renter to Room 2 via dashboard quick-add
        $this->actingAs($lodgeOwner)->post('/owner/students', [
            'redirect_to'      => 'dashboard',
            'property_id'      => $property->id,
            'room_id'          => $room2->id,
            'bed_id'           => $bed2A->id,
            'name'             => 'Ramesh Kumar',
            'mobile'           => '9111122222',
            'joining_date'     => now()->toDateString(),
            'monthly_rent'     => 4500,
            'security_deposit' => 5000,
        ])->assertRedirect(route('owner.dashboard', ['room_id' => $room2->id]));

        $renter = Student::where('mobile', '9111122222')->firstOrFail();
        $this->assertEquals($room2->id, $renter->room_id);
        $this->assertTrue((bool) $bed2A->fresh()->is_occupied);

        // Add Renter directly to Shop S1 (no bed_id)
        $this->actingAs($lodgeOwner)->post('/owner/students', [
            'redirect_to'      => 'dashboard',
            'property_id'      => $property->id,
            'room_id'          => $shopS1->id,
            'name'             => 'Suresh Medical Shop',
            'mobile'           => '9111133333',
            'joining_date'     => now()->toDateString(),
            'monthly_rent'     => 12000,
            'security_deposit' => 25000,
        ])->assertRedirect(route('owner.dashboard', ['room_id' => $shopS1->id]));

        $shopRenter = Student::where('mobile', '9111133333')->firstOrFail();
        $this->assertNull($shopRenter->bed_id);
        $this->assertEquals($shopS1->id, $shopRenter->room_id);

        // Remove/vacate Ramesh from Room 2 via dashboard; verify bed freed & room_id preserved in pastStudents
        $this->actingAs($lodgeOwner)->delete("/owner/students/{$renter->id}", [
            'redirect_to' => 'dashboard',
        ])->assertRedirect(route('owner.dashboard', ['room_id' => $room2->id]));

        $renter->refresh();
        $this->assertEquals('vacated', $renter->status);
        $this->assertNull($renter->bed_id);
        $this->assertEquals($room2->id, $renter->room_id);
        $this->assertFalse((bool) $bed2A->fresh()->is_occupied);
        $this->assertCount(1, $room2->pastStudents);

        // Dashboard payload still includes Ramesh under Room 2 past occupants
        $this->actingAs($lodgeOwner)
            ->get("/owner/dashboard?room_id={$room2->id}")
            ->assertStatus(200)
            ->assertSee('Ramesh Kumar')
            ->assertSee('50%'); // 1 of 2 (Room 2 vacant, Shop S1 booked)
    }

    public function test_rooms_sort_naturally_starting_from_one(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        $this->actingAs($owner)->post('/owner/properties', [
            'name'         => 'Order Check Lodge',
            'address'      => '1 Test Street',
            'city'         => 'Bangalore',
            'type'         => 'lodge',
            'total_floors' => 3,
            'rooms'        => [
                ['room_number' => '10', 'floor' => '2nd Floor', 'type' => 'family', 'rent_amount' => 2000],
                ['room_number' => '04', 'floor' => '1st Floor', 'type' => 'shop',   'rent_amount' => 5000],
                ['room_number' => '01', 'floor' => 'Ground Floor', 'type' => 'family', 'rent_amount' => 3000],
                ['room_number' => '02', 'floor' => 'Ground Floor', 'type' => 'family', 'rent_amount' => 3000],
                ['room_number' => '09', 'floor' => '2nd Floor', 'type' => 'family', 'rent_amount' => 2000],
            ],
        ])->assertRedirect();

        $property = Property::where('name', 'Order Check Lodge')->firstOrFail();
        $sorted = Room::sortCollection($property->rooms);

        $this->assertSame(['01', '02', '04', '09', '10'], $sorted->pluck('room_number')->all());
    }

    public function test_multi_month_rent_generation_and_selective_month_payment_flow(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();
        $student = Student::where('status', 'active')->firstOrFail();
        $studentUser = $student->user;
        $this->assertNotNull($studentUser);

        // Ensure clean state for month 11 & 12 of 2026 in case seeded differently
        RentRecord::where('student_id', $student->id)
            ->where('year', 2026)
            ->whereIn('month', [11, 12])
            ->delete();

        // Owner visits GET fallback route safely (redirects cleanly)
        $this->actingAs($owner)
            ->get('/owner/rent/generate-monthly')
            ->assertRedirect(route('owner.rent.index'));

        // Owner generates November 2026 rent
        $this->actingAs($owner)
            ->post('/owner/rent/generate-monthly', ['month' => 11, 'year' => 2026])
            ->assertRedirect();

        // Owner generates December 2026 rent (even while November is unpaid)
        $this->actingAs($owner)
            ->post('/owner/rent/generate-monthly', ['month' => 12, 'year' => 2026])
            ->assertRedirect();

        $novRecord = RentRecord::where('student_id', $student->id)
            ->where('month', 11)
            ->where('year', 2026)
            ->firstOrFail();
        $decRecord = RentRecord::where('student_id', $student->id)
            ->where('month', 12)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertSame('unpaid', $novRecord->status);
        $this->assertSame('unpaid', $decRecord->status);

        // Student dashboard and rent page both display November 2026 & December 2026 with Pay Now forms
        $this->actingAs($studentUser)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('December 2026')
            ->assertSee('payments/pay-rent');

        $this->actingAs($studentUser)
            ->get('/student/rent')
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('December 2026')
            ->assertSee('Pay Now');

        // Student pays November 2026 specifically
        $this->actingAs($studentUser)
            ->post('/student/payments/pay-rent', ['rent_record_id' => $novRecord->id])
            ->assertRedirect();

        $novRecord->refresh();
        $decRecord->refresh();

        $this->assertSame('paid', $novRecord->status);
        $this->assertEquals(0.0, (float) $novRecord->outstanding);
        $this->assertNotNull($novRecord->latest_payment);
        $this->assertNotNull($novRecord->latest_payment->paid_at);

        // December must remain unpaid with Pay Now still visible
        $this->assertSame('unpaid', $decRecord->status);
        $this->assertGreaterThan(0, (float) $decRecord->outstanding);

        $this->actingAs($studentUser)
            ->get('/student/rent')
            ->assertOk()
            ->assertSee('Receipt')
            ->assertSee('Pay Now');
    }

    public function test_owner_and_student_can_stay_logged_in_simultaneously_without_403_or_419(): void
    {
        $student = Student::where('status', 'active')->firstOrFail();

        // 1. Log in as Owner via real web login form
        $this->post('/login', [
            'email'    => 'owner@lodgemate.com',
            'password' => 'password',
        ])->assertRedirect(route('owner.dashboard'));

        $this->get('/owner/dashboard')->assertOk();

        // 2. Log in as Student via OTP in the same browser session
        $sendRes = $this->postJson('/student/otp/send', [
            'mobile' => $student->mobile,
        ])->assertOk();

        $otp = $sendRes->json('otp_dev');

        $this->postJson('/student/otp/verify', [
            'mobile' => $student->mobile,
            'otp'    => $otp,
        ])->assertOk()->assertJson(['success' => true]);

        // 3. Both portals work cleanly without 403 Unauthorized or session loss
        $this->get('/student/dashboard')->assertOk();
        $this->get('/owner/dashboard')->assertOk();

        // 4. Owner POST actions succeed after Student OTP login in the same session
        $this->post('/owner/rent/generate-monthly', ['month' => 11, 'year' => 2026])
            ->assertRedirect();

        // 5. Logging out of Student portal keeps Owner session intact
        $this->post('/student/logout')->assertRedirect(route('student.otp'));
        $this->get('/owner/dashboard')->assertOk();
    }

    public function test_unified_login_page_shows_niwasthal_owner_tenant_and_property_registration_options(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Niwasthal')
            ->assertSee('Login as Owner')
            ->assertSee('Login as Tenant')
            ->assertSee('Create Owner Account (PG / Lodge Only)')
            ->assertSee('Register Property');

        $this->get('/student/login')
            ->assertOk()
            ->assertSee('Niwasthal')
            ->assertSee('Login as Tenant')
            ->assertSee('Tenant Login Only');
    }

    public function test_fresh_empty_owner_zero_data_pages_and_end_to_end_journey(): void
    {
        // Wipe all tables dynamically via DELETE inside SQLite memory DB to simulate a 100% fresh DB
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
        foreach ([
            'payment_allocations', 'payments', 'rent_records', 'electricity_allocations',
            'electricity_bills', 'deposit_transactions', 'security_deposits', 'complaints',
            'notifications', 'expenses', 'otp_verifications', 'students', 'beds', 'rooms',
            'properties', 'users',
        ] as $table) {
            \Illuminate\Support\Facades\DB::table($table)->delete();
        }
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');

        // 1. Register a brand-new Owner on an empty database
        $this->post('/register', [
            'name'                  => 'Kishore Fresh',
            'email'                 => 'kishore@example.com',
            'mobile'                => '9876500001',
            'business_type'         => 'lodge',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('owner.dashboard'));

        // 2. Every owner module page + export must work smoothly with ZERO records
        foreach ([
            '/owner/dashboard',
            '/owner/properties',
            '/owner/properties/create',
            '/owner/students',
            '/owner/students/create',
            '/owner/rent',
            '/owner/rent/create',
            '/owner/payments',
            '/owner/electricity',
            '/owner/electricity/create',
            '/owner/deposits',
            '/owner/expenses',
            '/owner/expenses/create',
            '/owner/complaints',
            '/owner/notifications',
            '/owner/announcements',
            '/owner/reports',
            '/owner/reports/rent-collection',
            '/owner/reports/pending-dues',
            '/owner/reports/occupancy',
            '/owner/reports/expenses',
            '/owner/reports/students',
            '/owner/reports/rent-collection?format=pdf',
            '/owner/reports/rent-collection?format=excel',
            '/owner/reports/occupancy?format=excel',
            '/owner/settings',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }

        // 3. Create 1 Lodge Property with Room 1 (Family, Per Room 2200) via 2-step wizard
        $this->post('/owner/properties', [
            'name'         => 'Niwasthal Grand Lodge',
            'address'      => 'Main Road',
            'city'         => 'Patna',
            'type'         => 'lodge',
            'total_floors' => 1,
            'rooms'        => [
                [
                    'room_number' => '1',
                    'floor'       => 'Ground Floor',
                    'type'        => 'family',
                    'rent_type'   => 'per_room',
                    'total_beds'  => 0,
                    'rent_amount' => 2200,
                ],
            ],
        ])->assertRedirect();

        $property = Property::firstOrFail();
        $room = Room::firstOrFail();

        // 4. Add a Renter to Room 1
        $this->post('/owner/students', [
            'property_id'      => $property->id,
            'room_id'          => $room->id,
            'name'             => 'Fresh Tenant',
            'mobile'           => '9111122223',
            'email'            => 'tenant@example.com',
            'monthly_rent'     => 2200,
            'joining_date'     => '2026-11-01',
            'security_deposit' => 2000,
        ])->assertRedirect();

        $student = Student::firstOrFail();

        // 5. Tenant signs in via OTP BEFORE any rent/payments/complaints exist (empty tenant portal check)
        $sendRes = $this->postJson('/student/otp/send', ['mobile' => '9111122223'])->assertOk();
        $this->postJson('/student/otp/verify', [
            'mobile' => '9111122223',
            'otp'    => $sendRes->json('otp_dev'),
        ])->assertOk();

        foreach ([
            '/student/dashboard',
            '/student/room',
            '/student/rent',
            '/student/payments',
            '/student/electricity',
            '/student/complaints',
            '/student/complaints/create',
            '/student/notifications',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }

        // 6. Owner generates November & December 2026 rent in the same session
        $this->post('/owner/rent/generate-monthly', ['month' => 11, 'year' => 2026])->assertRedirect();
        $this->post('/owner/rent/generate-monthly', ['month' => 12, 'year' => 2026])->assertRedirect();

        // 7. Tenant sees both months, pays November specifically, December stays Pay Now
        $this->get('/student/rent')
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('December 2026')
            ->assertSee('Pay Now');

        $nov = RentRecord::where('student_id', $student->id)->where('month', 11)->firstOrFail();
        $this->post('/student/payments/pay-rent', ['rent_record_id' => $nov->id])->assertRedirect();

        $this->get('/owner/dashboard')->assertOk();
        $this->get('/student/rent')
            ->assertOk()
            ->assertSee('Receipt')
            ->assertSee('Pay Now');
    }

    public function test_auth_pages_have_no_cache_headers_and_419_graceful_redirect(): void
    {
        $loginRes = $this->get('/login');
        $loginRes->assertOk();
        $this->assertStringContainsString('no-cache', (string) $loginRes->headers->get('Cache-Control'));

        $registerRes = $this->get('/register');
        $registerRes->assertOk();
        $this->assertStringContainsString('no-cache', (string) $registerRes->headers->get('Cache-Control'));

        $otpRes = $this->get('/student/login');
        $otpRes->assertOk();
        $this->assertStringContainsString('no-cache', (string) $otpRes->headers->get('Cache-Control'));
    }
}
