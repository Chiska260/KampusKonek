<?php
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Cannot connect to the database. Check config/config.php and make sure MySQL is running.');
    }
    return $pdo;
}

function db_run(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

function db_row(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_val(string $sql, array $params = [])
{
    $v = db_run($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function db_insert(string $sql, array $params = []): int
{
    db_run($sql, $params);
    return (int) db()->lastInsertId();
}

// builds "col IN (?,?,?)" and pushes the ids into $params.
// null = no restriction (admin), empty array = match nothing
function in_clause(string $col, ?array $ids, array &$params): string
{
    if ($ids === null) {
        return '1=1';
    }
    if (!$ids) {
        return '0=1';
    }
    foreach ($ids as $id) {
        $params[] = (int) $id;
    }
    return $col . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
}
