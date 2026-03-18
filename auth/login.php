<?php
session_start();
// ตรวจสอบให้แน่ใจว่า path นี้ถูกต้อง
// บรรทัดที่ 8: ต้องไม่มีช่องว่างที่ผิดปกติก่อนคำสั่ง
require_once __DIR__ . '/../includes/config.php'; // ใช้ __DIR__ เพื่อความชัวร์เรื่อง Path ด้วย

// Gate: ถ้าเข้าสู่ระบบแล้ว ให้ไป Dashboard ทันที
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error_message = ''; // กำหนดตัวแปร error_message เปล่าไว้ก่อน

// *** เพิ่ม: ตัวแปรสำหรับรับข้อความสำเร็จจากการลงทะเบียน (Register Success Message) ***
$reg_success_message = ''; 
if (isset($_SESSION['reg_success'])) {
    $reg_success_message = $_SESSION['reg_success'];
    // ลบข้อความออกจาก Session ทันทีหลังดึงมาใช้
    unset($_SESSION['reg_success']); 
}
// ----------------------------------------------------------------------------------

// บรรทัดที่ 27: ตรวจสอบให้แน่ใจว่าไม่มีอักขระแปลกปลอมนำหน้า if
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // กรองและทำความสะอาดข้อมูลที่ส่งมาจากฟอร์ม (ใช้ FILTER_SANITIZE_FULL_SPECIAL_CHARS แทน STRING)
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = trim(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    // ------------------------------------------------------------------------------------------------
    // ส่วน PHP ที่ปรับปรุง: รวมข้อมูล Role, Status, Sex ด้วย LEFT JOIN
    // ------------------------------------------------------------------------------------------------
    $sql = "
        SELECT 
            u.*, 
            r.role_name, 
            s.status_name,
            x.sex_name
        FROM 
            tbl_users u
        LEFT JOIN 
            tbl_role r ON u.role = r.id
        LEFT JOIN 
            tbl_status s ON u.status = s.id
        LEFT JOIN 
            tbl_sex x ON u.sex = x.id
        WHERE 
            u.username = :username
    ";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // ตรวจสอบสถานะ (Status ID: 1 คือ ใช้งานอยู่)
            if ($user['status'] != '1') {
                $error_message = "บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
            } else {
                // บันทึกข้อมูลที่ต้องการใน Session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
																$_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role_id'] = $user['role']; 
                $_SESSION['role_name'] = $user['role_name']; 
                $_SESSION['status_id'] = $user['status']; 
                $_SESSION['status_name'] = $user['status_name']; 
                $_SESSION['sex_id'] = $user['sex']; 
                $_SESSION['sex_name'] = $user['sex_name']; 
                $_SESSION['LAST_ACTIVITY'] = time();

                // ป้องกัน Session Hijacking: สร้าง Session ID ใหม่
                session_regenerate_id(true);

                header('Location: ../index.php');
                exit;
            }
        } else {
            $error_message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    } catch (PDOException $e) {
        // จัดการข้อผิดพลาดฐานข้อมูล
        error_log("Database Error: " . $e->getMessage());
        $error_message = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่ภายหลัง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | ระบบบริหารจัดการ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* เปลี่ยนเป็น Sarabun เพื่อความเป็นทางการ/อ่านง่ายกว่า K2D สำหรับระบบงาน */
        body { font-family: 'Sarabun', sans-serif; }

        /* การปรับปรุงภาพพื้นหลังให้ดูพรีเมียมขึ้น */
        .premium-bg {
            background-image: linear-gradient(135deg, #a7f3d0 0%, #34d399 100%);
        }
        
        /* สไตล์ฟอร์มที่คงความทันสมัย */
        .login-card {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            backdrop-filter: blur(5px); /* เพิ่มความลึก */
        }

        /* Animation */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up {
            animation: fadeInUp 0.7s ease-out;
        }

        /* Password Toggle */
        .password-toggle:hover {
            color: #10b981 !important; /* เปลี่ยนเป็นสีเขียวเข้มขึ้น */
        }
    </style>
</head>
<body class="premium-bg min-h-screen flex items-center justify-center">

    <div class="w-full max-w-sm p-8 bg-white/90 login-card rounded-2xl shadow-2xl fade-in-up relative overflow-hidden">
        
        <div class="text-center mb-8">
            <!-- <i class="fas fa-lock text-5xl text-green-600 mb-3 animate-bounce" style="animation-duration: 2s;"></i> -->
            <img
            src="../assets/img/kch_logo.png"
            alt="KCH"
            class="w-32 h-auto mb-2 animate-bounce text-center mx-auto"
            style="animation-duration: 2s;"
            >
            <h2 class="text-3xl font-extrabold text-gray-800">เข้าสู่ระบบ</h2>
            <p class="text-gray-500 text-sm mt-1">กรุณากรอกชื่อผู้ใช้และรหัสผ่าน</p>
        </div>

        <form action="login.php" method="POST" class="space-y-6">
            
            <div class="relative">
                <input type="text" name="username" id="username" required autocomplete="username"
                    class="peer w-full px-4 pt-6 pb-2 bg-white border border-gray-300 rounded-lg placeholder-transparent focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    placeholder="ชื่อผู้ใช้" />
                <label for="username"
                    class="absolute left-4 top-2 text-gray-500 text-sm transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-green-600">
                    ชื่อผู้ใช้
                </label>
            </div>

            <div class="relative">
                <input type="password" name="password" id="password" required autocomplete="current-password"
                    class="peer w-full px-4 pt-6 pb-2 pr-12 bg-white border border-gray-300 rounded-lg placeholder-transparent focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    placeholder="รหัสผ่าน" />
                <label for="password"
                    class="absolute left-4 top-2 text-gray-500 text-sm transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-green-600">
                    รหัสผ่าน
                </label>
                <button type="button" onclick="togglePassword()" aria-label="แสดง/ซ่อนรหัสผ่าน"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none password-toggle p-2">
                    <i id="passwordToggleIcon" class="fas fa-eye text-lg"></i>
                    <span id="passwordToggleText" class="text-sm font-bold" style="display: none;">👁</span>
                </button>
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl transition shadow-lg font-semibold tracking-wider transform hover:scale-[1.01] duration-200">
                เข้าสู่ระบบ <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </form>

								<div class="mt-8 text-center text-sm text-gray-500 space-x-4">
            <a href="/kch-all/" class="text-gray-600 hover:text-green-600 hover:underline transition">หน้าหลัก</a> 
            <!-- <span class="text-gray-400">|</span>
            <a href="register_user.php" class="text-green-600 hover:text-green-700 hover:underline transition">ลงทะเบียนบัญชีใหม่</a> -->
        </div>
        

        <p class="text-xs text-gray-400 text-center mt-4">© 2025 System. All rights reserved.</p>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            const isPassword = (passwordInput.type === 'password');
            
            passwordInput.type = isPassword ? 'text' : 'password';
            
            if (isPassword) {
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
                toggleIcon.title = 'ซ่อนรหัสผ่าน';
            } else {
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
                toggleIcon.title = 'แสดงรหัสผ่าน';
            }
        }

        // SweetAlert Feedback
        document.addEventListener('DOMContentLoaded', function() {
            // ซ่อน emoji ถ้า Font Awesome ทำงาน
            const toggleIcon = document.getElementById('passwordToggleIcon');
            const toggleText = document.getElementById('passwordToggleText');
            if (toggleIcon.offsetWidth > 0) {
                 toggleText.style.display = 'none';
            } else {
                 toggleIcon.style.display = 'none';
                 toggleText.style.display = 'inline';
            }

            // 1. แสดง Error Message จากการ Login
            <?php if (isset($error_message) && $error_message): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'เข้าสู่ระบบล้มเหลว',
                    text: '<?php echo str_replace("'", "\'", $error_message); ?>',
                    confirmButtonText: 'ลองอีกครั้ง',
                    confirmButtonColor: '#dc2626' // Red
                });
            <?php endif; ?>

            // 2. แสดง Success Message ที่มาจากหน้าลงทะเบียน (Register)
            <?php if (isset($reg_success_message) && $reg_success_message): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'ลงทะเบียนสำเร็จ! ✅',
                    text: '<?php echo str_replace("'", "\'", $reg_success_message); ?>',
                    confirmButtonText: 'เข้าสู่ระบบ',
                    confirmButtonColor: '#10b981' // Green
                });
            <?php endif; ?>

            // 3. แสดง Timeout Message
            <?php if (isset($_GET['timeout'])): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'หมดเวลาใช้งาน',
                    text: 'คุณถูกออกจากระบบอัตโนมัติเนื่องจากไม่มีกิจกรรม',
                    confirmButtonText: 'เข้าสู่ระบบใหม่',
                    confirmButtonColor: '#f59e0b' // Amber
                });
            <?php endif; ?>
        });
    </script>

</body>
</html>