<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("landlord");

$landlord_id = currentUserId();

$property_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($property_id <= 0) {
    die("Invalid property.");
}


/*
|--------------------------------------------------------------------------
| Get Property
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM properties
    WHERE id = ?
    AND landlord_id = ?
    LIMIT 1
");

$stmt->execute([
    $property_id,
    $landlord_id
]);

$property = $stmt->fetch();

if (!$property) {
    die("Property not found or you do not have permission to edit it.");
}


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

$success = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $property_type = trim($_POST["property_type"] ?? "");
    $rent = trim($_POST["rent"] ?? "");
    $bedrooms = trim($_POST["bedrooms"] ?? "");
    $bathrooms = trim($_POST["bathrooms"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $amenities = trim($_POST["amenities"] ?? "");
    $county = trim($_POST["county"] ?? "");
    $town = trim($_POST["town"] ?? "");
    $area = trim($_POST["area"] ?? "");
    $address = trim($_POST["address"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $title === ""
        || $property_type === ""
        || $rent === ""
        || $county === ""
        || $town === ""
        || $area === ""
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!is_numeric($rent) || $rent < 0) {

        $error = "Please enter a valid rent amount.";

    } elseif (
        !is_numeric($bedrooms)
        || $bedrooms < 0
        || !is_numeric($bathrooms)
        || $bathrooms < 0
    ) {

        $error = "Please enter valid bedroom and bathroom values.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | Update Property
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


            $updateStmt = $pdo->prepare("
                UPDATE properties

                SET
                    title = ?,
                    property_type = ?,
                    rent = ?,
                    bedrooms = ?,
                    bathrooms = ?,
                    description = ?,
                    amenities = ?,
                    county = ?,
                    town = ?,
                    area = ?,
                    address = ?,
                    verification_status = 'pending',
                    admin_remarks = NULL,
                    updated_at = CURRENT_TIMESTAMP

                WHERE id = ?
                AND landlord_id = ?
            ");

            $updateStmt->execute([
                $title,
                $property_type,
                $rent,
                (int) $bedrooms,
                (int) $bathrooms,
                $description,
                $amenities,
                $county,
                $town,
                $area,
                $address,
                $property_id,
                $landlord_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | Upload New Images
            |--------------------------------------------------------------------------
            */

            if (
                isset($_FILES["images"])
                && isset($_FILES["images"]["name"])
            ) {

                $uploadDir = "../uploads/properties/";


                if (!is_dir($uploadDir)) {

                    mkdir(
                        $uploadDir,
                        0777,
                        true
                    );

                }


                $allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];


                $fileCount = count(
                    $_FILES["images"]["name"]
                );


                for ($i = 0; $i < $fileCount; $i++) {

                    if (
                        $_FILES["images"]["error"][$i]
                        === UPLOAD_ERR_NO_FILE
                    ) {
                        continue;
                    }


                    if (
                        $_FILES["images"]["error"][$i]
                        !== UPLOAD_ERR_OK
                    ) {
                        throw new Exception(
                            "One of the images could not be uploaded."
                        );
                    }


                    $tmpName =
                        $_FILES["images"]["tmp_name"][$i];


                    $fileSize =
                        $_FILES["images"]["size"][$i];


                    /*
                    |--------------------------------------------------------------------------
                    | File Size
                    |--------------------------------------------------------------------------
                    */

                    if ($fileSize > 5 * 1024 * 1024) {

                        throw new Exception(
                            "Each image must be 5MB or smaller."
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | MIME Type
                    |--------------------------------------------------------------------------
                    */

                    $mimeType = mime_content_type(
                        $tmpName
                    );


                    if (
                        !in_array(
                            $mimeType,
                            $allowedTypes,
                            true
                        )
                    ) {

                        throw new Exception(
                            "Only JPG, PNG and WEBP images are allowed."
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Extension
                    |--------------------------------------------------------------------------
                    */

                    $extension = match ($mimeType) {

                        "image/jpeg" => "jpg",

                        "image/png" => "png",

                        "image/webp" => "webp",

                        default => throw new Exception(
                            "Invalid image type."
                        )

                    };


                    /*
                    |--------------------------------------------------------------------------
                    | Unique File Name
                    |--------------------------------------------------------------------------
                    */

                    $fileName =
                        uniqid(
                            "property_",
                            true
                        )
                        . "."
                        . $extension;


                    $destination =
                        $uploadDir . $fileName;


                    /*
                    |--------------------------------------------------------------------------
                    | Move Image
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !move_uploaded_file(
                            $tmpName,
                            $destination
                        )
                    ) {

                        throw new Exception(
                            "Failed to save uploaded image."
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Database Path
                    |--------------------------------------------------------------------------
                    */

                    $imagePath =
                        "uploads/properties/"
                        . $fileName;


                    /*
                    |--------------------------------------------------------------------------
                    | Save Image
                    |--------------------------------------------------------------------------
                    */

                    $imageStmt = $pdo->prepare("
                        INSERT INTO property_images
                        (
                            property_id,
                            image_path,
                            is_primary
                        )
                        VALUES (?, ?, 0)
                    ");

                    $imageStmt->execute([
                        $property_id,
                        $imagePath
                    ]);

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Add Verification History
            |--------------------------------------------------------------------------
            */

            $historyStmt = $pdo->prepare("
                INSERT INTO property_verification
                (
                    property_id,
                    admin_id,
                    verification_type,
                    remarks,
                    status
                )
                VALUES (?, NULL, 'edit_resubmission', ?, 'pending')
            ");

            $historyStmt->execute([
                $property_id,
                "Property edited by landlord and resubmitted for verification."
            ]);


            $pdo->commit();


            $success =
                "Property updated successfully and submitted for verification.";


            /*
            |--------------------------------------------------------------------------
            | Refresh Property Data
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT *
                FROM properties
                WHERE id = ?
                AND landlord_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $property_id,
                $landlord_id
            ]);

            $property = $stmt->fetch();


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                "Error updating property: "
                . $e->getMessage();

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

    <title>
        Edit Property - Verified House Kenya
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .form-card {
            max-width: 950px;
            margin: auto;
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


<!-- =========================================================
     Main Content
========================================================= -->

<div class="container py-5">


    <div class="card shadow-sm form-card">

        <div class="card-body p-4 p-md-5">


            <h2 class="mb-2">
                Edit Property
            </h2>


            <p class="text-muted mb-4">

                Update your property information below.

                After editing, the property will be submitted
                for verification again.

            </p>


            <!-- Success -->

            <?php if ($success !== ""): ?>

                <div class="alert alert-success">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <!-- Error -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-danger">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 Edit Form
            ================================================== -->

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- Title -->

                <div class="mb-3">

                    <label class="form-label">
                        Property Title *
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control"
                        value="<?= htmlspecialchars($property["title"]) ?>"
                        required
                    >

                </div>


                <!-- Property Type -->

                <div class="mb-3">

                    <label class="form-label">
                        Property Type *
                    </label>

                    <select
                        name="property_type"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Property Type
                        </option>


                        <?php

                        $propertyTypes = [
                            "Apartment",
                            "Bedsitter",
                            "Studio",
                            "House",
                            "Maisonette",
                            "Villa",
                            "Hostel",
                            "Single Room",
                            "Commercial"
                        ];

                        ?>


                        <?php foreach (
                            $propertyTypes
                            as $type
                        ): ?>

                            <option
                                value="<?= htmlspecialchars($type) ?>"
                                <?= $property["property_type"] === $type
                                    ? "selected"
                                    : "" ?>
                            >

                                <?= htmlspecialchars($type) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Rent -->

                <div class="mb-3">

                    <label class="form-label">
                        Monthly Rent (KES) *
                    </label>

                    <input
                        type="number"
                        name="rent"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($property["rent"]) ?>"
                        required
                    >

                </div>


                <!-- Bedrooms / Bathrooms -->

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Bedrooms
                        </label>

                        <input
                            type="number"
                            name="bedrooms"
                            class="form-control"
                            min="0"
                            value="<?= htmlspecialchars($property["bedrooms"]) ?>"
                        >

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Bathrooms
                        </label>

                        <input
                            type="number"
                            name="bathrooms"
                            class="form-control"
                            min="0"
                            value="<?= htmlspecialchars($property["bathrooms"]) ?>"
                        >

                    </div>


                </div>


                <!-- Description -->

                <div class="mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                    ><?= htmlspecialchars($property["description"] ?? "") ?></textarea>

                </div>


                <!-- Amenities -->

                <div class="mb-3">

                    <label class="form-label">
                        Amenities
                    </label>

                    <textarea
                        name="amenities"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars($property["amenities"] ?? "") ?></textarea>

                </div>


                <!-- County -->

                <div class="mb-3">

                    <label class="form-label">
                        County *
                    </label>

                    <input
                        type="text"
                        name="county"
                        class="form-control"
                        value="<?= htmlspecialchars($property["county"]) ?>"
                        required
                    >

                </div>


                <!-- Town -->

                <div class="mb-3">

                    <label class="form-label">
                        Town *
                    </label>

                    <input
                        type="text"
                        name="town"
                        class="form-control"
                        value="<?= htmlspecialchars($property["town"]) ?>"
                        required
                    >

                </div>


                <!-- Area -->

                <div class="mb-3">

                    <label class="form-label">
                        Area *
                    </label>

                    <input
                        type="text"
                        name="area"
                        class="form-control"
                        value="<?= htmlspecialchars($property["area"]) ?>"
                        required
                    >

                </div>


                <!-- Address -->

                <div class="mb-3">

                    <label class="form-label">
                        Address
                    </label>

                    <input
                        type="text"
                        name="address"
                        class="form-control"
                        value="<?= htmlspecialchars($property["address"] ?? "") ?>"
                    >

                </div>


                <!-- Existing Status -->

                <div class="alert alert-warning">

                    <strong>
                        Important:
                    </strong>

                    Saving changes will return this property
                    to <strong>Pending Verification</strong>.

                    The property must be verified again before
                    it is publicly searchable.

                </div>


                <!-- New Images -->

                <div class="mb-4">

                    <label class="form-label">

                        Add New Images

                    </label>

                    <input
                        type="file"
                        name="images[]"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    >

                    <small class="text-muted">

                        JPG, PNG or WEBP.
                        Maximum 5MB per image.

                    </small>

                </div>


                <!-- Buttons -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>


                    <a
                        href="my-properties.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>


            </form>


        </div>

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
