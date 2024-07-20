<?php
require_once "user_info.php";
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مرکز تخصصی کلینیک پوست و زیبایی</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive_991.css" media="(max-width:991px)">
    <link rel="stylesheet" href="../css/responsive_768.css" media="(max-width:768px)">
    <link rel="stylesheet" href="../css/font.css">
</head>
<body>
<div class="sidebar__nav border-top border-left">
    <span class="bars d-none padding-0-18"></span>
    <div class="profile__info border cursor-pointer text-center">
        <div class="avatar__img">
            <img src="img/pro.jpg" class="avatar___img">
            <div class="v-dialog__container" style="display: block;"></div>
            <div class="box__camera default__avatar"></div>
        </div>
        <span class="profile__name">کاربر : <?php echo htmlspecialchars($username); ?> خوش آمدید</span>
    </div>


    <ul>
        <li class="item-li i-dashboard is-active"><a href="dashboard.php">پیشخوان</a></li>
        <li class="item-li i-users"><a href="users.php"> زیباجویان </a></li>
        <li class="item-li i-categories"><a href="employees.php"> افزودن زیباجو </a></li>
        <li class="item-li i-courses "><a href="add_admin.php"> افزودن مدیر </a></li>
        <!-- <li class="item-li i-user__inforamtion"><a href="user-information.html">اطلاعات کاربری</a></li> -->
        <!-- <li class="item-li i-banners"><a href="banners.html">  گزارش ها</a></li>  -->
        </li>
    </ul>
</div>
<div class="content">
    <div class="header d-flex item-center bg-white width-100 border-bottom padding-12-30">
        <div class="header__right d-flex flex-grow-1 item-center">
            <span class="bars"></span>
        </div>
        <div class="header__left d-flex flex-end item-center margin-top-2">
            <div class="notification margin-15">
                <a class="notification__icon"></a>
                <div class="dropdown__notification">
                    <div class="content__notification">
                        <span class="font-size-13">موردی برای نمایش وجود ندارد</span>
                    </div>
                </div>
            </div>
            <a href="logout.php" class="logout" title="خروج"></a>
        </div>
    </div>
    <div class="breadcrumb">
        <ul>
            <li><a href="dashboard.php" title="پیشخوان">پیشخوان</a></li>
        </ul>
    </div>