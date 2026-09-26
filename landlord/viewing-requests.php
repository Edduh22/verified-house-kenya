<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("landlord");

$landlord_id = currentUserId();

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Handle Request Action
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $request_id = (int)($_POST["request_id"] ?? 0);
    $action = $_POST["action"] ?? "";
    $landlord_response = trim($_POST["landlord_response"] ?? "");


    if ($request_id <= 0) {

        $error = "Invalid viewing request.";

    } elseif (!in_array($action, [
        "approve",
        "reject",
        "complete"
    ])) {

        $error = "Invalid action.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Make sure this request belongs to this landlord
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT
                    vr.id,
                    vr.tenant_id,
                    vr.property_id,
                    vr.status,
                    p.title AS property_title

                FROM viewing_requests vr

                INNER JOIN properties p
                    ON vr.property_id = p.id

                WHERE vr.id = ?
                AND vr.landlord_id = ?

                LIMIT 1
            ");

            $check->execute([
                $request_id,
                $landlord_id
            ]);

            $request = $check->fetch();


            if (!$request) {

                $error = "Viewing request not found.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Determine New Status
                |--------------------------------------------------------------------------
                */

                if ($action === "approve") {

                    $new_status = "approved";

                    if ($landlord_response === "") {
                        $landlord_response =
                            "Your viewing request has been approved.";
                    }

                } elseif ($action === "reject") {

                    $new_status = "rejected";

                    if ($landlord_response === "") {
                        $landlord_response =
                            "Your viewing request has been rejected.";
                    }

                } else {

                    $new_status = "completed";

                    if ($landlord_response === "") {
                        $landlord_response =
                            "The property viewing has been completed.";
                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Update Request
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    UPDATE viewing_requests

                    SET
                        status = ?,
                        landlord_response = ?,
                        updated_at = NOW()

                    WHERE id = ?
                    AND landlord_id = ?
                ");

                $stmt->execute([
                    $new_status,
                    $landlord_response,
                    $request_id,
                    $landlord_id
                ]);


                /*
                |--------------------------------------------------------------------------
                | Notification Title
                |--------------------------------------------------------------------------
                */

                if ($new_status === "approved") {

                    $notification_title =
                        "Viewing Request Approved";

                } elseif ($new_status === "rejected") {

                    $notification_title =
                        "Viewing Request Rejected";

                } else {

                    $notification_title =
                        "Viewing Completed";

                }


                /*
                |--------------------------------------------------------------------------
                | Create Tenant Notification
                |--------------------------------------------------------------------------
                */

                $notification_message =
                    "Your viewing request for "
                    . $request["property_title"]
                    . " has been "
                    . $new_status
                    . ". "
                    . $landlord_response;


                $stmt = $pdo->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        title,
                        message
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $request["tenant_id"],
                    $notification_title,
                    $notification_message
                ]);


                $message = "Viewing request updated successfully.";

            }


        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| Get Viewing Requests
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
        vr.updated_at,

        p.id AS property_id,
        p.title AS property_title,
        p.property_type,
        p.county,
        p.town,
        p.area,
        p.rent,

        u.id AS tenant_id,
        u.full_name AS tenant_name,
        u.email AS tenant_email,
        u.phone AS tenant_phone,

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
        ON vr.tenant_id = u.id

    WHERE vr.landlord_id = ?

    ORDER BY

        CASE vr.status

            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'rejected' THEN 4
            WHEN 'cancelled' THEN 5
            ELSE 6

        END,

        vr.created_at DESC
");

$stmt->execute([$landlord_id]);

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
        Viewing Requests - Verified House Kenya
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
            height: 210px;
            object-fit: cover;
        }

        .no-image {
            width: 100%;
            height: 210px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        .status-badge {
            font-size: 12px;
            padding: 7px 11px;
        }

        .tenant-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

    </style>

</head>


<body>


<!-- NAVIGATION -->

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
                href="my-properties.php"
                class="btn btn-light btn-sm"
            >
                My Properties
            </a>

        </div>

    </div>

</nav>


<div class="container py-5">


    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>
                Viewing Requests
            </h2>

            <p class="text-muted mb-0">

                Manage tenants requesting to view your properties.

            </p>

        </div>


        <span class="badge bg-primary fs-6">

            <?= count($requests) ?> Request(s)

        </span>

    </div>


    <!-- SUCCESS -->

    <?php if ($message): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <?php if (empty($requests)): ?>


        <div class="card shadow-sm">

            <div class="card-body text-center py-5">

                <h4>
                    No Viewing Requests
                </h4>

                <p class="text-muted">

                    You currently have no viewing requests.

                </p>


                <a
                    href="dashboard.php"
                    class="btn btn-primary"
                >
                    Back to Dashboard
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


                <div class="col-md-6 col-xl-4">


                    <div class="card request-card shadow-sm">


                        <!-- PROPERTY IMAGE -->

                        <?php if (!empty($request["primary_image"])): ?>

                            <img
                                src="../<?= htmlspecialchars(
                                    $request["primary_image"]
                                ) ?>"
                                class="property-image"
                                alt="Property image"
                            >

                        <?php else: ?>

                            <div class="no-image">

                                No Image Available

                            </div>

                        <?php endif; ?>


                        <div class="card-body">


                            <!-- PROPERTY -->

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


                            <p class="text-muted mb-2">

                                <?= htmlspecialchars(
                                    $request["area"]
                                ) ?>,

                                <?= htmlspecialchars(
                                    $request["town"]
                                ) ?>

                            </p>


                            <p class="mb-3">

                                <strong>

                                    KSh
                                    <?= number_format(
                                        $request["rent"]
                                    ) ?>

                                </strong>

                                / month

                            </p>


                            <!-- TENANT -->

                            <div class="tenant-box mb-3">

                                <h6>
                                    Tenant
                                </h6>

                                <hr class="my-2">


                                <p class="mb-1">

                                    <strong>

                                        <?= htmlspecialchars(
                                            $request["tenant_name"]
                                        ) ?>

                                    </strong>

                                </p>


                                <p class="mb-1 small">

                                    📞
                                    <?= htmlspecialchars(
                                        $request["tenant_phone"]
                                    ) ?>

                                </p>


                                <p class="mb-0 small">

                                    ✉️
                                    <?= htmlspecialchars(
                                        $request["tenant_email"]
                                    ) ?>

                                </p>

                            </div>


                            <!-- REQUEST DETAILS -->

                            <h6>
                                Requested Viewing
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


                            <p class="mb-2">

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


                            <?php if (!empty($request["message"])): ?>

                                <div class="alert alert-light border small">

                                    <strong>
                                        Tenant Message:
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
                                        Your Response:
                                    </strong>

                                    <br>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $request["landlord_response"]
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <!-- ACTIONS -->

                            <?php if ($request["status"] === "pending"): ?>


                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= $request["id"] ?>"
                                    >


                                    <div class="mb-2">

                                        <label class="form-label small">

                                            Response to Tenant

                                        </label>


                                        <textarea
                                            name="landlord_response"
                                            class="form-control"
                                            rows="2"
                                            placeholder="Optional message..."
                                        ></textarea>

                                    </div>


                                    <div class="d-flex gap-2">


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="btn btn-success btn-sm flex-fill"
                                        >

                                            ✓ Approve

                                        </button>


                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="btn btn-danger btn-sm flex-fill"
                                        >

                                            ✕ Reject

                                        </button>


                                    </div>

                                </form>


                            <?php elseif ($request["status"] === "approved"): ?>


                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= $request["id"] ?>"
                                    >


                                    <div class="mb-2">

                                        <label class="form-label small">

                                            Completion Note

                                        </label>


                                        <textarea
                                            name="landlord_response"
                                            class="form-control"
                                            rows="2"
                                            placeholder="Optional completion note..."
                                        ></textarea>

                                    </div>


                                    <button
                                        type="submit"
                                        name="action"
                                        value="complete"
                                        class="btn btn-primary btn-sm w-100"
                                    >

                                        ✓ Mark Viewing Completed

                                    </button>

                                </form>


                            <?php endif; ?>


                            <!-- PROPERTY LINK -->

                            <a
                                href="../property.php?id=<?= $request["property_id"] ?>"
                                class="btn btn-outline-primary btn-sm w-100 mt-2"
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
