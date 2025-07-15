<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <title>مدیریت نوبت‌ها</title>
    </head>
<body>
    <h1>لیست نوبت‌های رزرو شده</h1>
    <table>
        <thead>
            <tr>
                <th>پزشک</th>
                <th>بیمار</th>
                <th>موبایل</th>
                <th>زمان نوبت</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php
            require_once "config.php";
            // کوئری برای گرفتن نوبت‌ها به همراه نام پزشک
            $sql = "SELECT app.*, doc.name as doctor_name 
                    FROM appointments app 
                    JOIN doctors doc ON app.doctor_id = doc.id 
                    ORDER BY app.appointment_time DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['patient_mobile']) . "</td>";
                    echo "<td>" . (new DateTime($row['appointment_time']))->format('Y/m/d H:i') . "</td>";
                    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                    echo "<td><a href='confirm.php?id=" . $row['id'] . "'>تایید</a> | <a href='cancel.php?id=" . $row['id'] . "'>لغو</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>هیچ نوبتی یافت نشد.</td></tr>";
            }
            $conn->close();
            ?>
        </tbody>
    </table>
</body>
</html>