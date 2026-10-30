
<?php

$mysql = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=romy_allit_food_haven_laravel;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$postgres = new PDO(
    'pgsql:host=' . getenv('DB_HOST') .
    ';port=' . getenv('DB_PORT') .
    ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = [
    'users' => [
        'id', 'full_name', 'username', 'password_hash', 'role',
        'institution_id', 'email', 'email_verified', 'phone',
        'remember_token', 'created_at', 'updated_at'
    ],
    'product_categories' => [
        'id', 'name', 'created_at', 'updated_at'
    ],
    'menu_products' => [
        'id', 'name', 'category', 'description', 'price',
        'image_path', 'is_available', 'stock_quantity',
        'stock_enabled', 'created_at', 'updated_at'
    ],
    'canteen_profile' => [
        'id', 'canteen_name', 'operating_hours', 'contact_info',
        'pickup_location', 'created_at', 'updated_at'
    ],
];

try {
    $postgres->beginTransaction();

    foreach ($tables as $table => $columns) {
        $count = (int) $postgres
            ->query("SELECT COUNT(*) FROM \"$table\"")
            ->fetchColumn();

        if ($count !== 0) {
            throw new RuntimeException(
                "STOP: Supabase table $table is not empty."
            );
        }

        $columnList = implode(', ', array_map(
            fn($column) => "\"$column\"",
            $columns
        ));

        $placeholders = implode(', ', array_fill(
            0,
            count($columns),
            '?'
        ));

        $selectColumns = implode(', ', array_map(
            fn($column) => "`$column`",
            $columns
        ));

        $sourceRows = $mysql->query(
            "SELECT $selectColumns FROM `$table` ORDER BY `id`"
        );

        $insert = $postgres->prepare(
            "INSERT INTO \"$table\" ($columnList)
             VALUES ($placeholders)"
        );

        $transferred = 0;

        foreach ($sourceRows as $row) {
            $values = [];

            foreach ($columns as $column) {
                $value = $row[$column];

                if (in_array($column, [
    'email_verified',
    'is_available',
    'stock_enabled'
], true)) {
    $value = filter_var(
        $value,
        FILTER_VALIDATE_BOOLEAN
    ) ? 'true' : 'false';
}

                $values[] = $value;
            }

            $insert->execute($values);
            $transferred++;
        }

        echo "$table: $transferred records transferred.\n";
    }

    $sequences = [
        'users',
        'product_categories',
        'menu_products',
        'orders',
        'email_otps',
    ];

    foreach ($sequences as $table) {
        $postgres->query(
            "SELECT setval(
                pg_get_serial_sequence('$table', 'id'),
                COALESCE((SELECT MAX(id) FROM \"$table\"), 1),
                EXISTS (SELECT 1 FROM \"$table\")
            )"
        );
    }

    $postgres->commit();

    echo "\nSUCCESS: Transfer completed.\n";

} catch (Throwable $error) {
    if ($postgres->inTransaction()) {
        $postgres->rollBack();
    }

    echo "\nTRANSFER CANCELLED: " . $error->getMessage() . "\n";
    exit(1);
}
