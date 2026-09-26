<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

$userId = currentUserId();

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| GET CURRENT ADMIN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, full_name, email, phone, role, status, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$admin = $stmt->fetch();

if (!$admin) {
    die("Admin account not found.");
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    $fullName = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($fullName === "") {

        $error = "Full name is required.";

    } else {

        $stmt = $pdo->prepare("
            UPDATE users
            SET full_name = ?, phone = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $phone,
            $userId
        ]);

        $_SESSION["full_name"] = $fullName;

        $message = "Profile updated successfully.";

        $admin["full_name"] = $fullName;
        $admin["phone"] = $phone;
    }
}


/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {

    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {

        $error = "Please fill in all password fields.";

    } elseif (!password_verify($currentPassword, $admin["password"] ?? "")) {

        /*
        | Password is fetched separately below if needed.
        */

        $passwordStmt = $pdo->prepare("
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $passwordStmt->execute([$userId]);

        $passwordData = $passwordStmt->fetch();

        if (!$passwordData || !password_verify($currentPassword, $passwordData["password"])) {

            $error = "Current password is incorrect.";

        }

    }

    if ($error === "" && strlen($newPassword) < 8) {

        $error = "New password must be at least 8 characters.";

    }

    if ($error === "" && $newPassword !== $confirmPassword) {

        $error = "New passwords do not match.";

    }

    if ($error === "") {

        $passwordStmt = $pdo->prepare("
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $passwordStmt->execute([$userId]);

        $passwordData = $passwordStmt->fetch();

        if (!$passwordData || !password_verify($currentPassword, $passwordData["password"])) {

            $error = "Current password is incorrect.";

        } else {

            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $updatePassword = $pdo->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            $updatePassword->execute([
                $hashedPassword,
                $userId
            ]);

            $message = "Password changed successfully.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| UNREAD NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$notificationStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
    AND is_read = 0
");

$notificationStmt->execute([$userId]);

$unreadNotifications = $notificationStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Settings - Verified House Kenya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: #09234d;
            position: fixed;
            left: 0;
            top: 0;
            padding: 25px 15px;
        }

        .sidebar h4 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .sidebar a {
            display: block;
            color: #dce6f7;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 6px;
        }

        .sidebar a:hover {
            background: #1769d1;
            color: white;
        }

        .sidebar a.active {
            background: #1769d1;
            color: white;
        }

        .logout-link {
            margin-top: 30px;
        }

        .main {
            margin-left: 240px;
            padding: 25px;
        }

        .topbar {
            background: white;
            padding: 18px 22px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h3 {
            margin: 0;
            color: #09234d;
        }

        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .settings-card h5 {
            color: #09234d;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .form-label {
            font-weight: 600;
        }

        .notification-badge {
            background: #dc3545;
            color: white;
            font-size: 11px;
            padding: 3px 7px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .info-box {
            background: #f1f5fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        @media(max-width: 768px) {

            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <h4>Verified House</h4>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="../notifications.php">

        Notifications

        <?php if ($unreadNotifications > 0): ?>

            <span class="notification-badge">
                <?= $unreadNotifications ?>
            </span>

        <?php endif; ?>

    </a>

    <a href="users.php">
        Users
    </a>

    <a href="properties.php">
        Properties
    </a>

    <a href="verification.php">
        Verification
    </a>

    <a href="reports.php">
        Reports
    </a>

    <a href="viewing-requests.php">
        Viewing Requests
    </a>

    <a href="settings.php" class="active">
        Settings
    </a>

    <a href="../logout.php" class="logout-link">
        Logout
    </a>

</div>


<!-- MAIN -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h3>
            Admin Settings
        </h3>

        <div>

            <strong>
                <?= htmlspecialchars($admin["full_name"]) ?>
            </strong>

        </div>

    </div>


    <!-- MESSAGES -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <div class="row">


        <!-- PROFILE -->

        <div class="col-lg-7">

            <div class="settings-card">

                <h5>
                    Account Information
                </h5>

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?= htmlspecialchars($admin["full_name"]) ?>"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Email Address
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            value="<?= htmlspecialchars($admin["email"]) ?>"
                            readonly
                        >

                        <small class="text-muted">
                            Email address cannot be changed here.
                        </small>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($admin["phone"] ?? "") ?>"
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Account Role
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="Administrator"
                            readonly
                        >

                    </div>


                    <button
                        type="submit"
                        name="update_profile"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                </form>

            </div>

        </div>


        <!-- ACCOUNT STATUS -->

        <div class="col-lg-5">

            <div class="settings-card">

                <h5>
                    Account Status
                </h5>

                <div class="info-box">

                    <p class="mb-2">
                        <strong>Status:</strong>

                        <?php if ($admin["status"] === "active"): ?>

                            <span class="badge bg-success">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger">
                                <?= htmlspecialchars($admin["status"]) ?>
                            </span>

                        <?php endif; ?>

                    </p>


                    <p class="mb-2">

                        <strong>Account ID:</strong>

                        <?= htmlspecialchars($admin["id"]) ?>

                    </p>


                    <p class="mb-0">

                        <strong>Created:</strong>

                        <?= htmlspecialchars($admin["created_at"]) ?>

                    </p>

                </div>

            </div>

        </div>


    </div>


    <!-- CHANGE PASSWORD -->

    <div class="settings-card">

        <h5>
            Change Password
        </h5>

        <p class="text-muted">
            Use a strong password with at least 8 characters.
        </p>

        <form method="POST">

            <div class="row">

                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Current Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        class="form-control"
                        required
                    >

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        New Password
                    </label>

                    <input
                        type="password"
                        name="new_password"
                        class="form-control"
                        minlength="8"
                        required
                    >

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        minlength="8"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                name="change_password"
                class="btn btn-primary"
            >
                Change Password
            </button>

        </form>

    </div>


    <!-- SYSTEM INFORMATION -->

    <div class="settings-card">

        <h5>
            System Information
        </h5>

        <div class="row">

            <div class="col-md-4">

                <div class="info-box">

                    <strong>
                        System
                    </strong>

                    <br>

                    Verified House Kenya

                </div>

            </div>


            <div class="col-md-4">

                <div class="info-box">

                    <strong>
                        User Role
                    </strong>

                    <br>

                    Administrator

                </div>

            </div>


            <div class="col-md-4">

                <div class="info-box">

                    <strong>
                        Database
                    </strong>

                    <br>

                    verified_house

                </div>

            </div>

        </div>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>