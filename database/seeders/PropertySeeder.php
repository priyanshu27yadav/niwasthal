<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Bed;
use App\Models\Complaint;
use App\Models\DepositTransaction;
use App\Models\ElectricityAllocation;
use App\Models\ElectricityBill;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Room;
use App\Models\SecurityDeposit;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'owner@lodgemate.com')->firstOrFail();

        // Default Owner Settings
        Setting::set($owner->id, 'pg_name', 'Sunrise PG & Hostel');
        Setting::set($owner->id, 'due_day', 5);
        Setting::set($owner->id, 'late_fee_amount', 200);
        Setting::set($owner->id, 'rent_reminder_days', 3);
        Setting::set($owner->id, 'currency_symbol', '₹');

        // Property
        $property = Property::firstOrCreate(
            ['owner_id' => $owner->id, 'name' => 'Sunrise PG'],
            [
                'address'       => '123 MG Road, Indiranagar',
                'city'          => 'Bangalore',
                'state'         => 'Karnataka',
                'pincode'        => '560038',
                'contact_number' => '9876543210',
                'email'          => 'contact@sunrisepg.com',
                'type'           => 'pg',
                'total_floors'   => 2,
                'facilities'     => 'WiFi, Laundry, 24/7 Power Backup, CCTV, RO Water, Hot Water, Housekeeping',
                'total_rooms'    => 5,
                'is_active'      => true,
            ]
        );

        // Rooms & Beds
        $roomSpecs = [
            ['room_number' => 'R101', 'floor' => '1st Floor', 'type' => 'double',       'capacity' => 2, 'rent_amount' => 7500],
            ['room_number' => 'R102', 'floor' => '1st Floor', 'type' => 'triple',       'capacity' => 3, 'rent_amount' => 6000],
            ['room_number' => 'R103', 'floor' => '1st Floor', 'type' => 'single',       'capacity' => 1, 'rent_amount' => 10500],
            ['room_number' => 'R201', 'floor' => '2nd Floor', 'type' => 'double',       'capacity' => 2, 'rent_amount' => 7500],
            ['room_number' => 'R202', 'floor' => '2nd Floor', 'type' => 'four_sharing', 'capacity' => 4, 'rent_amount' => 5000],
        ];

        $allBeds = [];
        foreach ($roomSpecs as $spec) {
            $room = Room::firstOrCreate(
                ['property_id' => $property->id, 'room_number' => $spec['room_number']],
                [
                    'floor'       => $spec['floor'],
                    'type'        => $spec['type'],
                    'capacity'    => $spec['capacity'],
                    'rent_amount' => $spec['rent_amount'],
                    'is_active'   => true,
                ]
            );

            for ($i = 0; $i < $spec['capacity']; $i++) {
                $label = chr(65 + $i); // A, B, C, D
                $bed = Bed::firstOrCreate(
                    ['room_id' => $room->id, 'label' => $label],
                    ['is_occupied' => false]
                );
                $allBeds[] = ['bed' => $bed, 'room' => $room];
            }
        }

        // 8 Active Students
        $studentSpecs = [
            ['name' => 'Aarav Sharma',   'mobile' => '9000000001', 'email' => 'aarav@example.com',   'rent' => 7500,  'deposit' => 15000],
            ['name' => 'Priya Nair',     'mobile' => '9000000002', 'email' => 'priya@example.com',   'rent' => 7500,  'deposit' => 15000],
            ['name' => 'Rohan Verma',    'mobile' => '9000000003', 'email' => 'rohan@example.com',   'rent' => 6000,  'deposit' => 12000],
            ['name' => 'Sneha Patel',    'mobile' => '9000000004', 'email' => 'sneha@example.com',   'rent' => 6000,  'deposit' => 12000],
            ['name' => 'Vikram Reddy',   'mobile' => '9000000005', 'email' => 'vikram@example.com',  'rent' => 6000,  'deposit' => 12000],
            ['name' => 'Ananya Gupta',   'mobile' => '9000000006', 'email' => 'ananya@example.com',  'rent' => 10500, 'deposit' => 20000],
            ['name' => 'Karan Malhotra', 'mobile' => '9000000007', 'email' => 'karan@example.com',   'rent' => 7500,  'deposit' => 15000],
            ['name' => 'Meera Iyer',     'mobile' => '9000000008', 'email' => 'meera@example.com',   'rent' => 5000,  'deposit' => 10000],
        ];

        $createdStudents = [];
        foreach ($studentSpecs as $idx => $sSpec) {
            $studentUser = User::firstOrCreate(
                ['mobile' => $sSpec['mobile']],
                [
                    'name'      => $sSpec['name'],
                    'email'     => $sSpec['email'],
                    'role'      => 'student',
                    'owner_id'  => $owner->id,
                    'password'  => Hash::make($sSpec['mobile']),
                ]
            );

            $bedInfo = $allBeds[$idx];
            $bed     = $bedInfo['bed'];
            $room    = $bedInfo['room'];
            $bed->update(['is_occupied' => true]);

            $joiningDate = Carbon::now()->subMonths(4)->startOfMonth();

            $student = Student::firstOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'property_id'              => $property->id,
                    'bed_id'                   => $bed->id,
                    'room_id'                  => $room->id,
                    'name'                     => $sSpec['name'],
                    'mobile'                   => $sSpec['mobile'],
                    'email'                    => $sSpec['email'],
                    'address'                  => 'Bengaluru, Karnataka',
                    'city'                     => 'Bangalore',
                    'emergency_contact_name'   => 'Parent of ' . $sSpec['name'],
                    'emergency_contact_mobile' => '988888880' . $idx,
                    'joining_date'             => $joiningDate,
                    'monthly_rent'             => $sSpec['rent'],
                    'security_deposit'         => $sSpec['deposit'],
                    'status'                   => 'active',
                ]
            );

            $createdStudents[] = ['student' => $student, 'room' => $room];

            // Security Deposit
            $deposit = SecurityDeposit::firstOrCreate(
                ['student_id' => $student->id, 'property_id' => $property->id],
                [
                    'student_name' => $student->name,
                    'amount'       => $sSpec['deposit'],
                    'balance'      => $sSpec['deposit'],
                    'collected_on' => $joiningDate,
                    'status'       => 'held',
                ]
            );

            if ($deposit->wasRecentlyCreated) {
                DepositTransaction::create([
                    'deposit_id'       => $deposit->id,
                    'type'             => 'collect',
                    'amount'           => $sSpec['deposit'],
                    'reason'           => 'Initial security deposit on joining',
                    'transaction_date' => $joiningDate,
                    'reference'        => 'DEP-' . strtoupper(Str::random(6)),
                ]);
            }

            // Rent Records for last 3 months (including current month)
            for ($mOffset = 2; $mOffset >= 0; $mOffset--) {
                $targetDate = Carbon::now()->subMonths($mOffset);
                $m = (int) $targetDate->month;
                $y = (int) $targetDate->year;

                $existingRecord = RentRecord::where('student_id', $student->id)
                    ->where('month', $m)
                    ->where('year', $y)
                    ->first();

                if ($existingRecord) {
                    continue;
                }

                // Older 2 months mostly paid; current month mix of paid, partial, unpaid
                $isPaid    = $mOffset > 0 || ($idx % 3 === 0);
                $isPartial = !$isPaid && ($idx % 3 === 1);

                $paidAmt     = $isPaid ? $sSpec['rent'] : ($isPartial ? round($sSpec['rent'] / 2, 2) : 0);
                $outstanding = $sSpec['rent'] - $paidAmt;
                $status      = $outstanding <= 0 ? 'paid' : ($paidAmt > 0 ? 'partial' : 'unpaid');

                $rentRecord = RentRecord::create([
                    'student_id'   => $student->id,
                    'property_id'  => $property->id,
                    'student_name' => $student->name,
                    'room_number'  => $room->room_number,
                    'rent_amount'  => $sSpec['rent'],
                    'late_fee'     => 0,
                    'discount'     => 0,
                    'total_amount' => $sSpec['rent'],
                    'paid_amount'  => $paidAmt,
                    'outstanding'  => $outstanding,
                    'month'        => $m,
                    'year'         => $y,
                    'due_date'     => Carbon::create($y, $m, 5),
                    'status'       => $status,
                ]);

                if ($paidAmt > 0) {
                    $payment = Payment::create([
                        'student_id'     => $student->id,
                        'property_id'    => $property->id,
                        'student_name'   => $student->name,
                        'amount'         => $paidAmt,
                        'method'         => $idx % 2 === 0 ? 'upi' : 'bank_transfer',
                        'receipt_number' => 'LM-' . $y . str_pad($m, 2, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(5)),
                        'status'         => 'completed',
                        'paid_at'        => Carbon::create($y, $m, 4, 11, 30),
                    ]);

                    PaymentAllocation::create([
                        'payment_id'     => $payment->id,
                        'rent_record_id' => $rentRecord->id,
                        'amount'         => $paidAmt,
                    ]);
                }
            }
        }

        // 2 Expenses
        if (Expense::where('property_id', $property->id)->count() === 0) {
            Expense::create([
                'property_id'  => $property->id,
                'category'     => 'electricity',
                'description'  => 'BESCOM Monthly Power Bill',
                'amount'       => 8400,
                'expense_date' => Carbon::now()->subDays(10),
                'vendor'       => 'BESCOM',
            ]);
            Expense::create([
                'property_id'  => $property->id,
                'category'     => 'maintenance',
                'description'  => 'RO Water Purifier Servicing & Filter Replacement',
                'amount'       => 1850,
                'expense_date' => Carbon::now()->subDays(4),
                'vendor'       => 'AquaCare Services',
            ]);
        }

        // 2 Complaints
        if (Complaint::where('property_id', $property->id)->count() === 0 && count($createdStudents) >= 2) {
            Complaint::create([
                'student_id'  => $createdStudents[0]['student']->id,
                'property_id' => $property->id,
                'title'       => 'WiFi Router Dropping Connection on 1st Floor',
                'description' => 'The WiFi speed drops significantly after 8 PM in room R101.',
                'category'    => 'internet',
                'priority'    => 'high',
                'status'      => 'in_progress',
                'owner_response' => 'Technician scheduled for tomorrow morning.',
            ]);
            Complaint::create([
                'student_id'  => $createdStudents[2]['student']->id,
                'property_id' => $property->id,
                'title'       => 'Bathroom Tap Leaking in R102',
                'description' => 'The cold water tap in the attached washroom is dripping continuously.',
                'category'    => 'water',
                'priority'    => 'medium',
                'status'      => 'pending',
            ]);
        }

        // 1 Electricity Bill with Allocations
        $billMonth = (int) Carbon::now()->month;
        $billYear  = (int) Carbon::now()->year;
        $bill = ElectricityBill::firstOrCreate(
            ['property_id' => $property->id, 'month' => $billMonth, 'year' => $billYear],
            [
                'total_units'   => 960,
                'rate_per_unit' => 8.00,
                'fixed_charges' => 320,
                'total_amount'  => 8000,
                'split_type'    => 'equal',
                'status'        => 'generated',
                'notes'         => 'Monthly shared meter reading',
            ]
        );

        if ($bill->allocations()->count() === 0 && count($createdStudents) > 0) {
            $perStudentAmount = round(8000 / count($createdStudents), 2);
            $perStudentUnits  = round(960 / count($createdStudents), 2);
            foreach ($createdStudents as $i => $item) {
                $stu = $item['student'];
                $paid = $i < 3;
                ElectricityAllocation::create([
                    'bill_id'      => $bill->id,
                    'student_id'   => $stu->id,
                    'student_name' => $stu->name,
                    'units'        => $perStudentUnits,
                    'amount'       => $perStudentAmount,
                    'paid_amount'  => $paid ? $perStudentAmount : 0,
                    'is_paid'      => $paid,
                ]);
            }
        }

        // 1 Announcement
        if (Announcement::where('property_id', $property->id)->count() === 0) {
            Announcement::create([
                'property_id'  => $property->id,
                'owner_id'     => $owner->id,
                'title'        => 'Water Tank Cleaning Schedule',
                'body'         => 'Overhead water tank cleaning will take place this Sunday between 10:00 AM and 12:30 PM. Please store water accordingly.',
                'is_active'    => true,
                'published_at' => Carbon::now()->subDays(1),
            ]);
        }
    }
}
