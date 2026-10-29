<?php

define('SMTP_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('MAIL_PORT') ?: 587));
define('SMTP_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('MAIL_FROM_ADDRESS') ?: '');
define('SMTP_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Romy and Allit Food Haven');
