<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

$admin_id = currentUserId();

/*
|--------------------------------------------------------------------------
| Handle status changes
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $request_id = isset($_POST["request_id"])
        ? (int) $_POST["request_id"]
        : 0;

    $new_status = $_POST["status"] ?? "";

    $allowedStatuses = [
        "pending",
        "approved",
        "rejected",
        "completed",
        "cancelled"
    ];

    if (
        $request_id > 0
        && in_array($new_status, $allowedStatuses, true)
    ) {

        /*
        |--------------------------------------------------------------------------
        | Get request
        |--------------------------------------------------------------------------
        */

        $requestStmt = $pdo->prepare("
            SELECT
                id,
                tenant_id,
                property_id,
                landlord_id,
                status
            FROM viewing_requests
            WHERE id = ?
            LIMIT 1
        ");

        $requestStmt->execute([$request_id]);

        $request = $requestStmt->fetch();

        if ($request) {

            /*
            |--------------------------------------------------------------------------
            | Update request
            |--------------------------------------------------------------------------
            */

            $updateStmt = $pdo->prepare("
                UPDATE viewing_requests
                SET status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $updateStmt->execute([
                $new_status,
                $request_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Notify tenant
            |--------------------------------------------------------------------------
            */

            $title = "Viewing Request Updated";

            $message =
                "Your property viewing request status has been changed to "
                . ucfirst($new_status)
                . ".";

            $notificationStmt = $pdo->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    title,
                    message
                )
                VALUES (?, ?, ?)
            ");

            $notificationStmt->execute([
                $request["tenant_id"],
                $title,
                $message
            ]);
        }
    }

    header("Location: viewing-requests.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "";

$where = [];
$params = [];


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $where[] = "
        (
            p.title LIKE ?
            OR t.full_name LIKE ?
            OR t.email LIKE ?
            OR l.full_name LIKE ?
            OR l.email LIKE ?
            OR p.town LIKE ?
            OR p.area LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Status filter
|--------------------------------------------------------------------------
*/

if (
    $status !== ""
    && in_array(
        $status,
        [
            "pending",
            "approved",
            "rejected",
            "completed",
            "cancelled"
        ],
        true
    )
) {

    $where[] = "vr.status = ?";

    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| WHERE
|--------------------------------------------------------------------------
*/

$whereSql = "";

if (!empty($where)) {

    $whereSql = "
        WHERE " . implode(" AND ", $where);
}


/*
|--------------------------------------------------------------------------
| Fetch requests
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        vr.id,
        vr.requested_date,
        vr.requested_time,
        vr.message,
        vr.status,
        vr.landlord_response,
        vr.created_at,

        p.id AS property_id,
        p.title AS property_title,
        p.town,
        p.area,

        t.full_name AS tenant_name,
        t.email AS tenant_email,
        t.phone AS tenant_phone,

        l.full_name AS landlord_name,
        l.email AS landlord_email,
        l.phone AS landlord_phone

    FROM viewing_requests vr

    INNER JOIN properties p
        ON vr.property_id = p.id

    INNER JOIN users t
        ON vr.tenant_id = t.id

    INNER JOIN users l
        ON vr.landlord_id = l.id

    $whereSql

    ORDER BY vr.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$requests = $stmt->fetchAll();

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
        Viewing Requests - Admin
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .admin-navbar {
            background: #09234d;
        }

        .request-card {
            border: none;
            border-radius: 14px;
        }

        .status-badge {
            text-transform: capitalize;
        }

    </style>

</head>

<body>


<nav class="navbar navbar-dark admin-navbar">

    <div class="container">

        <a
            href="dashboard.php"
            class="navbar-brand fw-bold"
        >
            Verified House Kenya | Admin
        </a>

        <div>

            <a
                href="dashboard.php"
                class="btn btn-outline-light btn-sm me-2"
            >
                Dashboard
            </a>

            <a
                href="../notifications.php"
                class="btn btn-light btn-sm me-2"
            >
                🔔 Notifications
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

        <h2 class="fw-bold mb-1">
            Viewing Requests
        </h2>

        <p class="text-muted mb-0">
            Monitor property viewing requests across the platform.
        </p>

    </div>


    <!-- Filters -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3"
            >

                <div class="col-md-6">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Property, tenant, landlord, town or area"
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="pending"
                            <?= $status === "pending" ? "selected" : "" ?>
                        >
                            Pending
                        </option>

                        <option
                            value="approved"
                            <?= $status === "approved" ? "selected" : "" ?>
                        >
                            Approved
                        </option>

                        <option
                            value="rejected"
                            <?= $status === "rejected" ? "selected" : "" ?>
                        >
                            Rejected
                        </option>

                        <option
                            value="completed"
                            <?= $status === "completed" ? "selected" : "" ?>
                        >
                            Completed
                        </option>

                        <option
                            value="cancelled"
                            <?= $status === "cancelled" ? "selected" : "" ?>
                        >
                            Cancelled
                        </option>

                    </select>

                </div>


                <div class="col-md-2 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Search
                    </button>

                </div>

            </form>

        </div>

    </div>


    <!-- Requests -->

    <div class="card request-card shadow-sm border-0">

        <div class="card-body p-0">

            <?php if (empty($requests)): ?>

                <div class="text-center py-5">

                    <h5>
                        No viewing requests found
                    </h5>

                    <p class="text-muted mb-0">
                        There are currently no requests matching your filters.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>
                                    Property
                                </th>

                                <th>
                                    Tenant
                                </th>

                                <th>
                                    Landlord
                                </th>

                                <th>
                                    Requested
                                </th>

                                <th>
                                    Status
                                </th>

                                <th class="text-end">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($requests as $request): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $request["property_title"]
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $request["town"]
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $request["area"]
                                        ) ?>

                                    </small>

                                    <br>

                                    <a
                                        href="../property.php?id=<?= (int) $request["property_id"] ?>"
                                        target="_blank"
                                        class="small"
                                    >
                                        View Property
                                    </a>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $request["tenant_name"]
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            $request["tenant_email"]
                                        ) ?>
                                    </small>

                                    <br>

                                    <small>
                                        <?= htmlspecialchars(
                                            $request["tenant_phone"] ?? "-"
                                        ) ?>
                                    </small>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $request["landlord_name"]
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            $request["landlord_email"]
                                        ) ?>
                                    </small>

                                </td>


                                <td>

                                    <strong>
                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $request["requested_date"]
                                            )
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">

                                        <?= date(
                                            "h:i A",
                                            strtotime(
                                                $request["requested_time"]
                                            )
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <?php if (
                                        $request["status"] === "pending"
                                    ): ?>

                                        <span class="badge bg-warning text-dark status-badge">
                                            Pending
                                        </span>

                                    <?php elseif (
                                        $request["status"] === "approved"
                                    ): ?>

                                        <span class="badge bg-success status-badge">
                                            Approved
                                        </span>

                                    <?php elseif (
                                        $request["status"] === "rejected"
                                    ): ?>

                                        <span class="badge bg-danger status-badge">
                                            Rejected
                                        </span>

                                    <?php elseif (
                                        $request["status"] === "completed"
                                    ): ?>

                                        <span class="badge bg-primary status-badge">
                                            Completed
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary status-badge">
                                            Cancelled
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td class="text-end">

                                    <div class="dropdown">

                                        <button
                                            class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                        >
                                            Change
                                        </button>


                                        <ul class="dropdown-menu dropdown-menu-end">


                                            <?php if (
                                                $request["status"]
                                                !== "pending"
                                            ): ?>

                                                <li>

                                                    <form method="POST">

                                                        <input
                                                            type="hidden"
                                                            name="request_id"
                                                            value="<?= (int) $request["id"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="pending"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item"
                                                        >
                                                            Pending
                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>


                                            <?php if (
                                                $request["status"]
                                                !== "approved"
                                            ): ?>

                                                <li>

                                                    <form method="POST">

                                                        <input
                                                            type="hidden"
                                                            name="request_id"
                                                            value="<?= (int) $request["id"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="approved"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-success"
                                                        >
                                                            Approve
                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>


                                            <?php if (
                                                $request["status"]
                                                !== "rejected"
                                            ): ?>

                                                <li>

                                                    <form
                                                        method="POST"
                                                        onsubmit="
                                                            return confirm(
                                                                'Are you sure you want to reject this viewing request?'
                                                            );
                                                        "
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="request_id"
                                                            value="<?= (int) $request["id"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="rejected"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item text-danger"
                                                        >
                                                            Reject
                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>


                                            <?php if (
                                                $request["status"]
                                                !== "completed"
                                            ): ?>

                                                <li>

                                                    <form method="POST">

                                                        <input
                                                            type="hidden"
                                                            name="request_id"
                                                            value="<?= (int) $request["id"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="completed"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item"
                                                        >
                                                            Mark Completed
                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>


                                            <?php if (
                                                $request["status"]
                                                !== "cancelled"
                                            ): ?>

                                                <li>

                                                    <form method="POST">

                                                        <input
                                                            type="hidden"
                                                            name="request_id"
                                                            value="<?= (int) $request["id"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="status"
                                                            value="cancelled"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="dropdown-item"
                                                        >
                                                            Cancel
                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>


                                        </ul>

                                    </div>

                                </td>

                            </tr>


                            <?php if (
                                !empty($request["message"])
                                || !empty($request["landlord_response"])
                            ): ?>

                                <tr class="table-light">

                                    <td colspan="6">

                                        <?php if (
                                            !empty($request["message"])
                                        ): ?>

                                            <small>

                                                <strong>
                                                    Tenant message:
                                                </strong>

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $request["message"]
                                                    )
                                                ) ?>

                                            </small>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty(
                                                $request["landlord_response"]
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <strong>
                                                    Landlord response:
                                                </strong>

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $request[
                                                            "landlord_response"
                                                        ]
                                                    )
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endif; ?>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>