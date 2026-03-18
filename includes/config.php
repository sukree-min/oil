<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ฟังก์ชันสร้างการเชื่อมต่อ PDO
function createConnection($prefix) {
    $driver  = $_ENV["{$prefix}_DRIVER"] ?? 'mysql';
    $host    = $_ENV["{$prefix}_HOST"] ?? 'localhost';
    $port    = $_ENV["{$prefix}_PORT"] ?? '';
    $dbname  = $_ENV["{$prefix}_NAME"] ?? '';
    $user    = $_ENV["{$prefix}_USER"] ?? '';
    $pass    = $_ENV["{$prefix}_PASS"] ?? '';
    $charset = $_ENV["{$prefix}_CHARSET"] ?? 'utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($driver === 'mysql') {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    } elseif ($driver === 'sqlsrv') {
        //$dsn = "sqlsrv:Server=$host" . ($port ? ",$port" : "") . ";Database=$dbname;Encrypt=optional;TrustServerCertificate=true";   //เปิดสำหรับ server
        $dsn = "sqlsrv:Server=$host" . ($port ? ",$port" : "") . ";Database=$dbname";
    } else {
        throw new Exception("ไม่รองรับ driver: $driver");
    }

    $conn = new PDO($dsn, $user, $pass, $options);

    // ✅ เพิ่มบรรทัดนี้เพื่อความชัวร์ว่าใช้ utf8mb4 จริง
    if ($driver === 'mysql') {
        $conn->exec("SET NAMES 'utf8mb4'");
    }

    return $conn;
}


// สร้างตัวแปรเชื่อมต่อแต่ละฐานข้อมูล SQL_Server
$conn  = createConnection('DB_MAIN');
$conn_kkdoc  = createConnection('DB_KKDOC');
$conn_oil  = createConnection('DB_OIL');
$conn_sv2  = createConnection('DB_PhanthongHRM');
$conn_sv3  = createConnection('DB_PhanthongCFO');

?>
