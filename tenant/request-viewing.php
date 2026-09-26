<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("tenant");

$property_id = (int)($_GET["id"] ?? 0);

if ($property_id <= 0) {
    die("Invalid property.");
}


/*
|--------------------------------------------------------------------------
| Get Verified Property
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.title,
        p.property_type,
        p.county,
        p.town,
        p.area,
        p.rent,
        p.landlord_id,
        u.full_name AS landlord_name

    FROM properties p

    INNER JOIN users u
        ON p.landlord_id = u.id

    WHERE p.id = ?
    AND p.verification_status = 'verified'
    AND p.status = 'available'

    LIMIT 1
");

$stmt->execute([$property_id]);

$property = $stmt->fetch();

if (!$property) {
    die("This property is not available for viewing requests.");
}


$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Submit Viewing Request
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $requested_date = $_POST["requested_date"] ?? "";
    $requested_time = $_POST["requested_time"] ?? "";
    $request_message = trim($_POST["message"] ?? "");

    if ($requested_date === "" || $requested_time === "") {

        $error = "Please select a viewing date and time.";

    } elseif ($requested_date < date("Y-m-d")) {

        $error = "Viewing date cannot be in the past.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate pending requests
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM viewing_requests

                WHERE tenant_id = ?
                AND property_id = ?
                AND status = 'pending'

                LIMIT 1
            ");

            $check->execute([
                currentUserId(),
                $property_id
            ]);

            if ($check->fetch()) {

                $error = "You already have a pending viewing request for this property.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Insert Request
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO viewing_requests
                    (
                        tenant_id,
                        property_id,
                        landlord_id,
                        requested_date,
                        requested_time,
                        message,
                        status
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'pending'
                    )
                ");

                $stmt->execute([
                    currentUserId(),
                    $property_id,
                    $property["landlord_id"],
                    $requested_date,
                    $requested_time,
                    $request_message
                ]);

                /*
                |--------------------------------------------------------------------------
                | Create Notification for Landlord
                |--------------------------------------------------------------------------
                */

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
                    $property["landlord_id"],
                    "New Viewing Request",
                    "A tenant has requested to view your property: " . $property["title"]
                ]);

                $message = "Viewing request submitted successfully.";

            }

        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();

        }

    }

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

    <title>Request Viewing - Verified House Kenya</title>

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
            max-width: 700px;
            margin: 40px auto;
            border: none;
            border-radius: 12px;
        }

        .property-summary {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-dark bg-dark">

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
                class="btn btn-outline-light btn-sm"
            >
                Dashboard
            </a>

        </div>

    </div>

</nav>


<div class="container">

    <div class="card request-card shadow-sm">

        <div class="card-body p-4">


            <h2 class="mb-1">
                Request Property Viewing
            </h2>

            <p class="text-muted mb-4">
                Choose a convenient date and time to view this property.
            </p>


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


            <!-- PROPERTY -->

            <div class="property-summary mb-4">

                <h4>
                    <?= htmlspecialchars($property["title"]) ?>
                </h4>

                <p class="mb-1">

                    <?= htmlspecialchars($property["property_type"]) ?>

                </p>

                <p class="mb-1">

                    <?= htmlspecialchars($property["area"]) ?>,
                    <?= htmlspecialchars($property["town"]) ?>,
                    <?= htmlspecialchars($property["county"]) ?>

                </p>

                <p class="mb-1">

                    <strong>
                        KSh <?= number_format($property["rent"], 2) ?>
                    </strong>
                    / month

                </p>

                <p class="mb-0 text-muted">

                    Landlord:
                    <?= htmlspecialchars($property["landlord_name"]) ?>

                </p>

            </div>


            <?php if (!$message): ?>


                <form method="POST">


                    <!-- DATE -->

                    <div class="mb-3">

                        <label class="form-label">
                            Preferred Viewing Date
                        </label>

                        <input
                            type="date"
                            name="requested_date"
                            class="form-control"
                            min="<?= date("Y-m-d") ?>"
                            required
                        >

                    </div>


                    <!-- TIME -->

                    <div class="mb-3">

                        <label class="form-label">
                            Preferred Viewing Time
                        </label>

                        <input
                            type="time"
                            name="requested_time"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- MESSAGE -->

                    <div class="mb-4">

                        <label class="form-label">
                            Message
                        </label>

                        <textarea
                            name="message"
                            class="form-control"
                            rows="4"
                            placeholder="Optional message to the landlord..."
                        ></textarea>

                    </div>


                    <div class="d-flex gap-2">

                        <a
                            href="../property.php?id=<?= $property_id ?>"
                            class="btn btn-outline-secondary"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary flex-grow-1"
                        >
                            Submit Viewing Request
                        </button>

                    </div>


                </form>


            <?php else: ?>

                <div class="text-center mt-3">

                    <a
                        href="bookings.php"
                        class="btn btn-primary"
                    >
                        View My Requests
                    </a>

                    <a
                        href="../search.php"
                        class="btn btn-outline-secondary"
                    >
                        Search More Houses
                    </a>

                </div>

            <?php endif; ?>


        </div>

    </div>

</div>


</body>

</html>