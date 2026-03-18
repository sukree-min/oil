console.log('JS loaded');

// NEW LOGIC: ปุ่ม SCROLL TO TOP
document.addEventListener('DOMContentLoaded', () => {

    const scrollToTopBtn = document.getElementById('scrollToTopBtn');

    // 1. ฟังก์ชัน: แสดง/ซ่อนปุ่ม เมื่อเลื่อนลงมาเกิน 300px
    const scrollFunction = () => {
        if (scrollToTopBtn) {
            // ตรวจสอบทั้ง Body และ Document Element สำหรับ Cross-Browser Compatibility
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollToTopBtn.classList.remove('hidden');
            } else {
                scrollToTopBtn.classList.add('hidden');
            }
        }
    };

    // 2. ผูก Event Listener เมื่อมีการเลื่อนหน้าจอ
    window.onscroll = function () { scrollFunction() };

    // 3. ผูก Event Listener เมื่อคลิกปุ่ม
    if (scrollToTopBtn) {
        scrollToTopBtn.addEventListener('click', () => {
            // สั่งให้หน้าเว็บเลื่อนขึ้นไปด้านบนอย่างนุ่มนวล
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ... (ถ้ามีโค้ด Initialization อื่นๆ ในหน้านั้น ให้วางต่อตรงนี้) ...
});

/**
 * ฟังก์ชันสำหรับแสดง SweetAlert Loading และเปลี่ยนหน้า
 */
function processMenuClick(menuName, url) {
    Swal.fire({
        title: 'กำลังโหลดข้อมูล',
        html: `กรุณารอสักครู่... กำลังนำท่านไปยังเมนู <b>${menuName}</b>`,
        icon: 'info',
        showConfirmButton: false,
        timer: 1000,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        },
        willClose: () => {
            window.location.href = url;
        }
    });
    return false;
}

/**
* ฟังก์ชันสำหรับยืนยันและดำเนินการ Logout
*/
function confirmLogout() {
    Swal.fire({
        title: 'ยืนยันออกจากระบบ?',
        text: "ท่านจะถูกนำกลับไปหน้าเข้าสู่ระบบ",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-sign-out-alt"></i> ออกจากระบบ',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // *** สำคัญ: ตรวจสอบเส้นทางไปยังไฟล์ Logout PHP ***
            // ถ้าหน้าปัจจุบันอยู่ลึกกว่าไฟล์ logout สองระดับ (../../) ก็ใช้เส้นทางเดิม
            window.location.href = '/ok/kch-oil/auth/logout.php';
        }
    });
}