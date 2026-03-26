# Guide: Configuring Email in XAMPP

Since you are using XAMPP, the PHP `mail()` function requires a local mail transfer agent. Here is how to configure it to use Gmail's SMTP server.

## Step 1: Configure `php.ini`
1. Open your XAMPP Control Panel.
2. Click **Config** next to Apache and select `php.ini`.
3. Search for `[mail function]` (usually around line 1000).
4. Update the settings as follow:
   ```ini
   SMTP=smtp.gmail.com
   smtp_port=584
   sendmail_from = your-email@gmail.com
   sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
   ```
5. Save and close.

## Step 2: Configure `sendmail.ini`
1. Go to [C:\xampp\sendmail\sendmail.ini](file:///xampp/sendmail/sendmail.ini).
2. Update the settings as follow:
   ```ini
   smtp_server=smtp.gmail.com
   smtp_port=587
   error_log_file=error.log
   debug_log_file=debug.log
   auth_username=your-email@gmail.com
   auth_password=your-app-password
   force_sender=your-email@gmail.com
   ```
   > [!IMPORTANT]
   > For `auth_password`, do not use your regular Gmail password. You must generate an **App Password** from your Google Account security settings (2FA must be enabled).

## Step 3: Restart Apache
Restart Apache in the XAMPP Control Panel for changes to take effect.

## Testing
You can now try assigning a shipper in the Admin panel. If the configuration is correct, the customer will receive an email.
Check `C:\xampp\sendmail\error.log` if things are not working.
