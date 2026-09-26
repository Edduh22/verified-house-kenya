<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("landlord");

$landlord_id = currentUserId();

$stmt = $pdo->prepare("
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

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS primary_image

    FROM properties p

    WHERE p.landlord_id = ?

    ORDER BY p.created_at DESC
");

$stmt->execute([$landlord_id]);

$properties = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Properties - Verified House Kenya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        .property-image {

            width: 100%;
            height: 200px;
            object-fit: cover;

        }

        .property-card {

            transition: 0.2s;

        }

        .property-card:hover {

            transform: translateY(-3px);

        }

    </style>

</head>

<body class="bg-light">


<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a
            class="navbar-brand"
            href="dashboard.php"
        >
            Verified House Kenya
        </a>

        <div>

            <a
                href="dashboard.php"
                class="btn btn-light btn-sm me-2"
            >
                Dashboard
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

            <h2>
                My Properties
            </h2>

            <p class="text-muted mb-0">

                Manage properties you have listed.

            </p>

        </div>


        <a
            href="add-property.php"
            class="btn btn-primary"
        >

            + Add Property

        </a>

    </div>


    <?php if (empty($properties)): ?>

        <div class="card shadow-sm">

            <div class="card-body text-center py-5">

                <h4>
                    No Properties Yet
                </h4>

                <p class="text-muted">

                    You haven't added any properties.

                </p>

                <a
                    href="add-property.php"
                    class="btn btn-primary"
                >

                    Add Your First Property

                </a>

            </div>

        </div>

    <?php else: ?>


        <div class="row g-4">


            <?php foreach ($properties as $property): ?>


                <div class="col-md-6 col-lg-4">

                    <div class="card property-card shadow-sm h-100">


                        <?php if (!empty($property["primary_image"])): ?>

                            <img
                                src="../<?= htmlspecialchars($property["primary_image"]) ?>"
                                class="property-image card-img-top"
                                alt="Property image"
                            >

                        <?php else: ?>

                            <div
                                class="property-image bg-secondary d-flex align-items-center justify-content-center text-white"
                            >

                                No Image

                            </div>

                        <?php endif; ?>


                        <div class="card-body">


                            <h5 class="card-title">

                                <?= htmlspecialchars($property["title"]) ?>

                            </h5>


                            <p class="text-muted mb-2">

                                <?= htmlspecialchars($property["property_type"]) ?>

                            </p>


                            <p class="mb-1">

                                <strong>
                                    Location:
                                </strong>

                                <?= htmlspecialchars($property["area"] ?? "") ?>

                                <?php if (!empty($property["town"])): ?>

                                    ,
                                    <?= htmlspecialchars($property["town"]) ?>

                                <?php endif; ?>

                            </p>


                            <p class="mb-1">

                                <strong>
                                    Rent:
                                </strong>

                                KES
                                <?= number_format($property["rent"]) ?>

                                / month

                            </p>


                            <p class="mb-3">

                                <strong>
                                    Rooms:
                                </strong>

                                <?= (int) $property["bedrooms"] ?>

                                bedrooms

                                ·

                                <?= (int) $property["bathrooms"] ?>

                                bathrooms

                            </p>


                            <!-- Verification Status -->

                            <?php

                            $verification =
                                $property["verification_status"];

                            if ($verification === "verified") {

                                $badgeClass = "bg-success";
                                $badgeText = "Verified";

                            } elseif ($verification === "rejected") {

                                $badgeClass = "bg-danger";
                                $badgeText = "Rejected";

                            } else {

                                $badgeClass = "bg-warning text-dark";
                                $badgeText = "Pending Verification";

                            }

                            ?>


                            <span class="badge <?= $badgeClass ?> mb-3">

                                <?= $badgeText ?>

                            </span>


                            <br>


                            <!-- Availability -->

                            <?php

                            if ($property["status"] === "available") {

                                $statusClass = "text-success";
                                $statusText = "Available";

                            } else {

                                $statusClass = "text-danger";
                                $statusText = "Occupied";

                            }

                            ?>


                            <small class="<?= $statusClass ?>">

                                ● <?= $statusText ?>

                            </small>


                        </div>


                        <div class="card-footer bg-white">


                            <a
                                href="../property.php?id=<?= (int) $property["id"] ?>"
                                class="btn btn-outline-primary btn-sm"
                            >

                                View

                            </a>


                            <a
                                href="edit-property.php?id=<?= (int) $property["id"] ?>"
                                class="btn btn-outline-secondary btn-sm"
                            >

                                Edit

                            </a>


                        </div>


                    </div>

                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


</body>

</html>