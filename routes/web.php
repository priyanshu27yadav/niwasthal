<?php

use App\Http\Controllers\Auth\OwnerAuthController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\Owner\BedController;
use App\Http\Controllers\Owner\ComplaintController as OwnerComplaintController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\DepositController;
use App\Http\Controllers\Owner\ElectricityController as OwnerElectricityController;
use App\Http\Controllers\Owner\ExpenseController;
use App\Http\Controllers\Owner\NotificationController as OwnerNotificationController;
use App\Http\Controllers\Owner\PaymentController as OwnerPaymentController;
use App\Http\Controllers\Owner\PropertyController;
use App\Http\Controllers\Owner\RentController as OwnerRentController;
use App\Http\Controllers\Owner\ReportController;
use App\Http\Controllers\Owner\RoomController;
use App\Http\Controllers\Owner\SettingController;
use App\Http\Controllers\Owner\StudentController;
use App\Http\Controllers\Student\ComplaintController as StudentComplaintController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ElectricityController as StudentElectricityController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\PaymentController as StudentPaymentController;
use App\Http\Controllers\Student\RentController as StudentRentController;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    $ownerLoggedIn = auth('owner')->check() || (auth('web')->check() && auth('web')->user()->isOwner());
    $studentLoggedIn = auth('student')->check() || (auth('web')->check() && auth('web')->user()->isStudent());

    if ($ownerLoggedIn && !$studentLoggedIn) {
        return redirect()->route('owner.dashboard');
    }

    if ($studentLoggedIn && !$ownerLoggedIn) {
        return redirect()->route('student.dashboard');
    }

    return view('welcome');
})->name('home');

// Owner Authentication Routes
Route::get('/login', [OwnerAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [OwnerAuthController::class, 'login'])->name('login.post');
Route::get('/register', [OwnerAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [OwnerAuthController::class, 'register'])->name('register.post');
Route::post('/logout', [OwnerAuthController::class, 'logout'])->name('logout');

// Student OTP Authentication Routes
Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [StudentAuthController::class, 'showOtpPage'])->name('otp');
    Route::post('/otp/send', [StudentAuthController::class, 'sendOtp'])->name('otp.send');
    Route::post('/otp/verify', [StudentAuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/logout', [StudentAuthController::class, 'logout'])->name('logout');
});

// Razorpay Webhook (CSRF excluded in bootstrap/app.php)
Route::post('/webhooks/razorpay', [StudentPaymentController::class, 'webhook'])->name('webhooks.razorpay');

// ============================================================================
// OWNER PORTAL ROUTES
// ============================================================================
Route::middleware(['role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');

    // Properties
    Route::resource('properties', PropertyController::class);

    // Rooms & Beds (Nested under Property)
    Route::resource('properties.rooms', RoomController::class);
    Route::get('properties/{property}/beds', [BedController::class, 'index'])->name('properties.beds.index');
    Route::post('properties/{property}/beds/{bed}/toggle', [BedController::class, 'toggle'])->name('properties.beds.toggle');

    // Students
    Route::resource('students', StudentController::class);
    Route::post('students/{student}/generate-rent', [StudentController::class, 'generateRent'])->name('students.generate-rent');

    // Rent Management
    Route::get('rent', [OwnerRentController::class, 'index'])->name('rent.index');
    Route::get('rent/create', [OwnerRentController::class, 'create'])->name('rent.create');
    Route::post('rent', [OwnerRentController::class, 'store'])->name('rent.store');
    Route::get('rent/generate-monthly', fn () => redirect()->route('owner.rent.index'));
    Route::post('rent/generate-monthly', [OwnerRentController::class, 'generateMonthly'])->name('rent.generate-monthly');
    Route::get('rent/{rent}', [OwnerRentController::class, 'show'])->name('rent.show');
    Route::post('rent/{rent}/pay', [OwnerRentController::class, 'recordPayment'])->name('rent.pay');

    // Payments
    Route::get('payments', [OwnerPaymentController::class, 'index'])->name('payments.index');
    Route::post('payments', [OwnerPaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}/receipt', [OwnerPaymentController::class, 'receipt'])->name('payments.receipt');
    Route::delete('payments/{payment}', [OwnerPaymentController::class, 'destroy'])->name('payments.destroy');

    // Electricity Bills
    Route::get('electricity', [OwnerElectricityController::class, 'index'])->name('electricity.index');
    Route::get('electricity/create', [OwnerElectricityController::class, 'create'])->name('electricity.create');
    Route::post('electricity', [OwnerElectricityController::class, 'store'])->name('electricity.store');
    Route::get('electricity/{electricity}', [OwnerElectricityController::class, 'show'])->name('electricity.show');
    Route::post('electricity/{electricity}/generate-allocations', [OwnerElectricityController::class, 'generateAllocations'])->name('electricity.generate-allocations');
    Route::post('electricity/allocations/{allocation}/mark-paid', [OwnerElectricityController::class, 'markPaid'])->name('electricity.allocations.mark-paid');
    Route::delete('electricity/{electricity}', [OwnerElectricityController::class, 'destroy'])->name('electricity.destroy');

    // Security Deposits
    Route::get('deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::post('deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::get('deposits/{deposit}', [DepositController::class, 'show'])->name('deposits.show');
    Route::post('deposits/{deposit}/transactions', [DepositController::class, 'addTransaction'])->name('deposits.transactions.store');

    // Expenses
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    // Complaints
    Route::get('complaints', [OwnerComplaintController::class, 'index'])->name('complaints.index');
    Route::get('complaints/{complaint}', [OwnerComplaintController::class, 'show'])->name('complaints.show');
    Route::post('complaints/{complaint}/status', [OwnerComplaintController::class, 'updateStatus'])->name('complaints.update-status');
    Route::delete('complaints/{complaint}', [OwnerComplaintController::class, 'destroy'])->name('complaints.destroy');

    // Notifications & Announcements
    Route::get('notifications', [OwnerNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [OwnerNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/mark-all-read', [OwnerNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::get('announcements', [OwnerNotificationController::class, 'announcements'])->name('notifications.announcements');
    Route::post('announcements', [OwnerNotificationController::class, 'sendAnnouncement'])->name('notifications.announcements.store');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/rent-collection', [ReportController::class, 'rentCollection'])->name('reports.rent-collection');
    Route::get('reports/pending-dues', [ReportController::class, 'pendingDues'])->name('reports.pending-dues');
    Route::get('reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');
    Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('reports/students', [ReportController::class, 'students'])->name('reports.students');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/password', [SettingController::class, 'updatePassword'])->name('settings.password');
});

// ============================================================================
// STUDENT PORTAL ROUTES
// ============================================================================
Route::middleware(['role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/room', [StudentDashboardController::class, 'room'])->name('room');

    // Rent
    Route::get('/rent', [StudentRentController::class, 'index'])->name('rent.index');
    Route::get('/rent/{rent}', [StudentRentController::class, 'show'])->name('rent.show');

    // Payments & Razorpay
    Route::get('/payments', [StudentPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/order', [StudentPaymentController::class, 'createOrder'])->name('payments.order');
    Route::post('/payments/verify', [StudentPaymentController::class, 'verifyPayment'])->name('payments.verify');
    Route::post('/payments/pay-rent', [StudentPaymentController::class, 'payMonth'])->name('payments.pay-rent');
    Route::get('/payments/{payment}/receipt', [StudentPaymentController::class, 'receipt'])->name('payments.receipt');

    // Electricity
    Route::get('/electricity', [StudentElectricityController::class, 'index'])->name('electricity.index');

    // Complaints
    Route::get('/complaints', [StudentComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [StudentComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [StudentComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [StudentComplaintController::class, 'show'])->name('complaints.show');

    // Notifications
    Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [StudentNotificationController::class, 'markRead'])->name('notifications.read');
});
