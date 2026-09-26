<?php

require_once "includes/db.php";
require_once "includes/auth.php";


/*
|--------------------------------------------------------------------------
| Get Property ID
|--------------------------------------------------------------------------
*/

$property_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($property_id <= 0) {
    die("Invalid property.");
}


/*
|--------------------------------------------------------------------------
| Login / Role Information
|--------------------------------------------------------------------------
*/

$isLoggedIn = isLoggedIn();

$currentRole = currentUserRole();

$currentUserId = currentUserId();


/*
|--------------------------------------------------------------------------
| Get Property
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.*,
        u.full_name AS landlord_name,
        u.phone AS landlord_phone,
        u.email AS landlord_email

    FROM properties p

    INNER JOIN users u
        ON p.landlord_id = u.id

    WHERE p.id = ?

    LIMIT 1
");

$stmt->execute([$property_id]);

$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}


/*
|--------------------------------------------------------------------------
| Get Property Images
|--------------------------------------------------------------------------
*/

$imageStmt = $pdo->prepare("
    SELECT
        id,
        image_path,
        is_primary

    FROM property_images

    WHERE property_id = ?

    ORDER BY is_primary DESC, id ASC
");

$imageStmt->execute([$property_id]);

$images = $imageStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Check Favourite Status
|--------------------------------------------------------------------------
*/

$isFavourite = false;

if ($isLoggedIn && $currentRole === "tenant") {

    $favouriteStmt = $pdo->prepare("
        SELECT id
        FROM favourites
        WHERE tenant_id = ?
        AND property_id = ?
        LIMIT 1
    ");

    $favouriteStmt->execute([
        $currentUserId,
        $property_id
    ]);

    if ($favouriteStmt->fetch()) {
        $isFavourite = true;
    }
}


/*
|--------------------------------------------------------------------------
| Verification Badge
|--------------------------------------------------------------------------
*/

$verification = $property["verification_status"];

if ($verification === "verified") {

    $verificationClass = "bg-success";
    $verificationText = "Verified Property";

} elseif ($verification === "rejected") {

    $verificationClass = "bg-danger";
    $verificationText = "Verification Rejected";

} else {

    $verificationClass = "bg-warning text-dark";
    $verificationText = "Pending Verification";
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

    <title>
        <?= htmlspecialchars($property["title"]) ?>
        - Verified House Kenya
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .main-image {
            width: 100%;
            height: 450px;
            object-fit: cover;
            border-radius: 10px;
        }

        .property-price {
            font-size: 28px;
            font-weight: bold;
        }

        .favourite-button {
            font-size: 17px;
        }

    </style>

</head>


<body class="bg-light">


<!-- =========================================================
     Navigation
========================================================= -->

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a
            href="index.php"
            class="navbar-brand"
        >
            Verified House Kenya
        </a>


        <div>

            <a
                href="search.php"
                class="btn btn-light btn-sm me-2"
            >
                Search Houses
            </a>


            <?php if ($isLoggedIn): ?>

                <?php if ($currentRole === "tenant"): ?>

                    <a
                        href="tenant/dashboard.php"
                        class="btn btn-outline-light btn-sm"
                    >
                        Dashboard
                    </a>

                <?php elseif ($currentRole === "landlord"): ?>

                    <a
                        href="landlord/dashboard.php"
                        class="btn btn-outline-light btn-sm"
                    >
                        Dashboard
                    </a>

                <?php elseif ($currentRole === "admin"): ?>

                    <a
                        href="admin/dashboard.php"
                        class="btn btn-outline-light btn-sm"
                    >
                        Dashboard
                    </a>

                <?php endif; ?>

            <?php else: ?>

                <a
                    href="login.php"
                    class="btn btn-outline-light btn-sm"
                >
                    Login
                </a>

            <?php endif; ?>

        </div>

    </div>

</nav>


<!-- =========================================================
     Main Container
========================================================= -->

<div class="container py-5">


    <!-- =====================================================
         Property Title
    ====================================================== -->

    <div class="mb-4">

        <span class="badge <?= $verificationClass ?>">

            <?= $verificationText ?>

        </span>


        <h1 class="mt-2">

            <?= htmlspecialchars($property["title"]) ?>

        </h1>


        <p class="text-muted">

            <?= htmlspecialchars($property["area"] ?? "") ?>

            <?php if (!empty($property["town"])): ?>

                ,
                <?= htmlspecialchars($property["town"]) ?>

            <?php endif; ?>


            <?php if (!empty($property["county"])): ?>

                ,
                <?= htmlspecialchars($property["county"]) ?>

            <?php endif; ?>

        </p>

    </div>


    <div class="row g-4">


        <!-- =================================================
             Images
        ================================================== -->

        <div class="col-lg-7">

            <?php if (!empty($images)): ?>

                <div
                    id="propertyCarousel"
                    class="carousel slide"
                    data-bs-ride="carousel"
                >

                    <div class="carousel-inner">


                        <?php foreach ($images as $index => $image): ?>

                            <div
                                class="carousel-item <?= $index === 0 ? "active" : "" ?>"
                            >

                                <img
                                    src="<?= htmlspecialchars($image["image_path"]) ?>"
                                    class="main-image"
                                    alt="Property image"
                                >

                            </div>

                        <?php endforeach; ?>


                    </div>


                    <?php if (count($images) > 1): ?>

                        <button
                            class="carousel-control-prev"
                            type="button"
                            data-bs-target="#propertyCarousel"
                            data-bs-slide="prev"
                        >

                            <span class="carousel-control-prev-icon"></span>

                        </button>


                        <button
                            class="carousel-control-next"
                            type="button"
                            data-bs-target="#propertyCarousel"
                            data-bs-slide="next"
                        >

                            <span class="carousel-control-next-icon"></span>

                        </button>

                    <?php endif; ?>

                </div>


            <?php else: ?>

                <div
                    class="main-image bg-secondary d-flex align-items-center justify-content-center text-white"
                >

                    No property image available

                </div>

            <?php endif; ?>

        </div>


        <!-- =================================================
             Property Summary
        ================================================== -->

        <div class="col-lg-5">


            <div class="card shadow-sm">

                <div class="card-body">


                    <!-- Price -->

                    <div class="property-price text-primary">

                        KES <?= number_format($property["rent"]) ?>

                        <small class="text-muted fs-6">
                            / month
                        </small>

                    </div>


                    <hr>


                    <!-- Property Details -->

                    <div class="row text-center">


                        <div class="col-4">

                            <strong>
                                <?= (int) $property["bedrooms"] ?>
                            </strong>

                            <br>

                            <small class="text-muted">
                                Bedrooms
                            </small>

                        </div>


                        <div class="col-4">

                            <strong>
                                <?= (int) $property["bathrooms"] ?>
                            </strong>

                            <br>

                            <small class="text-muted">
                                Bathrooms
                            </small>

                        </div>


                        <div class="col-4">

                            <strong>
                                <?= htmlspecialchars($property["property_type"]) ?>
                            </strong>

                            <br>

                            <small class="text-muted">
                                Type
                            </small>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================================
                         Viewing Section
                    ================================================== -->

                    <?php if (
                        $property["verification_status"] === "verified"
                        && $property["status"] === "available"
                    ): ?>


                        <!-- Viewing Button -->

                        <?php if (!$isLoggedIn): ?>

                            <div class="d-grid">

                                <a
                                    href="login.php"
                                    class="btn btn-primary btn-lg"
                                >
                                    Login to Request Viewing
                                </a>

                            </div>


                            <small class="text-muted text-center d-block mt-2">

                                You need a tenant account to request
                                a property viewing.

                            </small>


                        <?php elseif ($currentRole === "tenant"): ?>

                            <div class="d-grid">

                                <a
                                    href="tenant/request-viewing.php?id=<?= $property_id ?>"
                                    class="btn btn-primary btn-lg"
                                >
                                    Request Viewing
                                </a>

                            </div>


                            <small class="text-muted text-center d-block mt-2">

                                Choose your preferred date and time
                                for viewing.

                            </small>


                        <?php else: ?>

                            <div class="d-grid">

                                <button
                                    class="btn btn-secondary btn-lg"
                                    disabled
                                >
                                    Tenant Viewing Request Only
                                </button>

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
                             Favourite Button
                        ================================================== -->

                        <?php if (
                            $isLoggedIn
                            && $currentRole === "tenant"
                        ): ?>

                            <form
                                action="tenant/toggle-favourite.php"
                                method="POST"
                                class="mt-2"
                            >

                                <input
                                    type="hidden"
                                    name="property_id"
                                    value="<?= $property_id ?>"
                                >


                                <?php if ($isFavourite): ?>

                                    <button
                                        type="submit"
                                        class="btn btn-danger btn-lg w-100 favourite-button"
                                    >
                                        ❤️ Remove from Favourites
                                    </button>

                                <?php else: ?>

                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-lg w-100 favourite-button"
                                    >
                                        ♡ Add to Favourites
                                    </button>

                                <?php endif; ?>

                            </form>

                        <?php endif; ?>


                    <?php elseif (
                        $property["verification_status"] !== "verified"
                    ): ?>

                        <div class="d-grid">

                            <button
                                class="btn btn-secondary btn-lg"
                                disabled
                            >
                                Awaiting Verification
                            </button>

                        </div>


                        <small class="text-muted text-center d-block mt-2">

                            This property must be verified before
                            viewing requests can be made.

                        </small>


                    <?php else: ?>

                        <div class="d-grid">

                            <button
                                class="btn btn-secondary btn-lg"
                                disabled
                            >
                                Property Not Available
                            </button>

                        </div>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 Landlord
            ================================================== -->

            <div class="card shadow-sm mt-4">

                <div class="card-body">

                    <h5>
                        Landlord
                    </h5>

                    <hr>


                    <p class="mb-1">

                        <strong>

                            <?= htmlspecialchars(
                                $property["landlord_name"]
                            ) ?>

                        </strong>

                    </p>


                    <p class="mb-1">

                        📞

                        <?= htmlspecialchars(
                            $property["landlord_phone"]
                        ) ?>

                    </p>


                    <p class="mb-0">

                        ✉️

                        <?= htmlspecialchars(
                            $property["landlord_email"]
                        ) ?>

                    </p>

                </div>

            </div>


        </div>

    </div>


    <!-- =====================================================
         Description and Location
    ====================================================== -->

    <div class="row mt-5">


        <!-- =================================================
             Description
        ================================================== -->

        <div class="col-lg-8">


            <div class="card shadow-sm">

                <div class="card-body">

                    <h4>
                        Property Description
                    </h4>

                    <hr>


                    <p style="white-space: pre-line;">

                        <?= htmlspecialchars(
                            $property["description"] ??
                            "No description provided."
                        ) ?>

                    </p>

                </div>

            </div>


            <!-- Amenities -->

            <div class="card shadow-sm mt-4">

                <div class="card-body">

                    <h4>
                        Amenities
                    </h4>

                    <hr>


                    <p style="white-space: pre-line;">

                        <?= htmlspecialchars(
                            $property["amenities"] ??
                            "No amenities listed."
                        ) ?>

                    </p>

                </div>

            </div>


        </div>


        <!-- =================================================
             Location
        ================================================== -->

        <div class="col-lg-4">


            <div class="card shadow-sm">

                <div class="card-body">

                    <h4>
                        Location
                    </h4>

                    <hr>


                    <p>

                        <strong>
                            County:
                        </strong>

                        <?= htmlspecialchars(
                            $property["county"] ?? ""
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Town:
                        </strong>

                        <?= htmlspecialchars(
                            $property["town"] ?? ""
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Area:
                        </strong>

                        <?= htmlspecialchars(
                            $property["area"] ?? ""
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Address:
                        </strong>

                        <?= htmlspecialchars(
                            $property["address"] ?? ""
                        ) ?>

                    </p>

                </div>

            </div>


        </div>

    </div>


</div>


<!-- =========================================================
     Bootstrap JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>

