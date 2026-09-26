<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("landlord");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $property_type = trim($_POST["property_type"] ?? "");
    $county = trim($_POST["county"] ?? "");
    $town = trim($_POST["town"] ?? "");
    $area = trim($_POST["area"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $rent = trim($_POST["rent"] ?? "");
    $bedrooms = (int) ($_POST["bedrooms"] ?? 0);
    $bathrooms = (int) ($_POST["bathrooms"] ?? 0);
    $amenities = trim($_POST["amenities"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validate required fields
    |--------------------------------------------------------------------------
    */

    if (
        empty($title) ||
        empty($property_type) ||
        empty($county) ||
        empty($town) ||
        empty($rent)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!is_numeric($rent) || $rent <= 0) {

        $error = "Please enter a valid monthly rent.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert property
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO properties
                (
                    landlord_id,
                    title,
                    description,
                    property_type,
                    county,
                    town,
                    area,
                    address,
                    rent,
                    bedrooms,
                    bathrooms,
                    amenities,
                    status,
                    verification_status
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    'available',
                    'pending'
                )
            ");

            $stmt->execute([
                currentUserId(),
                $title,
                $description,
                $property_type,
                $county,
                $town,
                $area,
                $address,
                $rent,
                $bedrooms,
                $bathrooms,
                $amenities
            ]);

            $property_id = $pdo->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | Handle images
            |--------------------------------------------------------------------------
            */

            if (
                isset($_FILES["images"]) &&
                !empty($_FILES["images"]["name"][0])
            ) {

                $uploadDirectory = "../uploads/properties/";

                $allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                foreach ($_FILES["images"]["tmp_name"] as $key => $tmpName) {

                    if (
                        $_FILES["images"]["error"][$key] !==
                        UPLOAD_ERR_OK
                    ) {
                        continue;
                    }

                    $fileType = mime_content_type($tmpName);

                    if (!in_array($fileType, $allowedTypes)) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Limit file size to 5MB
                    |--------------------------------------------------------------------------
                    */

                    if ($_FILES["images"]["size"][$key] > 5 * 1024 * 1024) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Generate unique filename
                    |--------------------------------------------------------------------------
                    */

                    $extension = strtolower(
                        pathinfo(
                            $_FILES["images"]["name"][$key],
                            PATHINFO_EXTENSION
                        )
                    );

                    $fileName =
                        uniqid("property_", true) .
                        "." .
                        $extension;

                    $destination =
                        $uploadDirectory . $fileName;

                    if (move_uploaded_file(
                        $tmpName,
                        $destination
                    )) {

                        $imagePath =
                            "uploads/properties/" . $fileName;

                        $imageStmt = $pdo->prepare("
                            INSERT INTO property_images
                            (
                                property_id,
                                image_path,
                                is_primary
                            )
                            VALUES (?, ?, ?)
                        ");

                        /*
                        |--------------------------------------------------------------------------
                        | First image becomes primary image
                        |--------------------------------------------------------------------------
                        */

                        $isPrimary = ($key === 0) ? 1 : 0;

                        $imageStmt->execute([
                            $property_id,
                            $imagePath,
                            $isPrimary
                        ]);
                    }
                }
            }

            $success =
                "Property added successfully and submitted for verification.";

        } catch (PDOException $e) {

            $error =
                "Unable to save property: " .
                $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Property - Verified House Kenya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">


<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a class="navbar-brand"
           href="dashboard.php">

            Verified House Kenya

        </a>

        <a href="../logout.php"
           class="btn btn-light btn-sm">

            Logout

        </a>

    </div>

</nav>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="mb-4">
                        Add Property
                    </h2>


                    <?php if ($error): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($success): ?>

                        <div class="alert alert-success">

                            <?= htmlspecialchars($success) ?>

                            <div class="mt-3">

                                <a
                                    href="my-properties.php"
                                    class="btn btn-success"
                                >

                                    View My Properties

                                </a>

                            </div>

                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >


                        <!-- Property Information -->

                        <h5 class="mb-3">
                            Property Information
                        </h5>


                        <div class="mb-3">

                            <label class="form-label">
                                Property Title *
                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                placeholder="e.g. Modern 2 Bedroom Apartment"
                                required
                            >

                        </div>


                        <div class="row">


                            <div class="col-md-6 mb-3">

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

                                    <option value="Apartment">
                                        Apartment
                                    </option>

                                    <option value="Bedsitter">
                                        Bedsitter
                                    </option>

                                    <option value="Studio">
                                        Studio
                                    </option>

                                    <option value="House">
                                        House
                                    </option>

                                    <option value="Maisonette">
                                        Maisonette
                                    </option>

                                    <option value="Villa">
                                        Villa
                                    </option>

                                    <option value="Hostel">
                                        Hostel
                                    </option>

                                    <option value="Single Room">
                                        Single Room
                                    </option>

                                    <option value="Commercial">
                                        Commercial
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Monthly Rent (KES) *
                                </label>

                                <input
                                    type="number"
                                    name="rent"
                                    class="form-control"
                                    min="1"
                                    placeholder="25000"
                                    required
                                >

                            </div>

                        </div>


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
                                    value="0"
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
                                    value="0"
                                >

                            </div>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="5"
                                placeholder="Describe the property..."
                            ></textarea>

                        </div>


                        <div class="mb-4">

                            <label class="form-label">
                                Amenities
                            </label>

                            <textarea
                                name="amenities"
                                class="form-control"
                                rows="3"
                                placeholder="Parking, Wi-Fi, Water, Security, Balcony..."
                            ></textarea>

                        </div>


                        <hr>


                        <!-- Location -->

                        <h5 class="mb-3">
                            Location
                        </h5>


                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    County *
                                </label>

                                <input
                                    type="text"
                                    name="county"
                                    class="form-control"
                                    placeholder="Nairobi"
                                    required
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Town *
                                </label>

                                <input
                                    type="text"
                                    name="town"
                                    class="form-control"
                                    placeholder="Nairobi"
                                    required
                                >

                            </div>

                        </div>


                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Area
                                </label>

                                <input
                                    type="text"
                                    name="area"
                                    class="form-control"
                                    placeholder="Kilimani"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Address
                                </label>

                                <input
                                    type="text"
                                    name="address"
                                    class="form-control"
                                    placeholder="Street / Building"
                                >

                            </div>

                        </div>


                        <hr>


                        <!-- Images -->

                        <h5 class="mb-3">
                            Property Photos
                        </h5>


                        <div class="mb-4">

                            <label class="form-label">
                                Upload Photos
                            </label>

                            <input
                                type="file"
                                name="images[]"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                            >

                            <div class="form-text">

                                JPG, PNG or WEBP.
                                Maximum 5MB per image.

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary btn-lg"
                        >

                            Submit Property

                        </button>


                        <a
                            href="dashboard.php"
                            class="btn btn-secondary btn-lg ms-2"
                        >

                            Cancel

                        </a>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>