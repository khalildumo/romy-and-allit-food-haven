
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Email Authorization</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 700px; margin: 50px auto; padding: 20px;">
    <h2>Google Authorization Successful!</h2>

    <p>Copy the refresh token below and save it in your Render environment variables.</p>

    <p><strong>Variable name:</strong> GOOGLE_REFRESH_TOKEN</p>

    <textarea id="refreshToken" readonly
        style="width: 100%; height: 120px; padding: 10px;">{{ $refreshToken }}</textarea>

    <button onclick="copyToken()" style="padding: 10px 20px; margin-top: 10px;">
        Copy Refresh Token
    </button>

    <p id="message"></p>

    <script>
        function copyToken() {
            const token = document.getElementById('refreshToken');

            navigator.clipboard.writeText(token.value).then(() => {
                document.getElementById('message').textContent =
                    'Token copied. Keep it private!';
            });
        }
    </script>

    <p><strong>Security:</strong> Do not share this token or publish it. Anyone with it may be able to send email through your connected Gmail account.</p>
</body>
</html>
