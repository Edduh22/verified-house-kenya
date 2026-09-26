<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

$admin_id = currentUserId();

/*
|--------------------------------------------------------------------------
| Handle account status changes
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = isset($_POST["user_id"])
        ? (int) $_POST["user_id"]
        : 0;

    $new_status = $_POST["status"] ?? "";

    $allowedStatuses = [
        "active",
        "inactive",
        "suspended"
    ];

    if (
        $user_id > 0
        && in_array($new_status, $allowedStatuses, true)
        && $user_id !== (int) $admin_id
    ) {

        $updateStmt = $pdo->prepare("
            UPDATE users
            SET status = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $new_status,
            $user_id
        ]);

        /*
        |--------------------------------------------------------------------------
        | Notification to affected user
        |--------------------------------------------------------------------------
        */

        $message = "Your Verified House Kenya account status has been changed to "
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
            $user_id,
            "Account Status Updated",
            $message
        ]);
    }

    header("Location: users.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$role = $_GET["role"] ?? "";
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
            full_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Role filter
|--------------------------------------------------------------------------
*/

if (
    $role !== ""
    && in_array(
        $role,
        ["tenant", "landlord", "admin"],
        true
    )
) {

    $where[] = "role = ?";

    $params[] = $role;
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
        ["active", "inactive", "suspended"],
        true
    )
) {

    $where[] = "status = ?";

    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| Build WHERE
|--------------------------------------------------------------------------
*/

$whereSql = "";

if (!empty($where)) {

    $whereSql = "
        WHERE " . implode(" AND ", $where);
}


/*
|--------------------------------------------------------------------------
| Fetch users
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        full_name,
        email,
        phone,
        role,
        status,
        created_at
    FROM users
    $whereSql
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$users = $stmt->fetchAll();

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
        User Management - Admin
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

        .user-card {
            border: none;
            border-radius: 14px;
        }

        .role-badge {
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
            User Management
        </h2>

        <p class="text-muted mb-0">
            Manage tenant, landlord and administrator accounts.
        </p>

    </div>


    <!-- Filters -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3"
            >

                <div class="col-md-5">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Name, email or phone"
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Role
                    </label>

                    <select
                        name="role"
                        class="form-select"
                    >

                        <option value="">
                            All Roles
                        </option>

                        <option
                            value="tenant"
                            <?= $role === "tenant" ? "selected" : "" ?>
                        >
                            Tenant
                        </option>

                        <option
                            value="landlord"
                            <?= $role === "landlord" ? "selected" : "" ?>
                        >
                            Landlord
                        </option>

                        <option
                            value="admin"
                            <?= $role === "admin" ? "selected" : "" ?>
                        >
                            Admin
                        </option>

                    </select>

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="active"
                            <?= $status === "active" ? "selected" : "" ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $status === "inactive" ? "selected" : "" ?>
                        >
                            Inactive
                        </option>

                        <option
                            value="suspended"
                            <?= $status === "suspended" ? "selected" : "" ?>
                        >
                            Suspended
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


    <!-- Users -->

    <div class="card user-card shadow-sm border-0">

        <div class="card-body p-0">

            <?php if (empty($users)): ?>

                <div class="text-center py-5">

                    <h5>
                        No users found
                    </h5>

                    <p class="text-muted mb-0">
                        Try changing your search or filters.
                    </p>

                </div>

            <?php else: ?>


                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th>
                                    User
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Registered
                                </th>

                                <th class="text-end">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $user["full_name"]
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            $user["email"]
                                        ) ?>
                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user["phone"] ?? "-"
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        $user["role"] === "tenant"
                                    ): ?>

                                        <span class="badge bg-primary role-badge">
                                            Tenant
                                        </span>

                                    <?php elseif (
                                        $user["role"] === "landlord"
                                    ): ?>

                                        <span class="badge bg-success role-badge">
                                            Landlord
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-dark role-badge">
                                            Admin
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        $user["status"] === "active"
                                    ): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php elseif (
                                        $user["status"] === "inactive"
                                    ): ?>

                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            Suspended
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $user["created_at"]
                                        )
                                    ) ?>

                                </td>


                                <td class="text-end">

                                    <?php if (
                                        (int) $user["id"]
                                        === (int) $admin_id
                                    ): ?>

                                        <span class="text-muted small">
                                            Current Account
                                        </span>

                                    <?php else: ?>

                                        <div class="dropdown">

                                            <button
                                                class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                            >
                                                Change Status
                                            </button>


                                            <ul class="dropdown-menu dropdown-menu-end">

                                                <?php if (
                                                    $user["status"]
                                                    !== "active"
                                                ): ?>

                                                    <li>

                                                        <form
                                                            method="POST"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= (int) $user["id"] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="active"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="dropdown-item"
                                                            >
                                                                Activate
                                                            </button>

                                                        </form>

                                                    </li>

                                                <?php endif; ?>


                                                <?php if (
                                                    $user["status"]
                                                    !== "inactive"
                                                ): ?>

                                                    <li>

                                                        <form
                                                            method="POST"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= (int) $user["id"] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="inactive"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="dropdown-item"
                                                            >
                                                                Deactivate
                                                            </button>

                                                        </form>

                                                    </li>

                                                <?php endif; ?>


                                                <?php if (
                                                    $user["status"]
                                                    !== "suspended"
                                                ): ?>

                                                    <li>

                                                        <form
                                                            method="POST"
                                                            onsubmit="
                                                                return confirm(
                                                                    'Are you sure you want to suspend this account?'
                                                                );
                                                            "
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value="<?= (int) $user["id"] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="suspended"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="dropdown-item text-danger"
                                                            >
                                                                Suspend
                                                            </button>

                                                        </form>

                                                    </li>

                                                <?php endif; ?>

                                            </ul>

                                        </div>

                                    <?php endif; ?>

                                </td>

                            </tr>

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