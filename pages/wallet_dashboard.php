<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// Fetch all active vehicles and their balances
$stmt = $conn_oil->query("SELECT * FROM oil_vehicles WHERE is_active = 1 ORDER BY current_balance ASC, plate_number ASC");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once '../includes/head.php'; ?>
    <link rel="stylesheet" href="/ok/kch-oil/assets/css/fuel-theme.css">
    <style>
        .wallet-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background: #fff;
        }
        .wallet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .wallet-card .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .wallet-image {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .wallet-image-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #94a3b8;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .balance-display {
            font-size: 2rem;
            font-weight: 700;
            margin: 1rem 0;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }
        .balance-positive { color: #10b981; }
        .balance-negative { color: #ef4444; }
        .balance-zero { color: #64748b; }
        .currency-label { font-size: 1rem; color: #64748b; font-weight: 500; }
    </style>
</head>
<body>
<?php require_once '../includes/nav.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-wallet me-2"></i>ระบบจัดการยอดเงินประจำรถ</h1>
                <p>ตรวจสอบยอดเงินคงเหลือ และเติมเงิน/ปรับยอดเงินของรถแต่ละคัน</p>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($vehicles as $v): 
            $balance = floatval($v['current_balance']);
            $balanceClass = $balance > 0 ? 'balance-positive' : ($balance < 0 ? 'balance-negative' : 'balance-zero');
            $imgSrc = !empty($v['image']) ? '/ok/kch-oil/uploads/fuel/' . htmlspecialchars($v['image']) : '';
        ?>
        <div class="col">
            <div class="wallet-card h-100">
                <div class="card-header">
                    <?php if ($imgSrc): ?>
                        <img src="<?= $imgSrc ?>" alt="Vehicle" class="wallet-image">
                    <?php else: ?>
                        <div class="wallet-image-placeholder"><i class="fas fa-car"></i></div>
                    <?php endif; ?>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($v['plate_number']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($v['vehicle_name']) ?: 'ไม่ระบุชื่อรถ' ?></small>
                    </div>
                </div>
                <div class="card-body text-center p-4">
                    <p class="text-muted mb-1 fw-semibold">ยอดเงินคงเหลือ / งบประมาณ</p>
                    <div class="balance-display justify-content-center <?= $balanceClass ?>">
                        <span><?= number_format($balance, 2) ?></span>
                        <span class="currency-label">บาท</span>
                    </div>
                    <button class="btn btn-fuel w-100 mt-3" onclick="openTopupModal(<?= $v['id'] ?>, '<?= htmlspecialchars($v['plate_number']) ?>')">
                        <i class="fas fa-coins me-2"></i>จัดการยอดเงิน
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: จัดการยอดเงิน -->
<div class="modal fade" id="topupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" id="topupForm">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-coins me-2"></i>จัดการยอดเงิน: <span id="modalPlateNumber" class="text-warning"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="add_fund">
                <input type="hidden" name="vehicle_id" id="topupVehicleId">
                
                <div class="alert alert-info border-0 bg-light text-dark">
                    <i class="fas fa-info-circle me-1 text-primary"></i> <b>การปรับยอด:</b> ใส่ตัวเลขบวกเพื่อเพิ่มเงิน หรือใส่เครื่องหมายลบ (เช่น -500) เพื่อลดจำนวนเงิน
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">จำนวนเงิน (บาท) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-baht-sign text-secondary"></i></span>
                        <input type="number" name="amount" class="form-control border-start-0 ps-0 fw-bold text-primary" step="0.01" required placeholder="เช่น 1000 หรือ -500">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">หมายเหตุ / คำอธิบาย</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="เช่น เติมงบรายเดือน, แก้ไขยอดผิดพลาด"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-fuel px-4"><i class="fas fa-save me-1"></i>บันทึกยอดเงิน</button>
            </div>
        </form>
    </div>
</div>

<script>
    const API_URL = '/ok/kch-oil/pages/api/fuel_action.php';
    let topupModalObj;

    $(document).ready(function() {
        topupModalObj = new bootstrap.Modal(document.getElementById('topupModal'));

        $('#topupForm').on('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            $.ajax({
                url: API_URL,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ข้อผิดพลาด', response.message || 'ไม่สามารถทำรายการได้', 'error');
                    }
                },
                error: function() {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                }
            });
        });
    });

    function openTopupModal(vehicleId, plateNumber) {
        $('#topupForm')[0].reset();
        $('#topupVehicleId').val(vehicleId);
        $('#modalPlateNumber').text(plateNumber);
        topupModalObj.show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>
