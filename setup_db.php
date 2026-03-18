<?php
// One-time script to set up the database tables
require_once __DIR__ . '/includes/config.php';

try {
    // Create tables in kkdoc database
    $conn_kkdoc->exec("
        CREATE TABLE IF NOT EXISTS oil_vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plate_number VARCHAR(20) NOT NULL COMMENT 'ทะเบียนรถ',
            vehicle_name VARCHAR(100) COMMENT 'ชื่อรถ/ยี่ห้อ',
            vehicle_type VARCHAR(50) COMMENT 'ประเภทรถ',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $conn_kkdoc->exec("
        CREATE TABLE IF NOT EXISTS oil_drivers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            driver_name VARCHAR(100) NOT NULL COMMENT 'ชื่อคนขับ',
            phone VARCHAR(20) COMMENT 'เบอร์โทร',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $conn_kkdoc->exec("
        CREATE TABLE IF NOT EXISTS oil_fuel_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            driver_id INT NOT NULL,
            fuel_date DATE NOT NULL COMMENT 'วันที่เติม',
            fuel_type VARCHAR(50) COMMENT 'ประเภทน้ำมัน',
            liters DECIMAL(10,2) COMMENT 'จำนวนลิตร',
            price_per_liter DECIMAL(10,2) COMMENT 'ราคาต่อลิตร',
            total_cost DECIMAL(10,2) NOT NULL COMMENT 'ยอดรวม (บาท)',
            mileage INT COMMENT 'เลขไมล์',
            station_name VARCHAR(200) COMMENT 'ชื่อปั๊มน้ำมัน',
            bill_image VARCHAR(500) COMMENT 'ไฟล์รูปบิล',
            note TEXT COMMENT 'หมายเหตุ',
            created_by VARCHAR(100) COMMENT 'ผู้บันทึก',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES oil_vehicles(id),
            FOREIGN KEY (driver_id) REFERENCES oil_fuel_records(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Check if sample data exists
    $check = $conn_kkdoc->query("SELECT COUNT(*) as cnt FROM oil_vehicles")->fetch();
    if ($check['cnt'] == 0) {
        $conn_kkdoc->exec("
            INSERT INTO oil_vehicles (plate_number, vehicle_name, vehicle_type) VALUES
            ('กข 1234', 'Toyota Hilux Revo', 'กระบะ'),
            ('ขค 5678', 'Isuzu D-Max', 'กระบะ'),
            ('คง 9012', 'Toyota Innova', 'รถตู้'),
            ('งจ 3456', 'Honda Civic', 'เก๋ง'),
            ('จฉ 7890', 'Nissan Navara', 'กระบะ')
        ");
        echo "✅ Inserted 5 vehicles<br>";
    }

    $check2 = $conn_kkdoc->query("SELECT COUNT(*) as cnt FROM oil_drivers")->fetch();
    if ($check2['cnt'] == 0) {
        $conn_kkdoc->exec("
            INSERT INTO oil_drivers (driver_name, phone) VALUES
            ('สมชาย ใจดี', '081-234-5678'),
            ('สมหญิง รักงาน', '082-345-6789'),
            ('ประเสริฐ มั่นคง', '083-456-7890'),
            ('วิชัย เก่งกาจ', '084-567-8901'),
            ('อรุณ สว่างไสว', '085-678-9012')
        ");
        echo "✅ Inserted 5 drivers<br>";
    }

    echo "<h2>✅ Database setup completed successfully!</h2>";
    echo "<a href='pages/fuel_dashboard.php'>ไปหน้า Dashboard →</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
