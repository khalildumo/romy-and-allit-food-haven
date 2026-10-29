# Gmail SMTP setup

The app sends account verification codes and password reset links through one Gmail sender account. Each customer receives the message at the email address they enter.

1. Enable 2-Step Verification on the sender Google account.
2. Create a Google App Password for this application. Use that 16-character password, not the account's normal password.
3. Set these environment variables for Apache before starting XAMPP:

   - `GMAIL_SMTP_USERNAME`: sender Gmail address
   - `GMAIL_SMTP_APP_PASSWORD`: Google App Password
   - `GMAIL_SMTP_FROM`: optional sender address; defaults to `GMAIL_SMTP_USERNAME`

For a local XAMPP install, add `SetEnv GMAIL_SMTP_USERNAME "sender@gmail.com"` and `SetEnv GMAIL_SMTP_APP_PASSWORD "your-app-password"` to Apache's configuration, then restart Apache. Keep the credentials out of the project files and source control. Gmail SMTP uses `smtp.gmail.com` on port 587 with STARTTLS.
