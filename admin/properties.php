<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$verification = $_GET["verification"] ?? "";
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
            OR p.town LIKE ?
            OR p.area LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| Verification filter
|--------------------------------------------------------------------------
*/

if (
    $verification !== ""
    && in_array(
        $verification,
        ["pending", "verified", "rejected"],
        true
    )
) {

    $where[] = "
        p.verification_status = ?
    ";

    $params[] = $verification;
}


/*
|--------------------------------------------------------------------------
| Availability filter
|--------------------------------------------------------------------------
*/

if (
    $status !== ""
    && in_array(
        $status,
        ["available", "occupied", "inactive"],
        true
    )
) {

    $where[] = "
        p.status = ?
    ";

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
        WHERE
        " . implode(" AND ", $where);
}


/*
|--------------------------------------------------------------------------
| Fetch properties
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.title,
        p.property_type,
        p.county,
        p.town,
        p.area,
        p.rent,
        p.bedrooms,
        p.bathrooms,
        p.status,
        p.verification_status,
        p.created_at,

        u.full_name AS landlord_name,
        u.email AS landlord_email,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS image_path

    FROM properties p

    INNER JOIN users u
        ON p.landlord_id = u.id

    $whereSql

    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$properties = $stmt->fetchAll();

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
        Manage Properties - Admin
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

        .property-card {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            transition: 0.2s;
        }

        .property-card:hover {
            transform: translateY(-3px);
        }

        .property-image {
            height: 210px;
            width: 100%;
            object-fit: cover;
        }

        .no-image {
            height: 210px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        .badge-pending {
            background: #ffc107;
            color: #000;
        }

        .badge-verified {
            background: #198754;
        }

        .badge-rejected {
            background: #dc3545;
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


    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Property Management
            </h2>

            <p class="text-muted mb-0">
                View and manage all properties on the platform.
            </p>

        </div>

    </div>


    <!-- Filters -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3"
            >

                <div class="col-md-4">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Title, town, area or landlord"
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Verification
                    </label>

                    <select
                        name="verification"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="pending"
                            <?= $verification === "pending" ? "selected" : "" ?>
                        >
                            Pending
                        </option>

                        <option
                            value="verified"
                            <?= $verification === "verified" ? "selected" : "" ?>
                        >
                            Verified
                        </option>

                        <option
                            value="rejected"
                            <?= $verification === "rejected" ? "selected" : "" ?>
                        >
                            Rejected
                        </option>

                    </select>

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Availability
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="available"
                            <?= $status === "available" ? "selected" : "" ?>
                        >
                            Available
                        </option>

                        <option
                            value="occupied"
                            <?= $status === "occupied" ? "selected" : "" ?>
                            >
                            Occupied
                        </option>

                        <option
                            value="inactive"
                            <?= $status === "inactive" ? "selected" : "" ?>
                        >
                            Inactive
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


    <!-- Results -->

    <?php if (empty($properties)): ?>

        <div class="card shadow-sm border-0">

            <div class="card-body text-center py-5">

                <h5>
                    No properties found
                </h5>

                <p class="text-muted mb-0">
                    Try changing your search or filters.
                </p>

            </div>

        </div>

    <?php else: ?>


        <div class="row g-4">

            <?php foreach ($properties as $property): ?>


                <div class="col-md-6 col-lg-4">

                    <div class="card property-card shadow-sm h-100">


                        <?php if (!empty($property["image_path"])): ?>

                            <img
                                src="../<?= htmlspecialchars($property["image_path"]) ?>"
                                class="property-image"
                                alt="Property"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No Image Available
                            </div>

                        <?php endif; ?>


                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-start mb-2">

                                <h5 class="fw-bold mb-0">

                                    <?= htmlspecialchars(
                                        $property["title"]
                                    ) ?>

                                </h5>


                                <?php if (
                                    $property["verification_status"]
                                    === "verified"
                                ): ?>

                                    <span class="badge badge-verified">
                                        Verified
                                    </span>

                                <?php elseif (
                                    $property["verification_status"]
                                    === "rejected"
                                ): ?>

                                    <span class="badge badge-rejected">
                                        Rejected
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-pending">
                                        Pending
                                    </span>

                                <?php endif; ?>

                            </div>


                            <p class="text-muted mb-2">

                                <?= htmlspecialchars(
                                    $property["property_type"]
                                ) ?>

                                ·

                                <?= htmlspecialchars(
                                    $property["town"]
                                ) ?>

                                ·

                                <?= htmlspecialchars(
                                    $property["area"]
                                ) ?>

                            </p>


                            <h5 class="text-primary fw-bold">

                                KSh
                                <?= number_format(
                                    $property["rent"],
                                    0
                                ) ?>

                                <small class="text-muted">
                                    / month
                                </small>

                            </h5>


                            <div class="small text-muted mb-3">

                                <?= (int) $property["bedrooms"] ?>
                                Bedroom(s)

                                ·

                                <?= (int) $property["bathrooms"] ?>
                                Bathroom(s)

                            </div>


                            <hr>


                            <p class="small mb-1">

                                <strong>
                                    Landlord:
                                </strong>

                                <?= htmlspecialchars(
                                    $property["landlord_name"]
                                ) ?>

                            </p>


                            <p class="small text-muted">

                                <?= htmlspecialchars(
                                    $property["landlord_email"]
                                ) ?>

                            </p>


                            <div class="mb-3">

                                <?php if (
                                    $property["status"]
                                    === "available"
                                ): ?>

                                    <span class="badge bg-success">
                                        Available
                                    </span>

                                <?php elseif (
                                    $property["status"]
                                    === "occupied"
                                ): ?>

                                    <span class="badge bg-secondary">
                                        Occupied
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-dark">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="d-flex gap-2">

                                <a
                                    href="../property.php?id=<?= (int) $property["id"] ?>"
                                    class="btn btn-outline-primary btn-sm"
                                    target="_blank"
                                >
                                    View
                                </a>


                                <?php if (
                                    $property["verification_status"]
                                    === "pending"
                                ): ?>

                                    <a
                                        href="verification.php"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Verify
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


            <?php endforeach; ?>

        </div>


    <?php endif; ?>

</div>


</body>

</html>