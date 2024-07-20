<?php
include "config.php"; // برای اتصال به پایگاه داده

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $employee_id = $_POST['id'];

    // برای جلوگیری از حملات SQL Injection از prepared statements استفاده کنید
    $stmt = $conn->prepare("DELETE FROM employees1 WHERE id = ?");
    $stmt->bind_param("i", $employee_id);

    if ($stmt->execute()) {
        // هدایت به صفحه users.php با پیغام
        header("Location: users.php?message=User deleted successfully");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>
