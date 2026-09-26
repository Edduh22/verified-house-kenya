<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

$admin_id = currentUserId();
$full_name = currentUserName();

/*
|--------------------------------------------------------------------------
| Total Users
|--------------------------------------------------------------------------
*/

$userStmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
");

$totalUsers = (int) $userStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Total Properties
|--------------------------------------------------------------------------
*/

$propertyStmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM properties
");

$totalProperties = (int) $propertyStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Pending Verification
|--------------------------------------------------------------------------
*/

$verificationStmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM properties
    WHERE verification_status = 'pending'
");

$pendingVerification = (int) $verificationStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Unread Notifications
|--------------------------------------------------------------------------
*/

$notificationStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
    AND is_read = 0
");

$notificationStmt->execute([$admin_id]);

$unreadNotifications = (int) $notificationStmt->fetch()["total"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Admin Dashboard - Verified House Kenya
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .dashboard-card {
            border: none;
            border-radius: 14px;
            transition: 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
        }

        .notification-link {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            min-width: 22px;
            height: 22px;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }

        .admin-header {
            background: #09234d;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-dark admin-header">

    <div class="container">

        <a
            href="../index.php"
            class="navbar-brand fw-bold"
        >
            Verified House Kenya
            <span class="small ms-2">
                | Admin
            </span>
        </a>


        <div class="d-flex align-items-center gap-3">

            <a
                href="../notifications.php"
                class="btn btn-light btn-sm notification-link"
            >

                🔔 Notifications

                <?php if ($unreadNotifications > 0): ?>

                    <span class="notification-badge">
                        <?= $unreadNotifications ?>
                    </span>

                <?php endif; ?>

            </a>


            <a
                href="../logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<div class="container py-5">

    <div class="mb-4">

        <h2 class="fw-bold">
            Admin Dashboard
        </h2>

        <p class="text-muted">
            Welcome, <?= htmlspecialchars($full_name) ?>.
            Manage Verified House Kenya from here.
        </p>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Users
                    </h6>

                    <h2 class="fw-bold">
                        <?= $totalUsers ?>
                    </h2>

                    <a
                        href="users.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Manage Users
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Total Properties
                    </h6>

                    <h2 class="fw-bold">
                        <?= $totalProperties ?>
                    </h2>

                    <a
                        href="properties.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Manage Properties
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Pending Verification
                    </h6>

                    <h2 class="fw-bold">
                        <?= $pendingVerification ?>
                    </h2>

                    <a
                        href="verification.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Review Properties
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- Management -->

    <div class="row g-4 mb-4">


        <div class="col-md-6">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body p-4">

                    <h4 class="fw-bold">
                        Property Verification
                    </h4>

                    <p class="text-muted">
                        Review properties submitted by landlords
                        and verify or reject listings.
                    </p>

                    <a
                        href="verification.php"
                        class="btn btn-primary"
                    >
                        Open Verification
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body p-4">

                    <h4 class="fw-bold">
                        Reports
                    </h4>

                    <p class="text-muted">
                        View system reports and platform activity.
                    </p>

                    <a
                        href="reports.php"
                        class="btn btn-outline-primary"
                    >
                        View Reports
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- Notifications -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h4 class="fw-bold mb-1">
                        Notifications
                    </h4>

                    <p class="text-muted mb-0">

                        <?php if ($unreadNotifications > 0): ?>

                            You have
                            <strong>
                                <?= $unreadNotifications ?>
                            </strong>
                            unread notification(s).

                        <?php else: ?>

                            You have no unread notifications.

                        <?php endif; ?>

                    </p>

                </div>


                <a
                    href="../notifications.php"
                    class="btn btn-outline-primary"
                >
                    View Notifications
                </a>

            </div>

        </div>

    </div>


    <!-- Quick Actions -->

    <div class="card shadow-sm border-0">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-4">
                Quick Actions
            </h4>

            <div class="d-flex flex-wrap gap-2">

                <a
                    href="users.php"
                    class="btn btn-outline-primary"
                >
                    Users
                </a>

                <a
                    href="properties.php"
                    class="btn btn-outline-primary"
                >
                    Properties
                </a>

                <a
                    href="verification.php"
                    class="btn btn-outline-primary"
                >
                    Verification
                </a>

                <a
                    href="reports.php"
                    class="btn btn-outline-primary"
                >
                    Reports
                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>