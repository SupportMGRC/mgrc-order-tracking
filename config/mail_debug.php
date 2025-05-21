<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Debug Configuration
    |--------------------------------------------------------------------------
    |
    | This file is used for debugging mail issues. It allows us to override the
    | mail configuration without modifying the .env file.
    |
    */

    // Set to true to enable PHP's mail() function as a fallback (recommended)
    'use_php_mail_fallback' => true,
    
    // Set to true to log email content for debugging
    'log_email_content' => false,
    
    // Alternate SMTP settings if Office 365 doesn't work
    'test_smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '', // Fill in your Gmail address here if needed
        'password' => '', // Fill in your Gmail app password here if needed
    ],
    
    // Set to true to use the test SMTP settings above instead of .env settings
    'use_test_smtp' => false,
    
    // Default recipients for testing (will only be used if test_mode is true)
    'test_recipients' => [
        'cell_lab_test@example.com',
        'quality_test@example.com',
    ],
    
    // Set to true to send to test recipients only
    'test_mode' => false,
]; 