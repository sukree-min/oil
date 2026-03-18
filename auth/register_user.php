<?php
// ต้องเริ่ม session ก่อนการใช้ header() หรือส่งข้อมูลใด ๆ
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// require_once '../includes/auth_session.php'; // คอมเมนต์ไว้หากต้องการให้ลงทะเบียนได้โดยไม่ต้องล็อกอิน
require_once '../includes/config.php'; // เชื่อมต่อฐานข้อมูล (สมมติว่า $conn ถูกกำหนดไว้)

// หากผู้ใช้ล็อกอินอยู่แล้ว อาจ redirect ไปหน้า dashboard
if (isset($_SESSION['user_id'])) {
    // header('Location: ../dashboard.php');
    // exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ----------------------------------------------------------------------
    // ส่วน PHP ที่ปรับปรุง: กรองข้อมูลและใช้ try-catch เพื่อความปลอดภัย
    // ----------------------------------------------------------------------
    
    // กรองและทำความสะอาดข้อมูลที่รับมาจากฟอร์ม
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $user_id = trim(filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = $_POST['password']; // เก็บไว้ตรวจสอบ
    $confirm_password = $_POST['confirm_password'];
    
    // ข้อมูลเริ่มต้นสำหรับผู้ใช้ใหม่
    $role = 2;  // ตั้งให้เป็น user (หรือบทบาทเริ่มต้นที่คุณต้องการ)
    $status = 1; // ตั้งให้เป็น active ทันที
    $created_at = date('Y-m-d H:i:s');

    // 1. ตรวจสอบรหัสพนักงานต้องเป็นตัวเลขเท่านั้น
    if (!is_numeric($user_id) || empty($user_id)) {
        $error = "รหัสพนักงานต้องเป็นตัวเลขเท่านั้น และห้ามเว้นว่าง";
    // 2. ตรวจสอบ Username ต้องไม่มีภาษาไทย
    } elseif (preg_match('/[\p{Thai}]/u', $username)) {
        $error = "ชื่อผู้ใช้ (Username) ต้องเป็นภาษาอังกฤษ ตัวเลข หรือสัญลักษณ์เท่านั้น และห้ามใช้ภาษาไทย";
    // 3. ตรวจสอบรหัสผ่านตรงกัน
    } elseif ($password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    // 4. ตรวจสอบความซับซ้อนของรหัสผ่าน
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $password)) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร รวมตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และอักขระพิเศษ';
    } else {
        try {
            // 5. ตรวจสอบชื่อผู้ใช้และรหัสพนักงานซ้ำกันใน Query เดียว
            $check_sql = "SELECT username, user_id FROM tbl_users WHERE username = :username OR user_id = :user_id";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([':username' => $username, ':user_id' => $user_id]);
            $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                if ($existing_user['username'] === $username) {
                    $error = "ชื่อผู้ใช้นี้มีอยู่แล้ว";
                } elseif ($existing_user['user_id'] === $user_id) {
                    $error = "รหัสพนักงานนี้มีอยู่แล้ว";
                }
            } else {
                // 6. ลงทะเบียนผู้ใช้ใหม่
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $insert_sql = "INSERT INTO tbl_users (username, password, user_id, role, status, created_at) 
                                 VALUES (:username, :password, :user_id, :role, :status, :created_at)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hash,
                    ':user_id' => $user_id,
                    ':role' => $role,
                    ':status' => $status,
                    ':created_at' => $created_at
                ]);
                
                // *** ส่วนที่เพิ่ม/ปรับปรุงเพื่อป้องกันการบันทึกซ้ำ (PRG Pattern) ***
                // 1. เก็บข้อความสำเร็จใน Session
                $_SESSION['reg_success'] = "ลงทะเบียนเรียบร้อยแล้ว! กรุณาเข้าสู่ระบบ"; 
                // 2. Redirect ไปหน้า login.php ทันที
                header('Location: login.php');
                exit; // สำคัญ: ต้อง exit หลัง header
                // -------------------------------------------------------------
            }
        } catch (PDOException $e) {
            error_log("Database Error on registration: " . $e->getMessage());
            $error = "เกิดข้อผิดพลาดในการลงทะเบียน กรุณาลองใหม่อีกครั้ง";
        }
    }
}
?>

<?php
// *** ส่วนที่เพิ่ม: ดึงข้อความสำเร็จจาก Session สำหรับการแสดงผล SweetAlert หลัง Redirect (GET) ***
// ตรวจสอบว่ามีข้อความสำเร็จจากการลงทะเบียนหรือไม่
if (isset($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success'];
    // ลบข้อความออกจาก Session ทันทีหลังดึงมาใช้ เพื่อไม่ให้แสดงซ้ำ
    unset($_SESSION['reg_success']); 
}
// -----------------------------------------------------------------------------------------
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลงทะเบียนผู้ใช้ | ระบบบริหารจัดการ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }

        /* การปรับปรุงภาพพื้นหลังให้ดูพรีเมียมขึ้น (คล้อยตาม Login) */
        .premium-bg {
            background-image: linear-gradient(135deg, #a7f3d0 0%, #34d399 100%);
        }
        
        /* สไตล์ฟอร์มที่คงความทันสมัย */
        .regis-card {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            backdrop-filter: blur(5px);
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
        .password-toggle-btn {
            transition: all 0.2s;
            padding: 8px;
        }
        .password-toggle-btn:hover {
            color: #10b981 !important;
        }

        /* --- สไตล์ใหม่สำหรับ Password Feedback (คัดลอกมาจากตัวอย่างก่อนหน้า) --- */
        .password-check-list {
            list-style-type: none;
            padding-left: 0;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem; /* เพิ่ม margin ด้านล่างเพื่อแยกจาก confirm password */
        }
        .password-check-list li {
            display: flex;
            align-items: center;
            margin-bottom: 0.15rem;
            font-size: 0.75rem; /* text-xs */
            color: #6b7280; /* text-gray-500 */
        }
        .password-check-list li .icon {
            margin-right: 0.5rem;
            width: 1rem;
            text-align: center;
            transition: color 0.3s;
        }
        .password-check-list li.valid {
            color: #10b981; /* text-emerald-500 */
        }
        .password-check-list li.invalid {
            color: #ef4444; /* text-red-500 */
        }
        .password-check-list li.valid .icon {
            color: #10b981;
        }
        .password-check-list li.invalid .icon {
            color: #ef4444;
        }
        /* ----------------------------------------------------------------------- */
    </style>
</head>
<body class="premium-bg min-h-screen flex items-center justify-center p-4">

    <div class="bg-white/90 p-8 rounded-2xl regis-card fade-in-up shadow-2xl w-full max-w-md">
        
        <div class="text-center mb-8">
            <i class="fas fa-user-plus text-5xl text-green-600 mb-3"></i>
            <h2 class="text-3xl font-extrabold text-gray-800">ลงทะเบียนบัญชีใหม่</h2>
            <p class="text-gray-500 text-sm mt-1">สร้างบัญชีเพื่อเข้าสู่ระบบ</p>
        </div>

        <form method="POST" class="space-y-6">
            
            <div class="relative">
                <input type="text" name="user_id" id="user_id" required 
                    class="peer w-full px-4 pt-6 pb-2 bg-white border border-gray-300 rounded-lg placeholder-transparent focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    placeholder="รหัสพนักงาน" autocomplete="off" />
                <label for="user_id"
                    class="absolute left-4 top-2 text-gray-500 text-sm transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-green-600">
                    รหัสพนักงาน (Employee ID)
                </label>
            </div>
             <p class="text-xs text-gray-500 -mt-4 pl-1">
                 <i class="fas fa-info-circle mr-1"></i> รหัสพนักงานต้องเป็น **ตัวเลข** เท่านั้น
            </p>
            
            <div class="relative">
                <input type="text" name="username" id="username" required 
                    class="peer w-full px-4 pt-6 pb-2 bg-white border border-gray-300 rounded-lg placeholder-transparent focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    placeholder="ชื่อผู้ใช้" autocomplete="new-username" />
                <label for="username"
                    class="absolute left-4 top-2 text-gray-500 text-sm transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-green-600">
                    (Username) ชื่อผู้ใช้ สำหรับเข้าสู่ระบบ
                </label>
            </div>
            <p class="text-xs text-gray-500 -mt-4 pl-1">
                <i class="fas fa-info-circle mr-1"></i> ชื่อผู้ใช้ต้องเป็น **ภาษาอังกฤษ** ตัวเลข หรือสัญลักษณ์เท่านั้น
            </p>

            <div class="relative">
                <input type="password" name="password" id="password" required 
                    class="peer w-full px-4 pt-6 pb-2 pr-12 bg-white border border-gray-300 rounded-lg placeholder-transparent focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    placeholder="กำหนดรหัสผ่าน" autocomplete="new-password"
                    onkeyup="checkPasswordStrength(this.value)" /> <label for="password"
                    class="absolute left-4 top-2 text-gray-500 text-sm transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-green-600">
                    กำหนดรหัสผ่าน
                </label>
                <button type="button" onclick="togglePassword('password', this)" aria-label="แสดง/ซ่อนรหัสผ่าน"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-green-600 focus:outline-none password-toggle-btn">
                    <i class="fas fa-eye text-lg"></i>
                </button>
            </div>

            <ul id="password-feedback" class="password-check-list -mt-4 pl-1">
                <li id="len"><span class="icon"><i class="fas fa-circle text-xs"></i></span>อย่างน้อย 8 ตัวอักษร</li>
                <li id="lower"><span class="icon"><i class="fas fa-circle text-xs"></i></span>ตัวอักษรพิมพ์เล็ก (a-z)</li>
                <li id="upper"><span class="icon"><i class="fas fa-circle text-xs"></i></span>ตัวอักษรพิมพ์ใหญ่ (A-Z)</li>
                <li id="number"><span class="icon"><i class="fas fa-circle text-xs"></i></span>ตัวเลข (0-9)</li>
                <li id="special"><span class="icon"><i class="fas fa-circle text-xs"></i></span>อักขระพิเศษ (@!#$...)</li>
            </ul>
            <div class="relative">
                <input type="password" name="confirm_password" id="confirm_password" required 
                    class="peer w-full px-4 pt-6 pb-2 pr-12 bg-white border border-gray-300 rounded-lg placeholder-transparent focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    placeholder="ยืนยันรหัสผ่าน" autocomplete="new-password" />
                <label for="confirm_password"
                    class="absolute left-4 top-2 text-gray-500 text-sm transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-green-600">
                    ยืนยันรหัสผ่าน
                </label>
                <button type="button" onclick="togglePassword('confirm_password', this)" aria-label="แสดง/ซ่อนรหัสผ่าน"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-green-600 focus:outline-none password-toggle-btn">
                    <i class="fas fa-eye text-lg"></i>
                </button>
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl transition shadow-lg font-semibold tracking-wider transform hover:scale-[1.01] duration-200 mt-8">
                ลงทะเบียน <i class="fas fa-check-circle ml-2"></i>
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-500 space-x-4">
            <a href="../dashboard.php" class="text-gray-600 hover:text-green-600 hover:underline transition">หน้าหลัก</a> 
            <span class="text-gray-400">|</span>
            <a href="login.php" class="text-green-600 hover:text-green-700 hover:underline transition font-semibold">เข้าสู่ระบบ</a>
        </div>
        <p class="text-xs text-gray-400 text-center mt-4">© 2025 System. All rights reserved.</p>
    </div>

    <script>
        // ใช้ฟังก์ชัน togglePassword แบบเดียวกับหน้า Login
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            const isPassword = (input.type === 'password');
            
            input.type = isPassword ? 'text' : 'password';
            
            if (isPassword) {
                icon.classList.replace('fa-eye', 'fa-eye-slash');
                icon.title = 'ซ่อนรหัสผ่าน';
            } else {
                icon.classList.replace('fa-eye-slash', 'fa-eye');
                icon.title = 'แสดงรหัสผ่าน';
            }
        }

        // --- ฟังก์ชันใหม่: Password Strength Feedback (Real-time) ---
        function checkPasswordStrength(password) {
            // เงื่อนไขต้องตรงกับ PHP Regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$/
            const checks = {
                len: password.length >= 8,
                lower: /[a-z]/.test(password),
                upper: /[A-Z]/.test(password),
                number: /\d/.test(password),
                special: /[\W_]/.test(password) // รวมอักขระที่ไม่ใช่ตัวอักษรและตัวเลข
            };

            for (const rule in checks) {
                const li = document.getElementById(rule);
                const iconSpan = li.querySelector('.icon');
                
                // ล้าง class และ icon เดิม
                li.classList.remove('valid', 'invalid');
                iconSpan.innerHTML = '<i class="fas fa-circle text-xs"></i>'; // ตั้งค่า icon เริ่มต้น

                if (password.length > 0) {
                    if (checks[rule]) {
                        // เงื่อนไขถูกต้อง
                        li.classList.add('valid');
                        iconSpan.innerHTML = '<i class="fas fa-check"></i>'; 
                    } else {
                        // เงื่อนไขไม่ถูกต้อง (แต่มีการพิมพ์แล้ว)
                        li.classList.add('invalid');
                        iconSpan.innerHTML = '<i class="fas fa-xmark"></i>';
                    }
                } 
            }
        }
        // ----------------------------------------------------------------------

        // SweetAlert Feedback
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($error)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: '<?php echo str_replace("'", "\'", $error); ?>',
                    confirmButtonText: 'แก้ไข',
                    confirmButtonColor: '#dc2626' // Red
                });
            <?php elseif (!empty($success)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'ลงทะเบียนสำเร็จ!',
                    text: '<?php echo str_replace("'", "\'", $success); ?>',
                    // ไม่มีการ Redirect ด้วย JavaScript แล้ว เพราะเราทำด้วย PHP Header แล้ว
                    confirmButtonText: 'เข้าสู่ระบบ',
                    confirmButtonColor: '#10b981' // Green
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>