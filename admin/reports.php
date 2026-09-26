<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

/*
|--------------------------------------------------------------------------
| USER STATISTICS
|--------------------------------------------------------------------------
*/

$totalUsers = $pdo->query("
    SELECT COUNT(*) 
    FROM users
")->fetchColumn();

$totalTenants = $pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE role = 'tenant'
")->fetchColumn();

$totalLandlords = $pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE role = 'landlord'
")->fetchColumn();

$totalAdmins = $pdo->query("
    SELECT COUNT(*) 
    FROM users
    WHERE role = 'admin'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| PROPERTY STATISTICS
|--------------------------------------------------------------------------
*/

$totalProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
")->fetchColumn();

$verifiedProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
    WHERE verification_status = 'verified'
")->fetchColumn();

$pendingProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
    WHERE verification_status = 'pending'
")->fetchColumn();

$rejectedProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
    WHERE verification_status = 'rejected'
")->fetchColumn();

$availableProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
    WHERE status = 'available'
")->fetchColumn();

$occupiedProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
    WHERE status = 'occupied'
")->fetchColumn();

$inactiveProperties = $pdo->query("
    SELECT COUNT(*)
    FROM properties
    WHERE status = 'inactive'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| VIEWING REQUEST STATISTICS
|--------------------------------------------------------------------------
*/

$totalViewings = $pdo->query("
    SELECT COUNT(*)
    FROM viewing_requests
")->fetchColumn();

$pendingViewings = $pdo->query("
    SELECT COUNT(*)
    FROM viewing_requests
    WHERE status = 'pending'
")->fetchColumn();

$approvedViewings = $pdo->query("
    SELECT COUNT(*)
    FROM viewing_requests
    WHERE status = 'approved'
")->fetchColumn();

$rejectedViewings = $pdo->query("
    SELECT COUNT(*)
    FROM viewing_requests
    WHERE status = 'rejected'
")->fetchColumn();

$completedViewings = $pdo->query("
    SELECT COUNT(*)
    FROM viewing_requests
    WHERE status = 'completed'
")->fetchColumn();

$cancelledViewings = $pdo->query("
    SELECT COUNT(*)
    FROM viewing_requests
    WHERE status = 'cancelled'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| RECENT PROPERTIES
|--------------------------------------------------------------------------
*/

$recentPropertiesStmt = $pdo->query("
    SELECT
        p.id,
        p.title,
        p.property_type,
        p.town,
        p.area,
        p.rent,
        p.verification_status,
        p.status,
        u.full_name AS landlord_name
    FROM properties p
    INNER JOIN users u ON u.id = p.landlord_id
    ORDER BY p.created_at DESC
    LIMIT 10
");

$recentProperties = $recentPropertiesStmt->fetchAll();


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

$notificationStmt->execute([currentUserId()]);

$unreadNotifications = $notificationStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports - Verified House Kenya</title>

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

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            height: 100%;
        }

        .stat-title {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
            color: #09234d;
        }

        .section-card {
            background: white;
            border-radius: 10px;
            padding: 22px;
            margin-top: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-card h5 {
            color: #09234d;
            margin-bottom: 20px;
        }

        .badge-status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .notification-badge {
            background: #dc3545;
            color: white;
            font-size: 11px;
            padding: 3px 7px;
            border-radius: 10px;
            margin-left: 5px;
        }

        table {
            vertical-align: middle;
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

    <a href="reports.php" class="active">
        Reports
    </a>

    <a href="viewing-requests.php">
        Viewing Requests
    </a>

    <a href="../logout.php" class="logout-link">
        Logout
    </a>

</div>


<!-- MAIN CONTENT -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h3>
            Reports
        </h3>

        <div>

            <strong>
                <?= htmlspecialchars(currentUserName()) ?>
            </strong>

        </div>

    </div>


    <!-- USER REPORTS -->

    <div class="section-card">

        <h5>
            User Statistics
        </h5>

        <div class="row g-3">

            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Total Users
                    </div>

                    <div class="stat-number">
                        <?= $totalUsers ?>
                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Tenants
                    </div>

                    <div class="stat-number">
                        <?= $totalTenants ?>
                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Landlords
                    </div>

                    <div class="stat-number">
                        <?= $totalLandlords ?>
                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Administrators
                    </div>

                    <div class="stat-number">
                        <?= $totalAdmins ?>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- PROPERTY REPORTS -->

    <div class="section-card">

        <h5>
            Property Statistics
        </h5>

        <div class="row g-3">

            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Total Properties
                    </div>

                    <div class="stat-number">
                        <?= $totalProperties ?>
                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Verified
                    </div>

                    <div class="stat-number">
                        <?= $verifiedProperties ?>
                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Pending Verification
                    </div>

                    <div class="stat-number">
                        <?= $pendingProperties ?>
                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="stat-card">

                    <div class="stat-title">
                        Rejected
                    </div>

                    <div class="stat-number">
                        <?= $rejectedProperties ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Available
                    </div>

                    <div class="stat-number">
                        <?= $availableProperties ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Occupied
                    </div>

                    <div class="stat-number">
                        <?= $occupiedProperties ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Inactive
                    </div>

                    <div class="stat-number">
                        <?= $inactiveProperties ?>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- VIEWING REPORTS -->

    <div class="section-card">

        <h5>
            Viewing Request Statistics
        </h5>

        <div class="row g-3">

            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Total Viewing Requests
                    </div>

                    <div class="stat-number">
                        <?= $totalViewings ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Pending
                    </div>

                    <div class="stat-number">
                        <?= $pendingViewings ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Approved
                    </div>

                    <div class="stat-number">
                        <?= $approvedViewings ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Rejected
                    </div>

                    <div class="stat-number">
                        <?= $rejectedViewings ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Completed
                    </div>

                    <div class="stat-number">
                        <?= $completedViewings ?>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="stat-card">

                    <div class="stat-title">
                        Cancelled
                    </div>

                    <div class="stat-number">
                        <?= $cancelledViewings ?>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- RECENT PROPERTIES -->

    <div class="section-card">

        <h5>
            Recent Properties
        </h5>

        <?php if (count($recentProperties) > 0): ?>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>

                        <tr>

                            <th>Property</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Rent</th>
                            <th>Landlord</th>
                            <th>Verification</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($recentProperties as $property): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($property['title']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($property['property_type']) ?>
                                </td>

                                <td>

                                    <?= htmlspecialchars($property['town']) ?>

                                    <?php if (!empty($property['area'])): ?>

                                        ,
                                        <?= htmlspecialchars($property['area']) ?>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    KSh <?= number_format($property['rent'], 2) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($property['landlord_name']) ?>
                                </td>

                                <td>

                                    <?php if ($property['verification_status'] === 'verified'): ?>

                                        <span class="badge bg-success badge-status">
                                            Verified
                                        </span>

                                    <?php elseif ($property['verification_status'] === 'pending'): ?>

                                        <span class="badge bg-warning text-dark badge-status">
                                            Pending
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger badge-status">
                                            Rejected
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($property['status'] === 'available'): ?>

                                        <span class="badge bg-success badge-status">
                                            Available
                                        </span>

                                    <?php elseif ($property['status'] === 'occupied'): ?>

                                        <span class="badge bg-secondary badge-status">
                                            Occupied
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-dark badge-status">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-info">
                No properties have been added yet.
            </div>

        <?php endif; ?>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>