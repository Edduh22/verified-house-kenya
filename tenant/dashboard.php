<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("tenant");

$tenant_id = currentUserId();
$full_name = currentUserName();

/*
|--------------------------------------------------------------------------
| Favourite count
|--------------------------------------------------------------------------
*/

$favouriteStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM favourites
    WHERE tenant_id = ?
");

$favouriteStmt->execute([$tenant_id]);

$favouriteCount = (int) $favouriteStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Viewing request count
|--------------------------------------------------------------------------
*/

$viewingStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM viewing_requests
    WHERE tenant_id = ?
");

$viewingStmt->execute([$tenant_id]);

$viewingCount = (int) $viewingStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Pending viewing requests
|--------------------------------------------------------------------------
*/

$pendingStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM viewing_requests
    WHERE tenant_id = ?
    AND status = 'pending'
");

$pendingStmt->execute([$tenant_id]);

$pendingCount = (int) $pendingStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Unread notifications
|--------------------------------------------------------------------------
*/

$notificationStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
    AND is_read = 0
");

$notificationStmt->execute([$tenant_id]);

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
        Tenant Dashboard - Verified House Kenya
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

    </style>

</head>

<body>

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a
            href="../index.php"
            class="navbar-brand fw-bold"
        >
            Verified House Kenya
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
            Welcome, <?= htmlspecialchars($full_name) ?>
        </h2>

        <p class="text-muted">
            Find your next home with Verified House Kenya.
        </p>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        My Favourites
                    </h6>

                    <h2 class="fw-bold">
                        <?= $favouriteCount ?>
                    </h2>

                    <a
                        href="favourites.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        View Favourites
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Viewing Requests
                    </h6>

                    <h2 class="fw-bold">
                        <?= $viewingCount ?>
                    </h2>

                    <a
                        href="bookings.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        View Requests
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        Pending Requests
                    </h6>

                    <h2 class="fw-bold">
                        <?= $pendingCount ?>
                    </h2>

                    <a
                        href="bookings.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Check Status
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- Main actions -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-3">
                Find Your Next Home
            </h4>

            <p class="text-muted">
                Search verified properties and contact landlords
                to arrange a viewing.
            </p>

            <a
                href="../search.php"
                class="btn btn-primary"
            >
                🔎 Search Houses
            </a>

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


    <!-- How it works -->

    <div class="card shadow-sm border-0">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-4">
                How Verified House Kenya Works
            </h4>

            <div class="row text-center">

                <div class="col-md-3 mb-3">

                    <h2>🔎</h2>

                    <h6>
                        Search
                    </h6>

                    <p class="text-muted small">
                        Search verified houses.
                    </p>

                </div>


                <div class="col-md-3 mb-3">

                    <h2>🏠</h2>

                    <h6>
                        Choose
                    </h6>

                    <p class="text-muted small">
                        View property details.
                    </p>

                </div>


                <div class="col-md-3 mb-3">

                    <h2>📅</h2>

                    <h6>
                        Request Viewing
                    </h6>

                    <p class="text-muted small">
                        Arrange a property viewing.
                    </p>

                </div>


                <div class="col-md-3 mb-3">

                    <h2>🔔</h2>

                    <h6>
                        Get Updates
                    </h6>

                    <p class="text-muted small">
                        Receive notifications.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>