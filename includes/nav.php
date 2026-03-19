<?php
$user_id = $_SESSION['user_id'] ?? null; 
$user_name = $_SESSION['username'] ?? 'ผู้ใช้งานทั่วไป'; 
$user_role = $_SESSION['role_name'] ?? 'ไม่ได้เข้าสู่ระบบ'; 
?>
<script>
    window.isLoggedIn = <?php echo $user_id ? 'true' : 'false'; ?>;
</script>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a3d 100%);">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/ok/kch-oil/pages/fuel_dashboard.php">
            <i class="fas fa-gas-pump me-2"></i>ระบบบันทึกน้ำมัน
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/ok/kch-oil/pages/fuel_dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> แดชบอร์ด
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/ok/kch-oil/pages/wallet_dashboard.php">
                        <i class="fas fa-wallet me-1"></i> ยอดเงินประจำรถ
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageBasicData" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-database me-1"></i> จัดการข้อมูลพื้นฐาน
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="manageBasicData" style="background: #1e3a5f; border: none; border-radius: 10px;">
                        <li>
                            <a class="dropdown-item py-2" href="/ok/kch-oil/pages/manage_vehicles.php">
                                <i class="fas fa-car me-2"></i> จัดการข้อมูลรถ
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="/ok/kch-oil/pages/manage_drivers.php">
                                <i class="fas fa-user-tie me-2"></i> จัดการคนขับ
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/kch-all/index.php">
                        <i class="fas fa-home me-1"></i> HOME
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end text-white d-none d-sm-block">
                    <div class="fw-semibold small"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="text-white-50 small">สถานะ: <?php echo htmlspecialchars($user_role); ?></div>
                </div>
                
                <?php if ($user_id): ?>
                    <button onclick="confirmLogout()" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                    </button>
                <?php else: ?>
                    <a href="/ok/kch-oil/auth/login.php" class="btn btn-success btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> เข้าสู่ระบบ
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>