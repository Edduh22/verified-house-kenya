<?php

require_once "includes/db.php";
require_once "includes/auth.php";

requireLogin();

$userId = currentUserId();

$message = "";


/*
|--------------------------------------------------------------------------
| MARK SINGLE NOTIFICATION AS READ
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_read"])) {

    $notificationId = (int)($_POST["notification_id"] ?? 0);

    if ($notificationId > 0) {

        $stmt = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
            AND user_id = ?
        ");

        $stmt->execute([
            $notificationId,
            $userId
        ]);

        $message = "Notification marked as read.";
    }
}


/*
|--------------------------------------------------------------------------
| MARK ALL AS READ
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_all_read"])) {

    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
        AND is_read = 0
    ");

    $stmt->execute([$userId]);

    $message = "All notifications marked as read.";
}


/*
|--------------------------------------------------------------------------
| GET NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);

$notifications = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| COUNT UNREAD
|--------------------------------------------------------------------------
*/

$unreadStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
    AND is_read = 0
");

$unreadStmt->execute([$userId]);

$unreadCount = $unreadStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| DASHBOARD LINK BASED ON ROLE
|--------------------------------------------------------------------------
*/

$dashboardLink = "index.php";

if (currentUserRole() === "tenant") {

    $dashboardLink = "tenant/dashboard.php";

} elseif (currentUserRole() === "landlord") {

    $dashboardLink = "landlord/dashboard.php";

} elseif (currentUserRole() === "admin") {

    $dashboardLink = "admin/dashboard.php";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifications - Verified House Kenya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
        }

        .navbar-custom {
            background: #09234d;
        }

        .navbar-brand,
        .navbar-custom .nav-link {
            color: white;
        }

        .navbar-custom .nav-link:hover {
            color: #dce6f7;
        }

        .page-container {
            max-width: 950px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .page-header h2 {
            color: #09234d;
            margin-bottom: 5px;
        }

        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 5px solid transparent;
        }

        .notification-card.unread {
            border-left-color: #1769d1;
            background: #f8fbff;
        }

        .notification-title {
            color: #09234d;
            font-weight: bold;
            font-size: 17px;
        }

        .notification-message {
            color: #555;
            margin-top: 8px;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .notification-date {
            color: #888;
            font-size: 13px;
        }

        .new-badge {
            background: #1769d1;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 8px;
        }

        .empty-box {
            background: white;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .empty-box h4 {
            color: #09234d;
        }

        .notification-actions {
            margin-top: 12px;
        }

        .unread-count {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 13px;
        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-custom">

    <div class="container">

        <a
            class="navbar-brand fw-bold"
            href="<?= htmlspecialchars($dashboardLink) ?>"
        >
            Verified House Kenya
        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= htmlspecialchars($dashboardLink) ?>"
                    >
                        Dashboard
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="notifications.php"
                    >
                        Notifications

                        <?php if ($unreadCount > 0): ?>

                            <span class="unread-count">
                                <?= $unreadCount ?>
                            </span>

                        <?php endif; ?>

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="logout.php"
                    >
                        Logout
                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- PAGE -->

<div class="page-container">


    <div class="page-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h2>
                    Notifications
                </h2>

                <p class="text-muted mb-0">

                    Stay updated about your Verified House Kenya account.

                </p>

            </div>


            <?php if ($unreadCount > 0): ?>

                <form method="POST">

                    <button
                        type="submit"
                        name="mark_all_read"
                        class="btn btn-outline-primary"
                    >
                        Mark All as Read
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- NOTIFICATIONS -->

    <?php if (count($notifications) > 0): ?>


        <?php foreach ($notifications as $notification): ?>

            <div
                class="notification-card
                <?= $notification["is_read"] == 0 ? "unread" : "" ?>"
            >

                <div class="d-flex justify-content-between align-items-start gap-3">

                    <div>

                        <div class="notification-title">

                            <?= htmlspecialchars($notification["title"]) ?>

                            <?php if ($notification["is_read"] == 0): ?>

                                <span class="new-badge">
                                    NEW
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="notification-message">

                            <?= nl2br(
                                htmlspecialchars($notification["message"])
                            ) ?>

                        </div>


                        <div class="notification-date">

                            <?= htmlspecialchars(
                                $notification["created_at"]
                            ) ?>

                        </div>

                    </div>


                    <?php if ($notification["is_read"] == 0): ?>

                        <div class="notification-actions">

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int)$notification["id"] ?>"
                                >

                                <button
                                    type="submit"
                                    name="mark_read"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Mark as Read
                                </button>

                            </form>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>


    <?php else: ?>


        <div class="empty-box">

            <h4>
                No Notifications
            </h4>

            <p class="text-muted mb-0">

                You don't have any notifications at the moment.

            </p>

        </div>


    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>