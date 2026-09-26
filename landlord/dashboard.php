<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("landlord");

$landlord_id = currentUserId();
$full_name = currentUserName();

/*
|--------------------------------------------------------------------------
| Property count
|--------------------------------------------------------------------------
*/

$propertyStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM properties
    WHERE landlord_id = ?
");

$propertyStmt->execute([$landlord_id]);

$propertyCount = (int) $propertyStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Pending verification count
|--------------------------------------------------------------------------
*/

$pendingStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM properties
    WHERE landlord_id = ?
    AND verification_status = 'pending'
");

$pendingStmt->execute([$landlord_id]);

$pendingProperties = (int) $pendingStmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Viewing request count
|--------------------------------------------------------------------------
*/

$viewingStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM viewing_requests
    WHERE landlord_id = ?
");

$viewingStmt->execute([$landlord_id]);

$viewingCount = (int) $viewingStmt->fetch()["total"];


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

$notificationStmt->execute([$landlord_id]);

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
        Landlord Dashboard - Verified House Kenya
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
            Manage your properties and viewing requests.
        </p>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-4">


        <div class="col-md-4">

            <div class="card dashboard-card shadow-sm">

                <div class="card-body">

                    <h6 class="text-muted">
                        My Properties
                    </h6>

                    <h2 class="fw-bold">
                        <?= $propertyCount ?>
                    </h2>

                    <a
                        href="my-properties.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        View Properties
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
                        <?= $pendingProperties ?>
                    </h2>

                    <a
                        href="my-properties.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Check Properties
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
                        href="viewing-requests.php"
                        class="btn btn-outline-primary btn-sm"
                    >
                        View Requests
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- Main Actions -->

    <div class="row g-4 mb-4">


        <div class="col-md-6">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body p-4">

                    <h4 class="fw-bold">
                        Add a Property
                    </h4>

                    <p class="text-muted">
                        List your house, apartment, bedsitter,
                        studio or other property.
                    </p>

                    <a
                        href="add-property.php"
                        class="btn btn-primary"
                    >
                        + Add Property
                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card dashboard-card shadow-sm h-100">

                <div class="card-body p-4">

                    <h4 class="fw-bold">
                        Manage Properties
                    </h4>

                    <p class="text-muted">
                        Edit your listings and check their
                        verification status.
                    </p>

                    <a
                        href="my-properties.php"
                        class="btn btn-outline-primary"
                    >
                        Manage Properties
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


    <!-- Account -->

    <div class="card shadow-sm border-0">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-3">
                Landlord Account
            </h4>

            <p class="mb-1">

                <strong>Name:</strong>

                <?= htmlspecialchars($full_name) ?>

            </p>

            <p class="text-muted mb-0">
                Use the dashboard to manage your properties,
                viewing requests and notifications.
            </p>

        </div>

    </div>

</div>


</body>

</html>