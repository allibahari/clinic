<?php
header('Content-Type: application/json');

require_once "../config.php"; // مسیر را بر اساس ساختار پوشه خود تنظیم کنید

$response = ['success' => false, 'message' => 'درخواست نامعتبر.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $appointment_id = (int)$_POST['id'];
    $status = $_POST['status'];

    // لیست وضعیت‌های مجاز
    $allowed_statuses = ['arrived', 'in_room', 'no_show', 'cancelled'];

    if (in_array($status, $allowed_statuses)) {
        try {
            $sql = "UPDATE appointments SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $appointment_id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'وضعیت با موفقیت به‌روزرسانی شد.';
            } else {
                $response['message'] = 'خطا در اجرای دستور دیتابیس.';
            }
            $stmt->close();
        } catch (Exception $e) {
            $response['message'] = 'خطای سرور: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'وضعیت ارسال شده مجاز نیست.';
    }

    $conn->close();
}

echo json_encode($response);
?>