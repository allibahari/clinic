<?php
include_once "config.php"; // اتصال به دیتابیس

$results = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $first_name = isset($_GET['first_name']) ? $_GET['first_name'] : '';
    $last_name = isset($_GET['last_name']) ? $_GET['last_name'] : '';
    $birth_date = isset($_GET['birth_date']) ? $_GET['birth_date'] : '';
    $national_code = isset($_GET['national_code']) ? $_GET['national_code'] : '';

    // ساخت کوئری جستجو
    $query = "SELECT * FROM employees1 WHERE 1=1";
    $params = [];

    if (!empty($first_name)) {
        $query .= " AND first_name LIKE ?";
        $params[] = "%" . $first_name . "%";
    }

    if (!empty($last_name)) {
        $query .= " AND last_name LIKE ?";
        $params[] = "%" . $last_name . "%";
    }

    if (!empty($birth_date)) {
        $query .= " AND birth_date LIKE ?";
        $params[] = "%" . $birth_date . "%";
    }

    if (!empty($national_code)) {
        $query .= " AND national_code LIKE ?";
        $params[] = "%" . $national_code . "%";
    }

    $stmt = $conn->prepare($query);

    if ($stmt) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
