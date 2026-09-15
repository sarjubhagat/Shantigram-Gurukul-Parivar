<?php

return [

    'unique_id_start' => 8001,

    'photo' => [
        'folder'         => env('PARIVAR_PHOTO_R2_FOLDER', 'parivar-photos'),
        'update_folder'  => env('PARIVAR_UPDATE_PHOTO_R2_FOLDER', 'parivar-update-photos'),
        'max_kb'         => (int) env('PARIVAR_PHOTO_MAX_KB', 4096),
        'signed_url_ttl' => (int) env('PARIVAR_PHOTO_SIGNED_TTL', 120),
    ],

    'otp' => [
        'test_mode'  => (bool) env('PARIVAR_OTP_TEST_MODE', false),
        'test_otp'   => env('PARIVAR_OTP_TEST_CODE', '12345'),
        'send_url'   => env('PARIVAR_OTP_SEND_URL', 'https://hostel.shantigramgurukul.org/api/message/send-otp'),
        'hash'       => env('PARIVAR_OTP_HASH', 'DKDKJIEJGOD'),
        'ttl_minutes'=> (int) env('PARIVAR_OTP_TTL', 10),
    ],

    'whatsapp' => [
        'send_url' => env(
            'PARIVAR_WHATSAPP_SEND_URL',
            'https://hostel.shantigramgurukul.org/api/whatsapp/send-message'
        ),
        'api_code' => env('PARIVAR_WHATSAPP_API_CODE', 'GH-BDY-7K9M2X4P8Q1R'),
        'birthday_recipients' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PARIVAR_BIRTHDAY_WHATSAPP_NUMBERS', '7575804441,9879206599,6359919885'))
        ))),
    ],

    'self_service' => [
        // English first / last / middle are locked on the form (internal use).
        // Members request name changes via member_note textarea.
        'locked_name_fields' => [
            'en_last_name',
            'en_first_name',
            'en_middle_name',
        ],
        'editable_fields' => [
            'display_name',
            'en_grandfather_name',
            'gu_first_name',
            'gu_last_name',
            'gu_middle_name',
            'gu_grandfather_name',
            'aadhaar_number',
            'marital_status',
            'date_of_birth',
            'gender',
            'c_code_1',
            'phone_1',
            'c_code_2',
            'phone_2',
            'native_place',
            'current_address',
            'is_gurukul_student',
        ],
    ],

    'genders' => [
        'male'   => 'Male',
        'female' => 'Female',
        'other'  => 'Other',
    ],

    'marital_statuses' => [
        'single'   => 'Single',
        'married'  => 'Married',
        'widowed'  => 'Widowed',
        'divorced' => 'Divorced',
    ],

    'country_codes' => [
        '+91'  => 'India (+91)',
        '+1'   => 'USA / Canada (+1)',
        '+44'  => 'United Kingdom (+44)',
        '+971' => 'UAE (+971)',
        '+61'  => 'Australia (+61)',
        '+64'  => 'New Zealand (+64)',
        '+65'  => 'Singapore (+65)',
        '+60'  => 'Malaysia (+60)',
        '+66'  => 'Thailand (+66)',
        '+977' => 'Nepal (+977)',
        '+880' => 'Bangladesh (+880)',
        '+94'  => 'Sri Lanka (+94)',
        '+92'  => 'Pakistan (+92)',
        '+27'  => 'South Africa (+27)',
        '+254' => 'Kenya (+254)',
        '+233' => 'Ghana (+233)',
        '+234' => 'Nigeria (+234)',
        '+20'  => 'Egypt (+20)',
        '+966' => 'Saudi Arabia (+966)',
        '+974' => 'Qatar (+974)',
        '+973' => 'Bahrain (+973)',
        '+968' => 'Oman (+968)',
        '+965' => 'Kuwait (+965)',
        '+962' => 'Jordan (+962)',
        '+961' => 'Lebanon (+961)',
        '+972' => 'Israel (+972)',
        '+81'  => 'Japan (+81)',
        '+82'  => 'South Korea (+82)',
        '+86'  => 'China (+86)',
        '+852' => 'Hong Kong (+852)',
        '+853' => 'Macau (+853)',
        '+886' => 'Taiwan (+886)',
        '+62'  => 'Indonesia (+62)',
        '+63'  => 'Philippines (+63)',
        '+84'  => 'Vietnam (+84)',
        '+49'  => 'Germany (+49)',
        '+33'  => 'France (+33)',
        '+39'  => 'Italy (+39)',
        '+34'  => 'Spain (+34)',
        '+31'  => 'Netherlands (+31)',
        '+32'  => 'Belgium (+32)',
        '+41'  => 'Switzerland (+41)',
        '+46'  => 'Sweden (+46)',
        '+47'  => 'Norway (+47)',
        '+45'  => 'Denmark (+45)',
        '+358' => 'Finland (+358)',
        '+353' => 'Ireland (+353)',
        '+351' => 'Portugal (+351)',
        '+48'  => 'Poland (+48)',
        '+90'  => 'Turkey (+90)',
        '+7'   => 'Russia / Kazakhstan (+7)',
        '+55'  => 'Brazil (+55)',
        '+52'  => 'Mexico (+52)',
        '+54'  => 'Argentina (+54)',
        '+56'  => 'Chile (+56)',
        '+57'  => 'Colombia (+57)',
        '+51'  => 'Peru (+51)',
    ],

];
