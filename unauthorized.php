<?php
session_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สิทธิ์ไม่เพียงพอ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-red-50 flex items-center justify-center min-h-screen">

    <script>
    Swal.fire({
        icon: 'error',
        title: 'สิทธิ์ไม่เพียงพอ',
        text: 'คุณไม่มีสิทธิ์ในการเข้าถึงหน้านี้',
        confirmButtonText: 'ตกลง'
    }).then(() => {
        window.location.href = 'index.php';
    });
</script>

</body>
</html>
