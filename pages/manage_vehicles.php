<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/config.php';
requireRole(['admin','pkr']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once '../includes/head.php'; ?>
    <link rel="stylesheet" href="/kch-oil/assets/css/fuel-theme.css">
</head>
<body>
<?php require_once '../includes/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-car me-2"></i>จัดการข้อมูลรถ</h1>
                <p>เพิ่ม แก้ไข หรือลบข้อมูลรถในระบบ</p>
            </div>
            <button class="btn btn-fuel-accent" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="openAddModal()">
                <i class="fas fa-plus-circle me-2"></i>เพิ่มรถใหม่
            </button>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="content-card animate-in">
        <div class="card-body-custom">
            <div class="table-responsive">
                <table id="vehicleTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ทะเบียนรถ</th>
                            <th>รูปถ่าน</th>
                            <th>ชื่อรถ/ยี่ห้อ</th>
                            <th>ประเภทรถ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: เพิ่ม/แก้ไขรถ -->
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form class="modal-content" id="vehicleForm" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มรถใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <input type="hidden" name="action" id="formAction" value="add_vehicle">
            <input type="hidden" name="id" id="vehicleId">
            <div class="modal-body">
                    <div class="text-center mb-3">
                        <div id="imagePlaceholder" class="rounded bg-light d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 200px; height: 120px; cursor: pointer; overflow: hidden;" onclick="$('#vImage').click()">
                            <i class="fas fa-car fa-3x text-secondary" id="placeholderIcon"></i>
                            <img id="imagePreview" src="" class="d-none w-100 h-100" style="object-fit: cover;">
                        </div>
                        <div class="mt-2 small text-muted">คลิกเพื่ออัปโหลดรูปภาพ</div>
                        <input type="file" name="image" id="vImage" class="d-none" accept="image/*" onchange="previewVehicleImage(this)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ทะเบียนรถ <span class="text-danger">*</span></label>
                        <input type="text" name="plate_number" id="plateNumber" class="form-control" required placeholder="เช่น กข 1234">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อรถ/ยี่ห้อ</label>
                        <input type="text" name="vehicle_name" id="vehicleName" class="form-control" placeholder="เช่น Toyota Hilux Revo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ประเภทรถ</label>
                        <select name="vehicle_type" id="vehicleType" class="form-select">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="กระบะ">กระบะ</option>
                            <option value="เก๋ง">เก๋ง</option>
                            <option value="รถตู้">รถตู้</option>
                            <option value="รถบรรทุก">รถบรรทุก</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-fuel">บันทึกข้อมูล</button>
                </div>
        </form>
    </div>
</div>

<script>
const API_URL = '/kch-oil/pages/api/fuel_action.php';
let vehicleTable;
let vehicleModal;

$(document).ready(function() {
    // Initialize Modal
    vehicleModal = new bootstrap.Modal(document.getElementById('vehicleModal'));
    
    initTable();

    $('#vehicleForm').on('submit', function(e) {
        e.preventDefault();
        saveVehicle();
    });
});

function initTable() {
    vehicleTable = $('#vehicleTable').DataTable({
        ajax: {
            url: API_URL,
            data: { action: 'get_vehicles' },
            dataSrc: 'data'
        },
        columns: [
            { data: null, render: (d, t, r, m) => m.row + 1 },
            { data: 'plate_number', className: 'fw-bold' },
            { data: 'image', render: function(d) {
                if (d) {
                    const img = `/kch-oil/uploads/fuel/${d}`;
                    return `<img src="${img}" class="rounded shadow-sm" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="viewImage('${img}')">`;
                }
                return `<div class="rounded bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;"><i class="fas fa-car text-secondary" style="font-size: 14px;"></i></div>`;
            }},
            { data: 'vehicle_name' },
            { data: 'vehicle_type' },
            { data: 'is_active', render: d => d == 1 ? '<span class="badge bg-success">ใช้งาน</span>' : '<span class="badge bg-secondary">ปิดใช้งาน</span>' },
            { data: null, render: function(d) {
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="openEditModal(${JSON.stringify(d).replace(/"/g, '&quot;')})" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteVehicle(${d.id})" title="ลบ">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }}
        ],
        responsive: true,
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' }
    });
}

function previewVehicleImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result).removeClass('d-none');
            $('#placeholderIcon').addClass('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function viewImage(src) {
    Swal.fire({
        imageUrl: src,
        imageAlt: 'Vehicle Image',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: { image: 'rounded shadow' }
    });
}

function openAddModal() {
    $('#modalTitle').text('เพิ่มรถใหม่');
    $('#formAction').val('add_vehicle');
    $('#vehicleId').val('');
    $('#vehicleForm')[0].reset();
    $('#imagePreview').addClass('d-none');
    $('#placeholderIcon').removeClass('d-none');
}

function openEditModal(data) {
    $('#modalTitle').text('แก้ไขข้อมูลรถ');
    $('#formAction').val('update_vehicle');
    $('#vehicleId').val(data.id);
    $('#plateNumber').val(data.plate_number);
    $('#vehicleName').val(data.vehicle_name);
    $('#vehicleType').val(data.vehicle_type);
    
    if (data.image) {
        $('#imagePreview').attr('src', `/kch-oil/uploads/fuel/${data.image}`).removeClass('d-none');
        $('#placeholderIcon').addClass('d-none');
    } else {
        $('#imagePreview').addClass('d-none');
        $('#placeholderIcon').removeClass('d-none');
    }
    
    vehicleModal.show();
}

function saveVehicle() {
    const formData = new FormData($('#vehicleForm')[0]);
    $.ajax({
        url: API_URL,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                vehicleModal.hide();
                vehicleTable.ajax.reload();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }
    });
}

function deleteVehicle(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลอาจถูกปิดการใช้งานแทนหากมีประวัติการบันทึกน้ำมัน",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(API_URL, { action: 'delete_vehicle', id: id }, function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    vehicleTable.ajax.reload();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>
</body>
</html>
