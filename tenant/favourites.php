<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("tenant");

$tenant_id = currentUserId();


/*
|--------------------------------------------------------------------------
| Get Tenant Favourites
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        f.id AS favourite_id,

        p.id AS property_id,
        p.title,
        p.description,
        p.property_type,
        p.county,
        p.town,
        p.area,
        p.rent,
        p.bedrooms,
        p.bathrooms,
        p.status,
        p.verification_status,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS image_path

    FROM favourites f

    INNER JOIN properties p
        ON f.property_id = p.id

    WHERE f.tenant_id = ?

    ORDER BY f.id DESC
");

$stmt->execute([
    $tenant_id
]);

$favourites = $stmt->fetchAll();

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
        My Favourites - Verified House Kenya
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .property-card {
            height: 100%;
            transition: 0.2s ease;
        }

        .property-card:hover {
            transform: translateY(-3px);
        }

        .property-image {
            height: 220px;
            object-fit: cover;
        }

        .empty-box {
            padding: 70px 20px;
        }

        .property-price {
            font-size: 20px;
            font-weight: bold;
        }

    </style>

</head>


<body>


<!-- =========================================================
     Navigation
========================================================= -->

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a
            href="../index.php"
            class="navbar-brand"
        >
            Verified House Kenya
        </a>


        <div>

            <a
                href="../search.php"
                class="btn btn-light btn-sm me-2"
            >
                Search Houses
            </a>


            <a
                href="dashboard.php"
                class="btn btn-outline-light btn-sm"
            >
                Dashboard
            </a>

        </div>

    </div>

</nav>


<!-- =========================================================
     Main Content
========================================================= -->

<div class="container py-5">


    <!-- Page Heading -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                ❤️ My Favourites
            </h2>

            <p class="text-muted mb-0">
                Properties you have saved for later.
            </p>

        </div>


        <a
            href="../search.php"
            class="btn btn-primary"
        >
            Search More Houses
        </a>

    </div>


    <?php if (empty($favourites)): ?>


        <!-- =================================================
             Empty State
        ================================================== -->

        <div class="card shadow-sm">

            <div class="card-body text-center empty-box">

                <div
                    style="font-size: 60px;"
                    class="mb-3"
                >
                    ❤️
                </div>


                <h4>
                    No Favourite Properties Yet
                </h4>


                <p class="text-muted">

                    When you find a property you like,
                    click "Add to Favourites" to save it here.

                </p>


                <a
                    href="../search.php"
                    class="btn btn-primary"
                >
                    Browse Houses
                </a>

            </div>

        </div>


    <?php else: ?>


        <!-- =================================================
             Favourite Count
        ================================================== -->

        <div class="alert alert-light border mb-4">

            You have saved
            <strong><?= count($favourites) ?></strong>
            <?= count($favourites) === 1 ? "property" : "properties" ?>.

        </div>


        <!-- =================================================
             Property Cards
        ================================================== -->

        <div class="row g-4">


            <?php foreach ($favourites as $property): ?>


                <div class="col-md-6 col-lg-4">


                    <div class="card shadow-sm property-card">


                        <!-- Property Image -->

                        <?php if (!empty($property["image_path"])): ?>

                            <img
                                src="../<?= htmlspecialchars(
                                    $property["image_path"]
                                ) ?>"
                                class="card-img-top property-image"
                                alt="Property image"
                            >

                        <?php else: ?>

                            <div
                                class="property-image bg-secondary text-white d-flex align-items-center justify-content-center"
                            >
                                No Image
                            </div>

                        <?php endif; ?>


                        <div class="card-body">


                            <!-- Verification -->

                            <?php if (
                                $property["verification_status"] === "verified"
                            ): ?>

                                <span class="badge bg-success mb-2">
                                    Verified
                                </span>

                            <?php elseif (
                                $property["verification_status"] === "pending"
                            ): ?>

                                <span class="badge bg-warning text-dark mb-2">
                                    Pending Verification
                                </span>

                            <?php else: ?>

                                <span class="badge bg-danger mb-2">
                                    Rejected
                                </span>

                            <?php endif; ?>


                            <!-- Title -->

                            <h5 class="card-title">

                                <?= htmlspecialchars(
                                    $property["title"]
                                ) ?>

                            </h5>


                            <!-- Location -->

                            <p class="text-muted mb-2">

                                📍

                                <?= htmlspecialchars(
                                    $property["area"] ?? ""
                                ) ?>

                                <?php if (!empty($property["town"])): ?>

                                    ,
                                    <?= htmlspecialchars(
                                        $property["town"]
                                    ) ?>

                                <?php endif; ?>

                            </p>


                            <!-- Rent -->

                            <div class="property-price text-primary">

                                KES
                                <?= number_format(
                                    $property["rent"]
                                ) ?>

                                <small class="text-muted">
                                    / month
                                </small>

                            </div>


                            <hr>


                            <!-- Details -->

                            <div class="d-flex justify-content-between text-muted small mb-3">

                                <span>
                                    🛏
                                    <?= (int) $property["bedrooms"] ?>
                                    Bedrooms
                                </span>


                                <span>
                                    🚿
                                    <?= (int) $property["bathrooms"] ?>
                                    Bathrooms
                                </span>

                            </div>


                            <!-- Actions -->

                            <div class="d-grid gap-2">


                                <a
                                    href="../property.php?id=<?= (int) $property["property_id"] ?>"
                                    class="btn btn-primary"
                                >
                                    View Property
                                </a>


                                <form
                                    action="toggle-favourite.php"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="property_id"
                                        value="<?= (int) $property["property_id"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger w-100"
                                    >
                                        ❤️ Remove Favourite
                                    </button>

                                </form>


                            </div>


                        </div>

                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


<!-- =========================================================
     Bootstrap JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
