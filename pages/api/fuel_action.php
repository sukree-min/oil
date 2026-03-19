<?php
require_once __DIR__ . '/../../includes/auth_session.php';
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ============================================
        // ดึงรายชื่อรถ
        // ============================================
        case 'get_vehicles':
            $stmt = $conn_oil->query("SELECT * FROM oil_vehicles WHERE is_active = 1 ORDER BY plate_number");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        // ============================================
        // ดึงรายชื่อคนขับ
        // ============================================
        case 'get_drivers':
            $stmt = $conn_oil->query("SELECT * FROM oil_drivers WHERE is_active = 1 ORDER BY driver_name");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        // ============================================
        // ดึงรายการเติมน้ำมัน
        // ============================================
        case 'get_records':
            $where = "1=1";
            $params = [];

            if (!empty($_GET['vehicle_id'])) {
                $where .= " AND r.vehicle_id = ?";
                $params[] = $_GET['vehicle_id'];
            }
            if (!empty($_GET['driver_id'])) {
                $where .= " AND r.driver_id = ?";
                $params[] = $_GET['driver_id'];
            }
            if (!empty($_GET['date_from'])) {
                $where .= " AND r.fuel_date >= ?";
                $params[] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $where .= " AND r.fuel_date <= ?";
                $params[] = $_GET['date_to'];
            }

            $sql = "SELECT r.*, v.plate_number, v.vehicle_name, d.driver_name
                    FROM oil_fuel_records r
                    LEFT JOIN oil_vehicles v ON r.vehicle_id = v.id
                    LEFT JOIN oil_drivers d ON r.driver_id = d.id
                    WHERE $where
                    ORDER BY r.fuel_date DESC, r.id DESC";
            
            $stmt = $conn_oil->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        // ============================================
        // ดึงข้อมูลสรุป
        // ============================================
        case 'get_summary':
            $month = $_GET['month'] ?? date('Y-m');
            
            // สรุปเดือนนี้
            $stmt = $conn_oil->prepare("
                SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(total_cost), 0) as total_cost,
                    COALESCE(SUM(liters), 0) as total_liters,
                    COALESCE(AVG(total_cost), 0) as avg_cost
                FROM oil_fuel_records 
                WHERE DATE_FORMAT(fuel_date, '%Y-%m') = ?
            ");
            $stmt->execute([$month]);
            $summary = $stmt->fetch();

            // สรุปรายรถ
            $stmt2 = $conn_oil->prepare("
                SELECT v.plate_number, v.vehicle_name,
                    COUNT(*) as cnt,
                    COALESCE(SUM(r.total_cost), 0) as total
                FROM oil_fuel_records r
                LEFT JOIN oil_vehicles v ON r.vehicle_id = v.id
                WHERE DATE_FORMAT(r.fuel_date, '%Y-%m') = ?
                GROUP BY r.vehicle_id
                ORDER BY total DESC
            ");
            $stmt2->execute([$month]);
            $by_vehicle = $stmt2->fetchAll();

            echo json_encode([
                'status' => 'success',
                'summary' => $summary,
                'by_vehicle' => $by_vehicle
            ]);
            break;

        // ============================================
        // เพิ่มข้อมูลการเติมน้ำมัน
        // ============================================
        case 'add_record':
            $vehicle_id    = $_POST['vehicle_id'] ?? '';
            $driver_id     = $_POST['driver_id'] ?? '';
            $fuel_date     = $_POST['fuel_date'] ?? '';
            $fuel_type     = $_POST['fuel_type'] ?? '';
            $liters        = $_POST['liters'] ?? 0;
            $price_per_liter = $_POST['price_per_liter'] ?? 0;
            $total_cost    = $_POST['total_cost'] ?? 0;
            $mileage = $_POST['mileage'] !== '' ? intval($_POST['mileage']) : null;
            $station_name  = $_POST['station_name'] ?? '';
            $note          = $_POST['note'] ?? '';
            $created_by    = $_SESSION['username'] ?? 'Unknown';

            if (!$vehicle_id || !$driver_id || !$fuel_date || !$total_cost) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็น']);
                exit;
            }

            // Upload bill image
            $bill_image = '';
            if (isset($_FILES['bill_image']) && $_FILES['bill_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/fuel/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['bill_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($ext, $allowed)) {
                    echo json_encode(['status' => 'error', 'message' => 'รองรับเฉพาะไฟล์รูปภาพ (jpg, png, gif, webp)']);
                    exit;
                }
                
                if ($_FILES['bill_image']['size'] > 10 * 1024 * 1024) {
                    echo json_encode(['status' => 'error', 'message' => 'ไฟล์ใหญ่เกินไป (สูงสุด 10MB)']);
                    exit;
                }

                $filename = 'bill_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['bill_image']['tmp_name'], $destination)) {
                    $bill_image = $filename;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'อัปโหลดรูปไม่สำเร็จ']);
                    exit;
                }
            }

            $stmt = $conn_oil->prepare("
                INSERT INTO oil_fuel_records 
                (vehicle_id, driver_id, fuel_date, fuel_type, liters, price_per_liter, total_cost, mileage, station_name, bill_image, note, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $vehicle_id, $driver_id, $fuel_date, $fuel_type,
                $liters ?: null, $price_per_liter ?: null, $total_cost,
                $mileage ?: null, $station_name, $bill_image, $note, $created_by
            ]);

            // หักยอดเงินจากกระเป๋ารถ
            $stmt_wallet = $conn_oil->prepare("UPDATE oil_vehicles SET current_balance = current_balance - ? WHERE id = ?");
            $stmt_wallet->execute([$total_cost, $vehicle_id]);

            // แจ้งเตือน Telegram
            sendFuelTelegram($conn_kkdoc, $conn, 'add', $_POST);

            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลสำเร็จ', 'id' => $conn_oil->lastInsertId()]);
            break;

        case 'update_record':
            $id = $_POST['id'] ?? '';
            $vehicle_id = $_POST['vehicle_id'] ?? '';
            $driver_id = $_POST['driver_id'] ?? '';
            $fuel_date = $_POST['fuel_date'] ?? date('Y-m-d');
            $fuel_type = $_POST['fuel_type'] ?? '';
            $liters = floatval($_POST['liters'] ?? 0);
            $price_per_liter = floatval($_POST['price_per_liter'] ?? 0);
            $total_cost = floatval($_POST['total_cost'] ?? 0);
            $mileage = $_POST['mileage'] !== '' ? intval($_POST['mileage']) : null;
            $station_name = $_POST['station_name'] ?? '';
            $note = $_POST['note'] ?? '';

            if (!$id || !$vehicle_id || !$driver_id || !$total_cost) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            // Fetch old record for balance calculation
            $stmt_old = $conn_oil->prepare("SELECT vehicle_id, total_cost FROM oil_fuel_records WHERE id = ?");
            $stmt_old->execute([$id]);
            $old_record = $stmt_old->fetch(PDO::FETCH_ASSOC);

            // จัดการรูปภาพบิลเดิม
            $stmt = $conn_oil->prepare("SELECT bill_image FROM oil_fuel_records WHERE id = ?");
            $stmt->execute([$id]);
            $bill_image = $stmt->fetchColumn();

            if (isset($_FILES['bill_image']) && $_FILES['bill_image']['error'] == 0) {
                $new_image = handleUpload($_FILES['bill_image'], 'bill_');
                if ($new_image) {
                    if ($bill_image && file_exists(__DIR__ . '/../../uploads/fuel/' . $bill_image)) {
                        unlink(__DIR__ . '/../../uploads/fuel/' . $bill_image);
                    }
                    $bill_image = $new_image;
                }
            }

            $stmt = $conn_oil->prepare("
                UPDATE oil_fuel_records 
                SET vehicle_id = ?, driver_id = ?, fuel_date = ?, fuel_type = ?, 
                    liters = ?, price_per_liter = ?, total_cost = ?, mileage = ?, 
                    station_name = ?, bill_image = ?, note = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $vehicle_id, $driver_id, $fuel_date, $fuel_type,
                $liters, $price_per_liter, $total_cost, $mileage,
                $station_name, $bill_image, $note, $id
            ]);

            // Adjust balances
            if ($old_record && $old_record['vehicle_id'] == $vehicle_id) {
                $diff = floatval($total_cost) - floatval($old_record['total_cost']);
                if ($diff != 0) {
                    $stmt_wallet = $conn_oil->prepare("UPDATE oil_vehicles SET current_balance = current_balance - ? WHERE id = ?");
                    $stmt_wallet->execute([$diff, $vehicle_id]);
                }
            } elseif ($old_record) {
                $stmt_refund = $conn_oil->prepare("UPDATE oil_vehicles SET current_balance = current_balance + ? WHERE id = ?");
                $stmt_refund->execute([$old_record['total_cost'], $old_record['vehicle_id']]);
                
                $stmt_deduct = $conn_oil->prepare("UPDATE oil_vehicles SET current_balance = current_balance - ? WHERE id = ?");
                $stmt_deduct->execute([$total_cost, $vehicle_id]);
            }

            // แจ้งเตือน Telegram
            sendFuelTelegram($conn_kkdoc, $conn, 'edit', $_POST);

            echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลสำเร็จ']);
            break;

        // ============================================
        // ลบรายการ
        // ============================================
        case 'delete_record':
            $id = $_POST['id'] ?? '';
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสรายการ']);
                exit;
            }

            // Delete bill image file and Refund balance
            $stmt = $conn_oil->prepare("SELECT vehicle_id, total_cost, bill_image FROM oil_fuel_records WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();
            
            if ($record) {
                // Refund balance
                $stmt_refund = $conn_oil->prepare("UPDATE oil_vehicles SET current_balance = current_balance + ? WHERE id = ?");
                $stmt_refund->execute([$record['total_cost'], $record['vehicle_id']]);
                
                if ($record['bill_image']) {
                    $file = __DIR__ . '/../../uploads/fuel/' . $record['bill_image'];
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }

            $stmt = $conn_oil->prepare("DELETE FROM oil_fuel_records WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['status' => 'success', 'message' => 'ลบรายการสำเร็จ']);
            break;

        // ============================================
        // จัดการกระเป๋าเงินรถ (Wallet)
        // ============================================
        case 'add_fund':
            if (!isset($_SESSION['role_id']) || !in_array(strval($_SESSION['role_id']), ['1', '5', '6'])) {
                echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ทำรายการนี้']);
                exit;
            }
            $vehicle_id = $_POST['vehicle_id'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            
            if (!$vehicle_id || $amount == 0) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุข้อมูลให้ครบถ้วนและจำนวนเงินต้องไม่เป็น 0']);
                exit;
            }
            
            $stmt = $conn_oil->prepare("UPDATE oil_vehicles SET current_balance = current_balance + ? WHERE id = ?");
            if ($stmt->execute([$amount, $vehicle_id])) {
                echo json_encode(['status' => 'success', 'message' => 'ปรับปรุงยอดเงินสำเร็จ']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถปรับปรุงยอดเงินได้']);
            }
            break;

        // ============================================
        // จัดการข้อมูลรถ (Vehicles)
        // ============================================
        case 'add_vehicle':
            $plate = $_POST['plate_number'] ?? '';
            $name = $_POST['vehicle_name'] ?? '';
            $type = $_POST['vehicle_type'] ?? '';
            if (!$plate) { echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุทะเบียนรถ']); exit; }
            
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = handleUpload($_FILES['image'], 'vehicles_');
            }

            $stmt = $conn_oil->prepare("INSERT INTO oil_vehicles (plate_number, vehicle_name, vehicle_type, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$plate, $name, $type, $image]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลรถสำเร็จ']);
            break;

        case 'update_vehicle':
            $id = $_POST['id'] ?? '';
            $plate = $_POST['plate_number'] ?? '';
            $name = $_POST['vehicle_name'] ?? '';
            $type = $_POST['vehicle_type'] ?? '';
            if (!$id || !$plate) { echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']); exit; }
            
            $stmt = $conn_oil->prepare("SELECT image FROM oil_vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $old_image = $stmt->fetchColumn();
            $image = $old_image;

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $new_image = handleUpload($_FILES['image'], 'vehicles_');
                if ($new_image) {
                    $image = $new_image;
                    if ($old_image && file_exists(__DIR__ . '/../../uploads/fuel/' . $old_image)) {
                        unlink(__DIR__ . '/../../uploads/fuel/' . $old_image);
                    }
                }
            }

            $stmt = $conn_oil->prepare("UPDATE oil_vehicles SET plate_number = ?, vehicle_name = ?, vehicle_type = ?, image = ? WHERE id = ?");
            $stmt->execute([$plate, $name, $type, $image, $id]);
            echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลรถสำเร็จ']);
            break;

        case 'delete_vehicle':
            $id = $_POST['id'] ?? '';
            if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัส']); exit; }
            
            $stmt = $conn_oil->prepare("SELECT image FROM oil_vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn();

            // ตรวจสอบว่ามีการใช้งานอยู่ในรายการน้ำมันหรือไม่
            $stmt = $conn_oil->prepare("SELECT COUNT(*) FROM oil_fuel_records WHERE vehicle_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $stmt = $conn_oil->prepare("UPDATE oil_vehicles SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'message' => 'ปิดการใช้งานรถสำเร็จเนื่องจากมีประวัติการบันทึกน้ำมัน']);
            } else {
                if ($image && file_exists(__DIR__ . '/../../uploads/fuel/' . $image)) {
                    unlink(__DIR__ . '/../../uploads/fuel/' . $image);
                }
                $stmt = $conn_oil->prepare("DELETE FROM oil_vehicles WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลรถสำเร็จ']);
            }
            break;

        // ============================================
        // จัดการข้อมูลคนขับ (Drivers)
        // ============================================
        case 'add_driver':
            $name = $_POST['driver_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            if (!$name) { echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อคนขับ']); exit; }
            
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = handleUpload($_FILES['image'], 'drivers_');
            }

            $stmt = $conn_oil->prepare("INSERT INTO oil_drivers (driver_name, phone, image) VALUES (?, ?, ?)");
            $stmt->execute([$name, $phone, $image]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลคนขับสำเร็จ']);
            break;

        case 'update_driver':
            $id = $_POST['id'] ?? '';
            $name = $_POST['driver_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            if (!$id || !$name) { echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']); exit; }
            
            $stmt = $conn_oil->prepare("SELECT image FROM oil_drivers WHERE id = ?");
            $stmt->execute([$id]);
            $old_image = $stmt->fetchColumn();
            $image = $old_image;

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $new_image = handleUpload($_FILES['image'], 'drivers_');
                if ($new_image) {
                    $image = $new_image;
                    if ($old_image && file_exists(__DIR__ . '/../../uploads/fuel/' . $old_image)) {
                        unlink(__DIR__ . '/../../uploads/fuel/' . $old_image);
                    }
                }
            }

            $stmt = $conn_oil->prepare("UPDATE oil_drivers SET driver_name = ?, phone = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $image, $id]);
            echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลคนขับสำเร็จ']);
            break;

        case 'delete_driver':
            $id = $_POST['id'] ?? '';
            if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัส']); exit; }
            
            $stmt = $conn_oil->prepare("SELECT image FROM oil_drivers WHERE id = ?");
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn();

            // ตรวจสอบว่ามีการใช้งานอยู่ในรายการน้ำมันหรือไม่
            $stmt = $conn_oil->prepare("SELECT COUNT(*) FROM oil_fuel_records WHERE driver_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $stmt = $conn_oil->prepare("UPDATE oil_drivers SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'message' => 'ปิดการใช้งานคนขับสำเร็จเนื่องจากมีประวัติการบันทึกน้ำมัน']);
            } else {
                if ($image && file_exists(__DIR__ . '/../../uploads/fuel/' . $image)) {
                    unlink(__DIR__ . '/../../uploads/fuel/' . $image);
                }
                $stmt = $conn_oil->prepare("DELETE FROM oil_drivers WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลคนขับสำเร็จ']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบ action ที่ระบุ']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}

/**
 * ฟังก์ชันช่วยจัดการอัปโหลดรูปภาพ
 */
function handleUpload($file, $prefix) {
    if (!isset($file) || $file['error'] != 0) return null;
    
    $targetDir = __DIR__ . '/../../uploads/fuel/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($ext, $allowed)) {
        $newName = $prefix . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $targetDir . $newName)) {
            return $newName;
        }
    }
    return null;
}

/**
 * ฟังก์ชันแจ้งเตือนผ่าน Telegram
 */
function sendFuelTelegram($conn_kkdoc, $conn, $action_name, $data) {
    try {
        global $conn_oil;

        // Get Vehicle Name
        $stmt = $conn_oil->prepare("SELECT plate_number, vehicle_name FROM oil_vehicles WHERE id = ?");
        $stmt->execute([$data['vehicle_id']]);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);
        $vehicle_text = $v ? $v['plate_number'] . (!empty($v['vehicle_name']) ? " ({$v['vehicle_name']})" : "") : "ไม่ระบุ";

        // Get Driver Name
        $stmt = $conn_oil->prepare("SELECT driver_name FROM oil_drivers WHERE id = ?");
        $stmt->execute([$data['driver_id']]);
        $driver_text = $stmt->fetchColumn() ?: "ไม่ระบุ";

        $date_obj = date_create($data['fuel_date']);
        $date_txt = $date_obj ? date_format($date_obj, 'd/m/Y') : $data['fuel_date'];
        
        $msg = "";
        if ($action_name === 'add') {
            $msg .= "🚨 <b>แจ้งเตือนบันทึกเติมน้ำมันใหม่</b>\n\n";
        } else {
            $msg .= "✏️ <b>แจ้งเตือนแก้ไขข้อมูลเติมน้ำมัน</b>\n\n";
        }

        $msg .= "🚗 <b>รถ:</b> " . htmlspecialchars($vehicle_text) . "\n";
        $msg .= "👨‍✈️ <b>คนขับ:</b> " . htmlspecialchars($driver_text) . "\n";
        $msg .= "📅 <b>วันที่:</b> " . $date_txt . "\n";
        
        if (!empty($data['fuel_type'])) {
            $msg .= "⛽ <b>ประเภท:</b> " . htmlspecialchars($data['fuel_type']) . "\n";
        }
        
        if (!empty($data['liters'])) {
            $msg .= "💧 <b>จำนวน:</b> " . number_format($data['liters'], 2) . " ลิตร\n";
        }
        
        if (!empty($data['price_per_liter'])) {
            $msg .= "🏷 <b>ราคา/ลิตร:</b> " . number_format($data['price_per_liter'], 3) . " บาท\n";
        }
        
        $msg .= "💰 <b>ยอดรวม:</b> <b>" . number_format($data['total_cost'], 2) . " บาท</b>\n";
        
        if (!empty($data['mileage'])) {
            $msg .= "⏱ <b>เลขไมล์:</b> " . number_format($data['mileage']) . " กม.\n";
        }
        if (!empty($data['station_name'])) {
            $msg .= "📍 <b>ปั๊ม:</b> " . htmlspecialchars($data['station_name']) . "\n";
        }
        if (!empty($data['note'])) {
            $msg .= "📝 <b>หมายเหตุ:</b> " . htmlspecialchars($data['note']) . "\n";
        }
        
        $msg .= "\n🙋‍♂️ <b>ผู้บันทึก:</b> " . htmlspecialchars($_SESSION['username'] ?? 'Unknown');

        // เรียกใช้ฟังก์ชันส่ง Telegram ที่แยกไว้
        require_once __DIR__ . '/../telegram_notify.php';
        sendTelegramNotification($msg, $conn_kkdoc);

        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
