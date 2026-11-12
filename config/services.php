<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business Cloud API
    |--------------------------------------------------------------------------
    |
    | Credentials for Meta's WhatsApp Business Cloud API (Graph API).
    |
    | WHATSAPP_API_TOKEN          - Permanent or temporary access token from
    |                               Meta Developer Dashboard.
    | WHATSAPP_PHONE_NUMBER_ID    - Phone Number ID from WhatsApp Business settings.
    | WHATSAPP_BUSINESS_ACCOUNT_ID - WhatsApp Business Account (WABA) ID.
    | WHATSAPP_API_VERSION        - Graph API version, e.g. v18.0
    |
    */

    'whatsapp' => [
        'api_token'           => env('WHATSAPP_API_TOKEN'),
        'phone_number_id'     => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'api_version'         => env('WHATSAPP_API_VERSION', 'v18.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Razorpay Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Credentials for the Razorpay payment gateway.
    |
    | RAZORPAY_KEY_ID     - Public API key (starts with rzp_live_ or rzp_test_)
    | RAZORPAY_KEY_SECRET - Secret API key — keep this out of version control.
    |
    */

    'razorpay' => [
        'key_id'     => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
    ],

];
