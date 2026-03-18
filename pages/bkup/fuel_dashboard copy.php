<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once '../includes/head.php'; ?>
    <link rel="stylesheet" href="/ok/kch-oil/assets/css/fuel-theme.css">
</head>
<body>
<?php require_once '../includes/nav.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-gas-pump me-2"></i>ระบบบันทึกค่าใช้จ่ายเติมน้ำมัน</h1>
                <p>บันทึก ติดตาม และจัดการค่าใช้จ่ายน้ำมันรถทุกคัน</p>
            </div>
            <button class="btn btn-fuel-accent btn-lg" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                <i class="fas fa-plus-circle me-2"></i>บันทึกเติมน้ำมัน
            </button>
        </div>
    </div>
</div>

<div class="container mb-5">

    <!-- Month Selector -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <label class="fw-semibold text-secondary mb-0">เดือน:</label>
        <input type="month" id="filterMonth" class="form-control" style="max-width: 200px; border-radius: 10px;" value="<?php echo date('Y-m'); ?>">
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4" id="summaryCards">
        <div class="col-6 col-lg-3 animate-in">
            <div class="summary-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="card-icon count"><i class="fas fa-clipboard-list"></i></div>
                    <div>
                        <div class="card-value" id="totalCount">0</div>
                        <div class="card-label">จำนวนครั้งเติม</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 animate-in">
            <div class="summary-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="card-icon cost"><i class="fas fa-baht-sign"></i></div>
                    <div>
                        <div class="card-value" id="totalCost">0</div>
                        <div class="card-label">ค่าใช้จ่ายรวม (บาท)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 animate-in">
            <div class="summary-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="card-icon fuel"><i class="fas fa-tint"></i></div>
                    <div>
                        <div class="card-value" id="totalLiters">0</div>
                        <div class="card-label">จำนวนลิตร</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3 animate-in">
            <div class="summary-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="card-icon avg"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div class="card-value" id="avgCost">0</div>
                        <div class="card-label">ค่าเฉลี่ยต่อครั้ง</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="content-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-list-alt me-2"></i>รายการเติมน้ำมัน</h5>
            <div class="d-flex gap-2 flex-wrap">
                <select id="filterVehicle" class="form-select form-select-sm" style="width: 180px; border-radius: 8px;">
                    <option value="">-- รถทุกคัน --</option>
                </select>
                <select id="filterDriver" class="form-select form-select-sm" style="width: 180px; border-radius: 8px;">
                    <option value="">-- คนขับทุกคน --</option>
                </select>
            </div>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table id="fuelTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>วันที่</th>
                            <th>ทะเบียนรถ</th>
                            <th>คนขับ</th>
                            <th>ประเภท</th>
                            <th>ลิตร</th>
                            <th>ราคา/ลิตร</th>
                            <th>ยอดรวม</th>
                            <th>ปั๊ม</th>
                            <th>บิล</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- Modal: เพิ่มข้อมูลเติมน้ำมัน -->
<!-- ============================================ -->
<div class="modal fade" id="addRecordModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gas-pump me-2"></i>บันทึกเติมน้ำมัน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="fuelForm" enctype="multipart/form-data">
                <div class="modal-body">
                    
                    <div class="row g-3">
                        <!-- รถ -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-car text-primary me-1"></i>เลือกรถ <span class="text-danger">*</span>
                            </label>
                            <select id="vehicleSelect" name="vehicle_id" class="form-select" required>
                                <option value="">-- เลือกรถ --</option>
                            </select>
                        </div>
                        <!-- คนขับ -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user text-success me-1"></i>เลือกคนขับ <span class="text-danger">*</span>
                            </label>
                            <select id="driverSelect" name="driver_id" class="form-select" required>
                                <option value="">-- เลือกคนขับ --</option>
                            </select>
                        </div>
                        <!-- วันที่ -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt text-info me-1"></i>วันที่เติม <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="fuel_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <!-- ประเภทน้ำมัน -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tint text-warning me-1"></i>ประเภทน้ำมัน
                            </label>
                            <select name="fuel_type" class="form-select">
                                <option value="">-- เลือก --</option>
                                <option value="ดีเซล">ดีเซล</option>
                                <option value="ดีเซล B7">ดีเซล B7</option>
                                <option value="ดีเซล B20">ดีเซล B20</option>
                                <option value="แก๊สโซฮอล์ 91">แก๊สโซฮอล์ 91</option>
                                <option value="แก๊สโซฮอล์ 95">แก๊สโซฮอล์ 95</option>
                                <option value="แก๊สโซฮอล์ E20">แก๊สโซฮอล์ E20</option>
                                <option value="แก๊สโซฮอล์ E85">แก๊สโซฮอล์ E85</option>
                                <option value="เบนซิน 95">เบนซิน 95</option>
                            </select>
                        </div>
                        <!-- ลิตร -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-flask text-secondary me-1"></i>จำนวนลิตร
                            </label>
                            <input type="number" name="liters" id="inputLiters" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <!-- ราคาต่อลิตร -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tag text-secondary me-1"></i>ราคาต่อลิตร (บาท)
                            </label>
                            <input type="number" name="price_per_liter" id="inputPrice" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <!-- ยอดรวม -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-baht-sign text-danger me-1"></i>ยอดรวม (บาท) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="total_cost" id="inputTotal" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <!-- เลขไมล์ -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tachometer-alt text-secondary me-1"></i>เลขไมล์
                            </label>
                            <input type="number" name="mileage" class="form-control" min="0" placeholder="0">
                        </div>
                        <!-- ปั๊ม -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>ชื่อปั๊มน้ำมัน
                            </label>
                            <input type="text" name="station_name" class="form-control" placeholder="เช่น ปตท., บางจาก, เชลล์">
                        </div>
                        <!-- หมายเหตุ -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-sticky-note text-info me-1"></i>หมายเหตุ
                            </label>
                            <textarea name="note" class="form-control" rows="2" placeholder="หมายเหตุ (ถ้ามี)"></textarea>
                        </div>
                        <!-- ถ่ายรูปบิล -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-camera text-primary me-1"></i>รูปบิลน้ำมัน
                            </label>
                            <div class="upload-area" id="uploadArea" onclick="document.getElementById('billImageInput').click()">
                                <i class="fas fa-cloud-upload-alt d-block"></i>
                                <span class="fw-semibold">คลิกเพื่อถ่ายรูป หรือเลือกรูปจากเครื่อง</span>
                                <br><small class="text-muted">รองรับ JPG, PNG, WEBP (สูงสุด 10MB)</small>
                            </div>
                            <input type="file" id="billImageInput" name="bill_image" accept="image/*" capture="environment" class="d-none">
                            
                            <!-- Preview -->
                            <div class="image-preview-container mt-3 d-none" id="imagePreviewContainer">
                                <img id="imagePreview" src="" alt="Bill Preview">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle" 
                                        onclick="clearImagePreview()" style="width:28px;height:28px;padding:0;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-fuel" id="btnSubmit">
                        <i class="fas fa-save me-1"></i>บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: ดูรูปบิล -->
<div class="modal fade" id="viewBillModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>รูปบิลน้ำมัน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="viewBillImage" src="" alt="Bill" class="img-fluid" style="border-radius: 0 0 1rem 1rem;">
            </div>
        </div>
    </div>
</div>

<script>
const API_URL = '/ok/kch-oil/pages/api/fuel_action.php';
const BILL_PATH = '/ok/kch-oil/pages/uploads/bills/';
let fuelTable;

// ============================================
// Initialize
// ============================================
$(document).ready(function() {
    loadVehicles();
    loadDrivers();
    loadSummary();
    initDataTable();

    // Select2 in modal
    $('#vehicleSelect, #driverSelect').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#addRecordModal'),
        width: '100%'
    });

    // Auto-calculate total
    $('#inputLiters, #inputPrice').on('input', function() {
        const liters = parseFloat($('#inputLiters').val()) || 0;
        const price = parseFloat($('#inputPrice').val()) || 0;
        if (liters > 0 && price > 0) {
            $('#inputTotal').val((liters * price).toFixed(2));
        }
    });

    // Month filter
    $('#filterMonth').on('change', function() {
        loadSummary();
        fuelTable.ajax.reload();
    });

    // Vehicle/Driver filter
    $('#filterVehicle, #filterDriver').on('change', function() {
        fuelTable.ajax.reload();
    });

    // Image preview
    $('#billImageInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                $('#imagePreview').attr('src', ev.target.result);
                $('#imagePreviewContainer').removeClass('d-none');
                $('#uploadArea').addClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    // Form submit
    $('#fuelForm').on('submit', function(e) {
        e.preventDefault();
        submitFuelRecord();
    });
});

// ============================================
// Load Vehicles & Drivers
// ============================================
function loadVehicles() {
    $.get(API_URL, { action: 'get_vehicles' }, function(res) {
        if (res.status === 'success') {
            let options = '<option value="">-- รถทุกคัน --</option>';
            let formOptions = '<option value="">-- เลือกรถ --</option>';
            res.data.forEach(v => {
                const label = v.plate_number + ' (' + (v.vehicle_name || '') + ')';
                options += `<option value="${v.id}">${label}</option>`;
                formOptions += `<option value="${v.id}">${label}</option>`;
            });
            $('#filterVehicle').html(options);
            $('#vehicleSelect').html(formOptions).trigger('change.select2');
        }
    }, 'json');
}

function loadDrivers() {
    $.get(API_URL, { action: 'get_drivers' }, function(res) {
        if (res.status === 'success') {
            let options = '<option value="">-- คนขับทุกคน --</option>';
            let formOptions = '<option value="">-- เลือกคนขับ --</option>';
            res.data.forEach(d => {
                options += `<option value="${d.id}">${d.driver_name}</option>`;
                formOptions += `<option value="${d.id}">${d.driver_name}</option>`;
            });
            $('#filterDriver').html(options);
            $('#driverSelect').html(formOptions).trigger('change.select2');
        }
    }, 'json');
}

// ============================================
// Load Summary
// ============================================
function loadSummary() {
    const month = $('#filterMonth').val();
    $.get(API_URL, { action: 'get_summary', month: month }, function(res) {
        if (res.status === 'success') {
            const s = res.summary;
            $('#totalCount').text(numberFormat(s.total_count));
            $('#totalCost').text(numberFormat(s.total_cost));
            $('#totalLiters').text(numberFormat(s.total_liters));
            $('#avgCost').text(numberFormat(s.avg_cost));
        }
    }, 'json');
}

// ============================================
// DataTable
// ============================================
function initDataTable() {
    fuelTable = $('#fuelTable').DataTable({
        ajax: {
            url: API_URL,
            data: function(d) {
                d.action = 'get_records';
                d.vehicle_id = $('#filterVehicle').val();
                d.driver_id = $('#filterDriver').val();
                // Use month filter for date range
                const month = $('#filterMonth').val();
                if (month) {
                    d.date_from = month + '-01';
                    const parts = month.split('-');
                    const lastDay = new Date(parts[0], parts[1], 0).getDate();
                    d.date_to = month + '-' + String(lastDay).padStart(2, '0');
                }
            },
            dataSrc: function(json) {
                return json.data || [];
            },
            type: 'GET'
        },
        columns: [
            { data: null, render: (d, t, r, m) => m.row + 1 },
            { data: 'fuel_date', render: d => formatThaiDate(d) },
            { data: null, render: d => `<span class="fw-semibold">${d.plate_number}</span><br><small class="text-muted">${d.vehicle_name || ''}</small>` },
            { data: 'driver_name' },
            { data: 'fuel_type', render: d => d ? `<span class="badge bg-info text-dark fuel-badge">${d}</span>` : '-' },
            { data: 'liters', render: d => d ? numberFormat(d) : '-' },
            { data: 'price_per_liter', render: d => d ? numberFormat(d) : '-' },
            { data: 'total_cost', render: d => `<span class="fw-bold text-success">${numberFormat(d)}</span>` },
            { data: 'station_name', render: d => d || '-' },
            { data: 'bill_image', render: function(d) {
                if (d) {
                    return `<img src="${BILL_PATH}${d}" class="bill-thumbnail" onclick="viewBill('${d}')" alt="bill">`;
                }
                return '<span class="text-muted">-</span>';
            }},
            { data: null, render: function(d) {
                return `
                    <button class="btn btn-action btn-outline-danger" onclick="deleteRecord(${d.id})" title="ลบ">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
            }}
        ],
        responsive: true,
        order: [[1, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
            infoEmpty: "ไม่พบรายการ",
            emptyTable: "ยังไม่มีข้อมูลการเติมน้ำมัน",
            paginate: { previous: "ก่อน", next: "ถัดไป" }
        },
        dom: '<"d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"Bf>rtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-1"></i> Excel',
                className: 'btn btn-sm btn-success',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                className: 'btn btn-sm btn-danger',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print me-1"></i> พิมพ์',
                className: 'btn btn-sm btn-dark',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
            }
        ],
        pageLength: 15,
    });
}

// ============================================
// Submit Form
// ============================================
function submitFuelRecord() {
    const formData = new FormData($('#fuelForm')[0]);
    formData.append('action', 'add_record');

    $('#btnSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>กำลังบันทึก...');

    $.ajax({
        url: API_URL,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                $('#addRecordModal').modal('hide');
                resetForm();
                fuelTable.ajax.reload();
                loadSummary();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        },
        complete: function() {
            $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-save me-1"></i>บันทึกข้อมูล');
        }
    });
}

// ============================================
// Delete Record
// ============================================
function deleteRecord(id) {
    Swal.fire({
        title: 'ยืนยันลบรายการ?',
        text: 'ข้อมูลที่ลบจะไม่สามารถกู้คืนได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> ลบ',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete_record', id: id }, function(res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1200, showConfirmButton: false });
                    fuelTable.ajax.reload();
                    loadSummary();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }, 'json');
        }
    });
}

// ============================================
// View Bill Image
// ============================================
function viewBill(filename) {
    $('#viewBillImage').attr('src', BILL_PATH + filename);
    new bootstrap.Modal(document.getElementById('viewBillModal')).show();
}

// ============================================
// Image Preview Helpers
// ============================================
function clearImagePreview() {
    $('#billImageInput').val('');
    $('#imagePreviewContainer').addClass('d-none');
    $('#uploadArea').removeClass('d-none');
}

function resetForm() {
    $('#fuelForm')[0].reset();
    $('input[name="fuel_date"]').val(new Date().toISOString().split('T')[0]);
    $('#vehicleSelect, #driverSelect').val('').trigger('change.select2');
    clearImagePreview();
}

// ============================================
// Utilities
// ============================================
function numberFormat(num) {
    return parseFloat(num || 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function formatThaiDate(dateStr) {
    if (!dateStr) return '-';
    const months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const parts = dateStr.split('-');
    const day = parseInt(parts[2]);
    const mon = parseInt(parts[1]);
    const year = parseInt(parts[0]) + 543;
    return `${day} ${months[mon]} ${year}`;
}
</script>

</body>
</html>
