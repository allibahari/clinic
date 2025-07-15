<?php
header('Content-Type: application/json'); // همیشه پاسخ را به صورت JSON برگردان
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "config.php"; // فایل اتصال به دیتابیس

// --- یک تابع برای ارسال پاسخ استاندارد JSON ---
function json_response($success, $message = '', $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// بررسی وجود اکشن در درخواست
$action = $_REQUEST['action'] ?? null;

if (!$action) {
    json_response(false, 'هیچ عملیاتی مشخص نشده است.');
}

// --- مدیریت اکشن‌های مختلف ---
switch ($action) {
    // --- دریافت لیست هزینه‌ها ---
    case 'get_costs':
        $employee_id = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT);
        if (!$employee_id) {
            json_response(false, 'شناسه کارمند نامعتبر است.');
        }

        try {
            $stmt = $conn->prepare("SELECT id, item_name, quantity, price FROM employee_costs WHERE employee_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $costs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            json_response(true, 'هزینه‌ها با موفقیت دریافت شد.', $costs);
        } catch (Exception $e) {
            json_response(false, 'خطای دیتابیس: ' . $e->getMessage());
        }
        break;

    // --- افزودن هزینه جدید ---
    case 'add_cost':
        $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
        $item_name = trim($_POST['item_name'] ?? '');
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

        if (!$employee_id || empty($item_name) || $quantity === false || $price === false) {
            json_response(false, 'اطلاعات ورودی ناقص یا نامعتبر است.');
        }

        try {
            $stmt = $conn->prepare("INSERT INTO employee_costs (employee_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isid", $employee_id, $item_name, $quantity, $price);
            if ($stmt->execute()) {
                json_response(true, 'هزینه با موفقیت ثبت شد.');
            } else {
                json_response(false, 'خطا در ثبت هزینه.');
            }
            $stmt->close();
        } catch (Exception $e) {
            json_response(false, 'خطای دیتابیس: ' . $e->getMessage());
        }
        break;

    // --- حذف هزینه ---
    case 'delete_cost':
        $cost_id = filter_input(INPUT_POST, 'cost_id', FILTER_VALIDATE_INT);
        if (!$cost_id) {
            json_response(false, 'شناسه هزینه نامعتبر است.');
        }

        try {
            $stmt = $conn->prepare("DELETE FROM employee_costs WHERE id = ?");
            $stmt->bind_param("i", $cost_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    json_response(true, 'هزینه با موفقیت حذف شد.');
                } else {
                    json_response(false, 'هزینه‌ای برای حذف یافت نشد.');
                }
            } else {
                json_response(false, 'خطا در حذف هزینه.');
            }
            $stmt->close();
        } catch (Exception $e) {
            json_response(false, 'خطای دیتابیس: ' . $e->getMessage());
        }
        break;

    default:
        json_response(false, 'عملیات نامعتبر است.');
        break;
}

$conn->close();
?>