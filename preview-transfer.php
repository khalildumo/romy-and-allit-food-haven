
<?php

$mysql = new PDO(
    'mysql:host=127.0.0.1;dbname=romy_allit_food_haven_laravel;charset=utf8mb4',
    'root',
    ''
);

$postgres = new PDO(
    'pgsql:host=' . getenv('DB_HOST') .
    ';port=' . getenv('DB_PORT') .
    ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD')
);

$tables = [
    'cache',
    'cache_locks',
    'jobs',
    'job_batches',
    'failed_jobs',
    'migrations',
    'password_reset_tokens',
    'sessions',
];

foreach ($tables as $table) {
    echo "\n$table\n";

    $mysqlCount = $mysql->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    $pgCount = $postgres->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();

    echo "XAMPP: $mysqlCount | Supabase: $pgCount\n";
}
