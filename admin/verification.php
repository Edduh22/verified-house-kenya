<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("admin");

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| HANDLE VERIFY / REJECT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $property_id = (int)($_POST["property_id"] ?? 0);
    $action = $_POST["action"] ?? "";
    $remarks = trim($_POST["remarks"] ?? "");

    if ($property_id <= 0) {

        $error = "Invalid property.";

    } elseif ($action !== "verify" && $action !== "reject") {

        $error = "Invalid action.";

    } else {

        try {

            /*
            | Get property and landlord
            */

            $stmt = $pdo->prepare("
                SELECT
                    p.id,
                    p.title,
                    p.landlord_id
                FROM properties p
                WHERE p.id = ?
                LIMIT 1
            ");

            $stmt->execute([$property_id]);

            $property = $stmt->fetch();

            if (!$property) {

                $error = "Property not found.";

            } else {

                /*
                | VERIFY
                */

                if ($action === "verify") {

                    $stmt = $pdo->prepare("
                        UPDATE properties
                        SET
                            verification_status = 'verified',
                            admin_remarks = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $remarks,
                        $property_id
                    ]);


                    /*
                    | Verification history
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO property_verification
                        (
                            property_id,
                            admin_id,
                            verification_type,
                            remarks,
                            status,
                            verified_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            'property',
                            ?,
                            'approved',
                            NOW()
                        )
                    ");

                    $stmt->execute([
                        $property_id,
                        currentUserId(),
                        $remarks
                    ]);


                    /*
                    | Landlord notification
                    */

                    $notification =
                        'Your property "' .
                        $property["title"] .
                        '" has been verified and approved.';

                    if ($remarks !== "") {

                        $notification .=
                            " Admin remarks: " . $remarks;
                    }


                    $stmt = $pdo->prepare("
                        INSERT INTO notifications
                        (
                            user_id,
                            title,
                            message,
                            is_read
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            0
                        )
                    ");

                    $stmt->execute([
                        $property["landlord_id"],
                        "Property Verified",
                        $notification
                    ]);


                    $message =
                        "Property verified successfully. Landlord notified.";
                }


                /*
                | REJECT
                */

                if ($action === "reject") {

                    $stmt = $pdo->prepare("
                        UPDATE properties
                        SET
                            verification_status = 'rejected',
                            admin_remarks = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $remarks,
                        $property_id
                    ]);


                    /*
                    | Rejection history
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO property_verification
                        (
                            property_id,
                            admin_id,
                            verification_type,
                            remarks,
                            status,
                            verified_at
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            'property',
                            ?,
                            'rejected',
                            NOW()
                        )
                    ");

                    $stmt->execute([
                        $property_id,
                        currentUserId(),
                        $remarks
                    ]);


                    /*
                    | Landlord notification
                    */

                    $notification =
                        'Your property "' .
                        $property["title"] .
                        '" has been rejected during verification.';

                    if ($remarks !== "") {

                        $notification .=
                            " Admin remarks: " . $remarks;

                    } else {

                        $notification .=
                            " Please review the property and make the necessary corrections.";

                    }


                    $stmt = $pdo->prepare("
                        INSERT INTO notifications
                        (
                            user_id,
                            title,
                            message,
                            is_read
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            0
                        )
                    ");

                    $stmt->execute([
                        $property["landlord_id"],
                        "Property Rejected",
                        $notification
                    ]);


                    $message =
                        "Property rejected successfully. Landlord notified.";
                }
            }

        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET PENDING PROPERTIES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
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
        p.admin_remarks,
        p.created_at,

        u.full_name AS landlord_name,
        u.phone AS landlord_phone,
        u.email AS landlord_email

    FROM properties p

    INNER JOIN users u
        ON p.landlord_id = u.id

    WHERE p.verification_status = 'pending'

    ORDER BY p.created_at ASC
");

$properties = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| SIMPLE OUTPUT
|--------------------------------------------------------------------------
*/

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Property Verification</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>Property Verification</h2>

            <p class="text-muted">
                Review properties submitted by landlords.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-secondary"
        >
            Dashboard
        </a>

    </div>


    <?php if ($message !== ""): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <?php if (empty($properties)): ?>

        <div class="alert alert-info">

            No properties are currently pending verification.

        </div>

    <?php endif; ?>


    <?php foreach ($properties as $property): ?>

        <div class="card mb-4 shadow-sm">

            <div class="card-body">

                <h4>
                    <?= htmlspecialchars($property["title"]) ?>
                </h4>

                <p class="text-muted">

                    <?= htmlspecialchars($property["property_type"]) ?>

                    ·

                    <?= htmlspecialchars($property["town"]) ?>

                    ·

                    <?= htmlspecialchars($property["area"]) ?>

                </p>


                <p>

                    <strong>
                        KSh <?= number_format($property["rent"], 2) ?>
                    </strong>

                    / month

                </p>


                <p>

                    Bedrooms:
                    <strong>
                        <?= htmlspecialchars($property["bedrooms"]) ?>
                    </strong>

                    &nbsp; | &nbsp;

                    Bathrooms:
                    <strong>
                        <?= htmlspecialchars($property["bathrooms"]) ?>
                    </strong>

                </p>


                <hr>


                <h6>Landlord</h6>

                <p class="mb-1">

                    <?= htmlspecialchars($property["landlord_name"]) ?>

                </p>

                <p class="text-muted mb-1">

                    <?= htmlspecialchars($property["landlord_phone"]) ?>

                </p>

                <p class="text-muted">

                    <?= htmlspecialchars($property["landlord_email"]) ?>

                </p>


                <a
                    href="../property.php?id=<?= (int)$property["id"] ?>"
                    target="_blank"
                    class="btn btn-outline-primary btn-sm"
                >
                    View Property
                </a>


                <hr>


                <form method="POST">

                    <input
                        type="hidden"
                        name="property_id"
                        value="<?= (int)$property["id"] ?>"
                    >


                    <div class="mb-3">

                        <label class="form-label">
                            Admin Remarks
                        </label>

                        <textarea
                            name="remarks"
                            class="form-control"
                            rows="3"
                            placeholder="Enter remarks..."
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        name="action"
                        value="verify"
                        class="btn btn-success"
                    >
                        ✓ Verify
                    </button>


                    <button
                        type="submit"
                        name="action"
                        value="reject"
                        class="btn btn-danger"
                    >
                        ✕ Reject
                    </button>

                </form>

            </div>

        </div>

    <?php endforeach; ?>

</div>

</body>

</html>

