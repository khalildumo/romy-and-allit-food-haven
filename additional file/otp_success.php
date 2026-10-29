
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['email_verified'])) {
    header('Location: send_otp.php');
    exit;
}

$email = $_SESSION['email_verified'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verified | Romy and Allit Food Haven</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: #f3f7f4;
            font-family: Arial, sans-serif;
        }

        .card {
            max-width: 450px;
            padding: 35px;
            text-align: center;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px #00000012;
        }

        .check {
            color: #198754;
            font-size: 60px;
        }

        h1 { color: #198754; }

        p { overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <main class="card">
        <div class="check">&#10004;</div>
        <h1>Email Verified!</h1>
        <p>Your email address has been verified successfully.</p>
        <p>
            <strong>
                <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
            </strong>
        </p>
        <p>You can now continue to the next step in your application.</p>
    </main>
</body>
</html>
