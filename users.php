<?php
include "config.php";
include "inc/nav.php";

// Initialize search parameters
$search_first_name = isset($_GET['first_name']) ? $_GET['first_name'] : '';
$search_last_name = isset($_GET['last_name']) ? $_GET['last_name'] : '';
$search_national_code = isset($_GET['national_code']) ? $_GET['national_code'] : '';
$search_service_type = isset($_GET['service_type']) ? $_GET['service_type'] : '';

// Fetch user data from the database with search filters
$sql = "SELECT * FROM employees1 WHERE 1=1";

if (!empty($search_first_name)) {
    $sql .= " AND first_name LIKE '%" . $conn->real_escape_string($search_first_name) . "%'";
}
if (!empty($search_last_name)) {
    $sql .= " AND last_name LIKE '%" . $conn->real_escape_string($search_last_name) . "%'";
}
if (!empty($search_national_code)) {
    $sql .= " AND national_code LIKE '%" . $conn->real_escape_string($search_national_code) . "%'";
}
if (!empty($search_service_type)) {
    $sql .= " AND service_type LIKE '%" . $conn->real_escape_string($search_service_type) . "%'";
}

$result = $conn->query($sql);

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Function to render users table rows
function renderUserRows($users) {
    $html = '';
    if (!empty($users)) {
        foreach ($users as $user) {
            $html .= '<tr role="row" class="">
                        <td><a href="user-details.php?id=' . htmlspecialchars($user['id']) . '">' . htmlspecialchars($user['id']) . '</a></td>
                        <td><img src="' . htmlspecialchars($user['photo_path1']) . '" alt="تصویر کاربر" width="100"></td>
                        <td><a href="user-details.php?id=' . htmlspecialchars($user['id']) . '">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</a></td>
                        <td>' . htmlspecialchars($user['national_code']) . '</td>
                        <td>' . htmlspecialchars($user['mobile']) . '</td>
                        <td>' . htmlspecialchars($user['service_type']) . '</td>
                        <td>' . htmlspecialchars($user['created_at']) . '</td>
                        <td>
                            <a href="delete-user.php?id=' . htmlspecialchars($user['id']) . '" class="item-delete mlg-15" title="حذف" onclick="return confirm(\'آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟\');"></a>
                            <a href="employee_details.php?id=' . htmlspecialchars($user['id']) . '" target="_blank" class="item-eye mlg-15" title="مشاهده"></a>
                            <a href="edit_employee.php?id=' . htmlspecialchars($user['id']) . '" class="item-edit" title="ویرایش"></a>
                        </td>
                    </tr>';
        }
    } else {
        $html .= '<tr role="row" class=""><td colspan="8">هیچ کاربری یافت نشد.</td></tr>';
    }
    return $html;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    echo renderUserRows($users);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مرکز تخصصی کلینیک پوست و زیبایی</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="css/font.css">
    <script src="js/jquery-3.4.1.min.js"></script>
    <script>
        $(document).ready(function() {
            function fetchUsers() {
                $.ajax({
                    url: 'users.php',
                    type: 'GET',
                    data: {
                        ajax: 'true',
                        first_name: $('input[name="first_name"]').val(),
                        last_name: $('input[name="last_name"]').val(),
                        national_code: $('input[name="national_code"]').val(),
                        service_type: $('input[name="service_type"]').val()
                    },
                    success: function(response) {
                        $('#user-table-body').html(response);
                    }
                });
            }

            $('.t-header-searchbox input').on('input', function() {
                fetchUsers();
            });

            $('form').on('submit', function(e) {
                e.preventDefault();
                fetchUsers();
            });
        });
    </script>
</head>
<body>

    <div class="main-content font-size-13">
        <div class="tab__box">
            <div class="tab__items">
                <a class="tab__item is-active" href="users.php">همه کاربران</a>
                <a class="tab__item" href="employees.php">ایجاد کاربر جدید</a>
            </div>
        </div>
        <div class="container">
            <div class="d-flex flex-space-between item-center flex-wrap padding-30 border-radius-3 bg-white">
                <div class="t-header-search">
                    <form method="GET" action="users.php">
                        <div class="t-header-searchbox font-size-13">
                            <input type="text" class="text search-input__box font-size-13" placeholder="جستجوی کاربر">
                            <div class="t-header-search-content">
                                <input type="text" name="first_name" class="text" placeholder="نام" value="<?php echo htmlspecialchars($search_first_name); ?>">
                                <input type="text" name="last_name" class="text" placeholder="نام خانوادگی" value="<?php echo htmlspecialchars($search_last_name); ?>">
                                <input type="text" name="service_type" class="text" placeholder="نوع خدمات" value="<?php echo htmlspecialchars($search_service_type); ?>">
                                <input type="text" name="national_code" class="text margin-bottom-20" placeholder="کد ملی" value="<?php echo htmlspecialchars($search_national_code); ?>">
                                <button type="submit" class="btn btn-netcopy_net">جستجو</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table__box">
            <table class="table">
                <thead role="rowgroup">
                    <tr role="row" class="title-row">
                        <th>شناسه</th>
                        <th>تصویر زیباجو</th>
                        <th>نام و نام خانوادگی</th>
                        <th>کدملی</th>
                        <th>شماره موبایل</th>
                        <th>نوع خدمات</th>
                        <th>تاریخ ثبت نام</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                <?php echo renderUserRows($users); ?>
                </tbody>
            </table>
        </div>
    </div>
   
</body>
<script src="js/js.js"></script>
 <!-- Bootstrap JS and jQuery -->
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</html>
