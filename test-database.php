
<?php

try {
    $mysql = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=romy_allit_food_haven_laravel;charset=utf8mb4',
        'root',
        ''
    );

    echo "LOCAL MYSQL: CONNECTED\n";
} catch (PDOException $e) {
    echo "LOCAL MYSQL ERROR: " . $e->getMessage() . "\n";
}

try {
    $postgres = new PDO(
        'pgsql:host=' . getenv('DB_HOST') .
        ';port=' . getenv('DB_PORT') .
        ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );

    echo "SUPABASE: CONNECTED\n";
} catch (PDOException $e) {
    echo "SUPABASE ERROR: " . $e->getMessage() . "\n";
}
