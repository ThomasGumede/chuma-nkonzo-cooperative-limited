# Contact Form Setup Guide

This document explains how to set up and configure the contact form with Google reCAPTCHA v3 and AJAX functionality.

## Files Created/Modified

1. **assets/mailer.php** - Server-side handler for form submissions
2. **assets/js/contact-form-handler.js** - Client-side AJAX handler
3. **contact-chuma-nkonzo.html** - Updated HTML form with reCAPTCHA

## Step 1: Get Google reCAPTCHA v3 Keys

1. Go to [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Sign in with your Google account
3. Click **Create** or **+** to create a new site
4. Fill in the form:
   - **Label**: Chuma Nkozo Contact Form
   - **reCAPTCHA type**: reCAPTCHA v3
   - **Domains**: Add your domain (e.g., chumankozo.co.za, www.chumankozo.co.za)
5. Accept the terms and click **Submit**
6. Copy your **Site Key** and **Secret Key**

## Step 2: Update Configuration Files

### In contact-chuma-nkonzo.html

Find this line (around line 585):

```html
<div
  class="g-recaptcha"
  data-sitekey="YOUR_RECAPTCHA_SITE_KEY_HERE"
  style="margin: 15px 0;"
></div>
```

Replace `YOUR_RECAPTCHA_SITE_KEY_HERE` with your actual Site Key from Google reCAPTCHA.

### In assets/js/contact-form-handler.js

Find this line (around line 34):

```javascript
grecaptcha.execute('YOUR_RECAPTCHA_SITE_KEY_HERE', {action: 'submit'}).then(function(token) {
```

Replace `YOUR_RECAPTCHA_SITE_KEY_HERE` with your actual Site Key from Google reCAPTCHA.

### In assets/mailer.php

Find these lines (around lines 8-11):

```php
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY_HERE');
define('RECIPIENT_EMAIL', 'info@chuma-nkonzo.com');
define('MAIL_FROM', 'noreply@chuma-nkonzo.com');
```

Update:

- `RECAPTCHA_SECRET_KEY` - Your Secret Key from Google reCAPTCHA
- `RECIPIENT_EMAIL` - Your email address where form submissions will be sent
- `MAIL_FROM` - The sender email address (must be a valid domain email)

## Step 3: PHP Server Requirements

Make sure your server has:

- PHP 7.0 or higher
- cURL extension enabled (for reCAPTCHA verification)
- mail() function enabled for email sending

## Step 4: Test the Form

1. Visit the contact page
2. Fill out the form completely
3. Submit the form
4. You should see a success message
5. Check your email inbox for the submission

## Form Fields

The form includes:

- **Name** - Required, text input
- **Email** - Required, valid email format
- **Phone** - Required, at least 7 characters
- **Subject** - Dropdown selection
- **Message** - Required, at least 10 characters
- **reCAPTCHA v3** - Automatic validation

## Features

### Client-Side Validation

- Checks all required fields are filled
- Validates email format
- Validates phone length
- Validates message length
- Provides user-friendly error messages

### reCAPTCHA v3

- Runs invisibly in the background
- Detects bots automatically
- No user interaction required
- Score-based verification (0.5+ required)

### AJAX Submission

- No page reload on submission
- Progress indication while sending
- Success/error notifications
- Auto-reset form on success

### Server-Side Security

- Input sanitization and validation
- reCAPTCHA token verification
- HTML email formatting
- Confirmation email to subscriber
- Error logging

## Email Customization

### Admin Email (assets/mailer.php)

The admin email includes:

- Formatted HTML layout
- All form field values
- Timestamp of submission
- Reply-to address set to user's email

### Confirmation Email to User

The user receives a confirmation email with:

- Thank you message
- Their submission details
- Submission timestamp

Both email templates are in the `mailer.php` file and can be customized as needed.

## Troubleshooting

### Form won't submit

- Check that your Site Key and Secret Key are correct
- Ensure domain is added to reCAPTCHA admin console
- Check that mail() is enabled on server
- Check server error logs

### reCAPTCHA verification fails

- Verify Secret Key is correct
- Check domain matches reCAPTCHA settings
- Ensure cURL is enabled on server

### Emails not received

- Check RECIPIENT_EMAIL configuration
- Verify mail server is working
- Check spam/junk folders
- Check server error logs: `error_log()`

### Console errors

- Open browser DevTools (F12)
- Check Console tab for JavaScript errors
- Verify all script sources are loading

## Security Notes

1. **Never commit API keys** - Store keys in environment variables for production
2. **HTTPS required** - reCAPTCHA v3 requires HTTPS on production
3. **IP validation** - Consider implementing IP-based rate limiting
4. **Rate limiting** - Add rate limiting to prevent spam
5. **Database logging** - Consider logging submissions to database

## Production Recommendations

1. Move API keys to environment variables (.env file)
2. Implement rate limiting
3. Add database logging for submissions
4. Enable HTTPS (required for reCAPTCHA v3)
5. Set up proper SPF/DKIM records for email sending
6. Monitor error logs regularly
7. Add additional spam filters if needed

## Support

For issues with:

- **reCAPTCHA**: Visit [Google reCAPTCHA Docs](https://developers.google.com/recaptcha/docs/v3)
- **PHP Mail**: Check your hosting provider's documentation
- **AJAX**: Check browser console (F12 → Console tab)
