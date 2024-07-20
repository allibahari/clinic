<?php
require_once "inc/nav.php";
include "config.php";

// کوئری SQL برای شمارش تعداد بیماران
$sql = "SELECT COUNT(*) AS patient_count FROM patients";
$result = $conn->query($sql);

// بررسی نتیجه کوئری
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $patient_count = $row['patient_count'];
} else {
    $patient_count = 0;
}

// کوئری SQL برای بازیابی آخرین ۵ لاگ لاگین
$sql_logs = "SELECT ip_address, login_time FROM login_logs ORDER BY login_time DESC LIMIT 5";
$result_logs = $conn->query($sql_logs);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="css/font.css">
    <style>
        .main-content {
            display: flex;
            flex-wrap: wrap;
        }
        .box {
            padding: 20px;
            border-radius: 3px;
            background-color: #fff;
            margin: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            height: 20%;
        }
        .patient-box {
            flex: 1 1 20%;
            max-width: 20%;
        }
        .logs-box {
            flex: 1 1 65%;
            max-width: 30%;
        }
        .table th, .table td ,  h3{
            text-align: right;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="box patient-box">
            <p>تعداد بیماران</p>
            <p><?php echo htmlspecialchars($patient_count, ENT_QUOTES, 'UTF-8'); ?> نفر</p>
        </div>

        <div class="box logs-box">
            <h3>لاگ‌های لاگین</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>آدرس IP</th>
                        <th>زمان لاگین</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_logs->num_rows > 0) {
                        while ($row_log = $result_logs->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row_log['ip_address'], ENT_QUOTES, 'UTF-8') . "</td>";
                            echo "<td>" . htmlspecialchars($row_log['login_time'], ENT_QUOTES, 'UTF-8') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>لاگی یافت نشد</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="js/jquery-3.4.1.min.js"></script>
    <script src="js/js.js"></script>
</body>
</html>
