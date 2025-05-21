# Email Notification System for MGRC Order Tracking

This document provides information about the email notification system for new orders in the MGRC Order Tracking application.

## Overview

When a new order is placed, the system automatically sends email notifications to:
- Cell Lab department staff
- Quality department staff

The email contains all relevant order details including customer information, products ordered, delivery date/time, and any remarks.

## Configuration

Email settings are configured in the `.env` file. Make sure the following settings are properly configured:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=support@mgrc.com.my
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=support@mgrc.com.my
MAIL_FROM_NAME="MGRC Order System"
```

## Troubleshooting Email Issues

If emails are not being delivered properly:

1. Make sure users have the correct department assignment (Cell Lab or Quality)
2. Verify all users have valid email addresses
3. Visit `/test-email` in your browser to test and diagnose email issues
4. Check the Laravel log file (`storage/logs/laravel.log`) for any errors

## Alternative Email Methods

The system uses multiple methods to ensure emails are delivered:

1. Primary method: Office 365 SMTP 
2. Fallback method: PHP's built-in mail() function
3. Alternate SMTP: Configure in `config/mail_debug.php` if needed

## Email Recipients

To ensure emails are sent to the right people:

1. Users must be assigned to either the "Cell Lab" or "Quality" department
2. Each user must have a valid email address
3. Update user information in the user management section of the application 