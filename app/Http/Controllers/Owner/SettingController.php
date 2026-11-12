<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    /**
     * The list of settings managed by this controller, with their
     * expected types and default values.
     */
    private array $settingsConfig = [
        'pg_name'              => ['type' => 'string',  'default' => ''],
        'late_fee_amount'      => ['type' => 'numeric', 'default' => 0],
        'due_day'              => ['type' => 'integer', 'default' => 5],
        'rent_reminder_days'   => ['type' => 'integer', 'default' => 3],
        'whatsapp_enabled'     => ['type' => 'boolean', 'default' => false],
        'whatsapp_api_url'     => ['type' => 'string',  'default' => ''],
        'whatsapp_api_token'   => ['type' => 'string',  'default' => ''],
        'currency_symbol'      => ['type' => 'string',  'default' => '₹'],
        'timezone'             => ['type' => 'string',  'default' => 'Asia/Kolkata'],
        'email_notifications'  => ['type' => 'boolean', 'default' => true],
    ];

    /**
     * Show the settings form with current values.
     */
    public function index()
    {
        $owner = Auth::user();

        // Load all current settings with their defaults
        $settings = [];
        foreach ($this->settingsConfig as $key => $config) {
            $settings[$key] = Setting::get($owner->id, $key, $config['default']);
        }

        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

        return view('owner.settings.index', compact('settings', 'timezones'));
    }

    /**
     * Save all settings using Setting::set().
     */
    public function update(Request $request)
    {
        $owner = Auth::user();

        $rules = [
            'pg_name'            => 'nullable|string|max:255',
            'late_fee_amount'    => 'nullable|numeric|min:0|max:99999',
            'due_day'            => 'nullable|integer|min:1|max:28',
            'rent_reminder_days' => 'nullable|integer|min:0|max:30',
            'whatsapp_enabled'   => 'nullable|boolean',
            'whatsapp_api_url'   => 'nullable|url|max:500',
            'whatsapp_api_token' => 'nullable|string|max:500',
            'currency_symbol'    => 'nullable|string|max:10',
            'timezone'           => 'nullable|string|timezone',
            'email_notifications'=> 'nullable|boolean',
        ];

        $validated = $request->validate($rules);

        foreach ($this->settingsConfig as $key => $config) {
            $value = match ($config['type']) {
                'boolean' => (bool) $request->boolean($key),
                'integer' => isset($validated[$key]) ? (int) $validated[$key] : (int) $config['default'],
                'numeric' => isset($validated[$key]) ? (float) $validated[$key] : (float) $config['default'],
                default   => $validated[$key] ?? $config['default'],
            };

            Setting::set($owner->id, $key, $value);
        }

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Settings saved successfully.');
    }

    /**
     * Update the owner's account password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Password updated successfully.');
    }
}
