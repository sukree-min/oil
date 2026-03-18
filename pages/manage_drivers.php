<?php
require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/config.php';
requireRole(['1','5','6']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once '../includes/head.php'; ?>
    <link rel="stylesheet" href="/ok/kch-oil/assets/css/fuel-theme.css">
</head>
<body>
<?php require_once '../includes/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-user-tie me-2"></i>จัดการข้อมูลคนขับ</h1>
                <p>เพิ่ม แก้ไข หรือลบข้อมูลพนักงานขับรถในระบบ</p>
            </div>
            <button class="btn btn-fuel-accent" data-bs-toggle="modal" data-bs-target="#driverModal" onclick="openAddModal()">
                <i class="fas fa-plus-circle me-2"></i>เพิ่มคนขับใหม่
            </button>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="content-card animate-in">
        <div class="card-body-custom">
            <div class="table-responsive">
                <table id="driverTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>คนขับ</th>
                            <th>ชื่อคนขับ</th>
                            <th>เบอร์โทรศัพท์</th>
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

<!-- Modal: เพิ่ม/แก้ไขคนขับ -->
<div class="modal fade" id="driverModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form class="modal-content" id="driverForm" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มคนขับใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <input type="hidden" name="action" id="formAction" value="add_driver">
            <input type="hidden" name="id" id="driverId">
            <div class="modal-body">
                    <div class="text-center mb-3">
                        <div id="imagePlaceholder" class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 120px; height: 120px; cursor: pointer; overflow: hidden;" onclick="$('#dImage').click()">
                            <i class="fas fa-user fa-3x text-secondary" id="placeholderIcon"></i>
                            <img id="imagePreview" src="" class="d-none w-100 h-100" style="object-fit: cover;">
                        </div>
                        <div class="mt-2 small text-muted">คลิกเพื่ออัปโหลดรูปภาพ</div>
                        <input type="file" name="image" id="dImage" class="d-none" accept="image/*" onchange="previewDriverImage(this)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อคนขับ <span class="text-danger">*</span></label>
                        <input type="text" name="driver_name" id="driverName" class="form-control" required placeholder="เช่น สมชาย ใจดี">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="เช่น 081-234-5678">
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
const API_URL = '/ok/kch-oil/pages/api/fuel_action.php';
let driverTable;
let driverModal;

$(document).ready(function() {
    // Initialize Modal
    driverModal = new bootstrap.Modal(document.getElementById('driverModal'));
    
    initTable();

    $('#driverForm').on('submit', function(e) {
        e.preventDefault();
        saveDriver();
    });
});

function initTable() {
    driverTable = $('#driverTable').DataTable({
        ajax: {
            url: API_URL,
            data: { action: 'get_drivers' },
            dataSrc: 'data'
        },
        columns: [
            { data: null, render: (d, t, r, m) => m.row + 1 },
            { data: 'image', render: function(d) {
                if (d) {
                    const img = `/ok/kch-oil/uploads/fuel/${d}`;
                    return `<img src="${img}" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" onclick="viewImage('${img}')">`;
                }
                return `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;"><i class="fas fa-user text-secondary" style="font-size: 12px;"></i></div>`;
            }},
            { data: 'driver_name', className: 'fw-bold' },
            { data: 'phone' },
            { data: 'is_active', render: d => d == 1 ? '<span class="badge bg-success">ใช้งาน</span>' : '<span class="badge bg-secondary">ปิดใช้งาน</span>' },
            { data: null, render: function(d) {
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary btn-action" onclick="openEditModal(${JSON.stringify(d).replace(/"/g, '&quot;')})" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteDriver(${d.id})" title="ลบ">
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

function previewDriverImage(input) {
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
        imageAlt: 'Driver Image',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: { image: 'rounded-circle shadow' }
    });
}

function openAddModal() {
    $('#modalTitle').text('เพิ่มคนขับใหม่');
    $('#formAction').val('add_driver');
    $('#driverId').val('');
    $('#driverForm')[0].reset();
    $('#imagePreview').addClass('d-none');
    $('#placeholderIcon').removeClass('d-none');
}

function openEditModal(data) {
    $('#modalTitle').text('แก้ไขข้อมูลคนขับ');
    $('#formAction').val('update_driver');
    $('#driverId').val(data.id);
    $('#driverName').val(data.driver_name);
    $('#phone').val(data.phone);
    
    if (data.image) {
        $('#imagePreview').attr('src', `/ok/kch-oil/uploads/fuel/${data.image}`).removeClass('d-none');
        $('#placeholderIcon').addClass('d-none');
    } else {
        $('#imagePreview').addClass('d-none');
        $('#placeholderIcon').removeClass('d-none');
    }
    
    }
    
    driverModal.show();
}

function saveDriver() {
    const formData = new FormData($('#driverForm')[0]);
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
                driverModal.hide();
                driverTable.ajax.reload();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }
    });
}

function deleteDriver(id) {
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
            $.post(API_URL, { action: 'delete_driver', id: id }, function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    driverTable.ajax.reload();
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
