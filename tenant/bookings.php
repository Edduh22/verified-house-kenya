<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("tenant");

$tenant_id = currentUserId();


/*
|--------------------------------------------------------------------------
| Get Tenant Viewing Requests
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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
        p.property_type,
        p.county,
        p.town,
        p.area,
        p.rent,

        u.full_name AS landlord_name,
        u.phone AS landlord_phone,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS primary_image

    FROM viewing_requests vr

    INNER JOIN properties p
        ON vr.property_id = p.id

    INNER JOIN users u
        ON vr.landlord_id = u.id

    WHERE vr.tenant_id = ?

    ORDER BY vr.created_at DESC
");

$stmt->execute([$tenant_id]);

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
        My Viewing Requests - Verified House Kenya
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .navbar-brand {
            font-weight: 700;
        }

        .request-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .property-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .no-image {
            height: 200px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        .status-badge {
            font-size: 13px;
            padding: 7px 12px;
        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a
            href="dashboard.php"
            class="navbar-brand"
        >
            Verified House Kenya
        </a>


        <div>

            <a
                href="dashboard.php"
                class="btn btn-outline-light btn-sm me-2"
            >
                Dashboard
            </a>

            <a
                href="../search.php"
                class="btn btn-light btn-sm"
            >
                Search Houses
            </a>

        </div>

    </div>

</nav>


<div class="container py-5">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>
                My Viewing Requests
            </h2>

            <p class="text-muted mb-0">

                Track the properties you have requested to view.

            </p>

        </div>


        <span class="badge bg-primary fs-6">

            <?= count($requests) ?> Request(s)

        </span>

    </div>


    <?php if (empty($requests)): ?>


        <div class="card shadow-sm">

            <div class="card-body text-center py-5">

                <h4>
                    No Viewing Requests
                </h4>

                <p class="text-muted">

                    You have not requested to view any properties yet.

                </p>

                <a
                    href="../search.php"
                    class="btn btn-primary"
                >
                    Search Houses
                </a>

            </div>

        </div>


    <?php else: ?>


        <div class="row g-4">


            <?php foreach ($requests as $request): ?>


                <?php

                /*
                |--------------------------------------------------------------------------
                | Status Badge
                |--------------------------------------------------------------------------
                */

                switch ($request["status"]) {

                    case "approved":

                        $statusClass = "bg-success";
                        $statusText = "Approved";

                        break;

                    case "rejected":

                        $statusClass = "bg-danger";
                        $statusText = "Rejected";

                        break;

                    case "completed":

                        $statusClass = "bg-primary";
                        $statusText = "Completed";

                        break;

                    case "cancelled":

                        $statusClass = "bg-secondary";
                        $statusText = "Cancelled";

                        break;

                    default:

                        $statusClass = "bg-warning text-dark";
                        $statusText = "Pending";

                        break;

                }

                ?>


                <div class="col-md-6 col-lg-4">


                    <div class="card request-card shadow-sm">


                        <!-- PROPERTY IMAGE -->

                        <?php if (!empty($request["primary_image"])): ?>

                            <img
                                src="../<?= htmlspecialchars($request["primary_image"]) ?>"
                                class="property-image"
                                alt="Property image"
                            >

                        <?php else: ?>

                            <div class="no-image">

                                No Image Available

                            </div>

                        <?php endif; ?>


                        <div class="card-body">


                            <!-- TITLE -->

                            <div class="d-flex justify-content-between align-items-start mb-2">

                                <h5 class="mb-0">

                                    <?= htmlspecialchars(
                                        $request["property_title"]
                                    ) ?>

                                </h5>


                                <span
                                    class="badge <?= $statusClass ?> status-badge"
                                >

                                    <?= $statusText ?>

                                </span>

                            </div>


                            <!-- LOCATION -->

                            <p class="text-muted mb-2">

                                <?= htmlspecialchars(
                                    $request["area"]
                                ) ?>,

                                <?= htmlspecialchars(
                                    $request["town"]
                                ) ?>

                            </p>


                            <!-- RENT -->

                            <p class="mb-2">

                                <strong>

                                    KSh
                                    <?= number_format(
                                        $request["rent"]
                                    ) ?>

                                </strong>

                                / month

                            </p>


                            <hr>


                            <!-- VIEWING DETAILS -->

                            <h6>
                                Viewing Details
                            </h6>


                            <p class="mb-1">

                                <strong>
                                    Date:
                                </strong>

                                <?= htmlspecialchars(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $request["requested_date"]
                                        )
                                    )
                                ) ?>

                            </p>


                            <p class="mb-1">

                                <strong>
                                    Time:
                                </strong>

                                <?= htmlspecialchars(
                                    date(
                                        "h:i A",
                                        strtotime(
                                            $request["requested_time"]
                                        )
                                    )
                                ) ?>

                            </p>


                            <p class="mb-2">

                                <strong>
                                    Landlord:
                                </strong>

                                <?= htmlspecialchars(
                                    $request["landlord_name"]
                                ) ?>

                            </p>


                            <?php if (!empty($request["message"])): ?>

                                <div class="alert alert-light border small">

                                    <strong>
                                        Your message:
                                    </strong>

                                    <br>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $request["message"]
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <?php if (!empty($request["landlord_response"])): ?>

                                <div class="alert alert-info small">

                                    <strong>
                                        Landlord response:
                                    </strong>

                                    <br>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $request["landlord_response"]
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <!-- PROPERTY BUTTON -->

                            <a
                                href="../property.php?id=<?= $request["property_id"] ?>"
                                class="btn btn-outline-primary btn-sm w-100"
                            >

                                View Property

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