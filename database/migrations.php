<?php
/** Versioned, additive MySQL/TiDB migrations. Run from a single deploy process. */

function migrationTableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function verifyBaselineSchema(PDO $pdo): void {
    $required = [
        'users' => ['id', 'full_name', 'student_id', 'email', 'password', 'role', 'created_at'],
        'complaints' => ['id', 'complaint_code', 'user_id', 'title', 'category', 'location', 'priority', 'description', 'status', 'resolution_note', 'created_at', 'updated_at'],
    ];
    foreach ($required as $table => $columns) {
        if (!migrationTableExists($pdo, $table)) {
            throw new RuntimeException("Existing database is missing {$table}; manual review required before migration.");
        }
        $stmt = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        $found = array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $missing = array_diff($columns, $found);
        if ($missing) {
            throw new RuntimeException("Existing {$table} table lacks required columns: " . implode(', ', $missing));
        }
    }
}

function executeMigrationSql(PDO $pdo, string $sql): void {
    // Migration files contain plain DDL statements; no routines or semicolons in literals.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
}

/** @return list<string> Newly applied migration versions. */
function runMigrations(PDO $pdo): array {
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(100) PRIMARY KEY,
        checksum CHAR(64) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $files = glob(__DIR__ . '/migrations/[0-9]*_*.sql') ?: [];
    sort($files, SORT_STRING);
    if (!$files) {
        throw new RuntimeException('No migration files found.');
    }
    $applied = [];
    $versions = [];
    foreach ($files as $file) {
        $version = basename($file, '.sql');
        if (isset($versions[$version])) {
            throw new RuntimeException("Duplicate migration version {$version}.");
        }
        $versions[$version] = true;
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Cannot read migration {$version}.");
        }
        $checksum = hash('sha256', $sql);
        $stmt = $pdo->prepare('SELECT checksum FROM schema_migrations WHERE version = ?');
        $stmt->execute([$version]);
        $previous = $stmt->fetchColumn();
        if ($previous !== false) {
            if (!hash_equals((string)$previous, $checksum)) {
                throw new RuntimeException("Migration {$version} changed after being applied.");
            }
            continue;
        }

        if ($version === '001_baseline') {
            $users = migrationTableExists($pdo, 'users');
            $complaints = migrationTableExists($pdo, 'complaints');
            if ($users xor $complaints) {
                throw new RuntimeException('Partial baseline schema found; manual review required.');
            }
            if (!$users) {
                executeMigrationSql($pdo, $sql);
            }
            verifyBaselineSchema($pdo);
        } else {
            executeMigrationSql($pdo, $sql);
        }
        // DDL can commit implicitly. Migrations must therefore be idempotent;
        // recording only follows successful verification/execution.
        $record = $pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (?, ?)');
        $record->execute([$version, $checksum]);
        $applied[] = $version;
    }
    return $applied;
}
