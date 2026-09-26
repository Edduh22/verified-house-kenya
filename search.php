<?php

require_once "includes/db.php";
require_once "includes/auth.php";

$county = trim($_GET["county"] ?? "");
$town = trim($_GET["town"] ?? "");
$area = trim($_GET["area"] ?? "");
$propertyType = trim($_GET["property_type"] ?? "");
$maxRent = trim($_GET["max_rent"] ?? "");
$minBedrooms = trim($_GET["min_bedrooms"] ?? "");

$where = [
    "p.verification_status = 'verified'",
    "p.status = 'available'"
];

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH FILTERS
|--------------------------------------------------------------------------
*/

if ($county !== "") {

    $where[] = "p.county = ?";
    $params[] = $county;
}

if ($town !== "") {

    $where[] = "p.town = ?";
    $params[] = $town;
}

if ($area !== "") {

    $where[] = "p.area LIKE ?";
    $params[] = "%" . $area . "%";
}

if ($propertyType !== "") {

    $where[] = "p.property_type = ?";
    $params[] = $propertyType;
}

if ($maxRent !== "" && is_numeric($maxRent)) {

    $where[] = "p.rent <= ?";
    $params[] = $maxRent;
}

if ($minBedrooms !== "" && is_numeric($minBedrooms)) {

    $where[] = "p.bedrooms >= ?";
    $params[] = $minBedrooms;
}


/*
|--------------------------------------------------------------------------
| GET PROPERTIES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
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

    FROM properties p

    WHERE " . implode(" AND ", $where) . "

    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$properties = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| GET COUNT
|--------------------------------------------------------------------------
*/

$resultCount = count($properties);


/*
|--------------------------------------------------------------------------
| GET COUNTIES
|--------------------------------------------------------------------------
*/

$countiesStmt = $pdo->query("
    SELECT DISTINCT county
    FROM properties
    WHERE county IS NOT NULL
    AND county != ''
    ORDER BY county ASC
");

$counties = $countiesStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| GET PROPERTY TYPES
|--------------------------------------------------------------------------
*/

$typesStmt = $pdo->query("
    SELECT DISTINCT property_type
    FROM properties
    WHERE property_type IS NOT NULL
    AND property_type != ''
    ORDER BY property_type ASC
");

$propertyTypes = $typesStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Search Houses - Verified House Kenya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
        }

        .navbar-custom {
            background: #09234d;
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white;
        }

        .navbar-custom .nav-link:hover {
            color: #dce6f7;
        }

        .page-header {
            background: #09234d;
            color: white;
            padding: 45px 20px;
            text-align: center;
        }

        .page-header h1 {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .search-container {
            max-width: 1150px;
            margin: -25px auto 35px;
            padding: 0 20px;
        }

        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .filter-title {
            color: #09234d;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .results-container {
            max-width: 1150px;
            margin: auto;
            padding: 0 20px 50px;
        }

        .results-header {
            background: white;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .results-header h4 {
            color: #09234d;
            margin: 0;
        }

        .property-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 2px 9px rgba(0,0,0,0.07);
            transition: 0.2s;
        }

        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 16px rgba(0,0,0,0.12);
        }

        .property-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .no-image {
            width: 100%;
            height: 220px;
            background: #e9eef5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 16px;
        }

        .property-content {
            padding: 20px;
        }

        .property-title {
            color: #09234d;
            font-weight: bold;
            font-size: 19px;
            margin-bottom: 8px;
        }

        .property-location {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .property-rent {
            color: #1769d1;
            font-size: 20px;
            font-weight: bold;
        }

        .property-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-top: 12px;
        }

        .property-features {
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 12px;
            color: #555;
            font-size: 14px;
        }

        .verified-badge {
            background: #198754;
            color: white;
            padding: 5px 9px;
            border-radius: 15px;
            font-size: 11px;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state h4 {
            color: #09234d;
        }

        @media(max-width: 768px) {

            .page-header {
                padding: 35px 15px;
            }

            .property-image,
            .no-image {
                height: 200px;
            }

        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-custom">

    <div class="container">

        <a
            class="navbar-brand fw-bold"
            href="index.php"
        >
            Verified House Kenya
        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarMenu"
        >

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >
                        Home
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="search.php"
                    >
                        Search Houses
                    </a>

                </li>


                <?php if (isLoggedIn()): ?>

                    <li class="nav-item">

                        <?php if (currentUserRole() === "tenant"): ?>

                            <a
                                class="nav-link"
                                href="tenant/dashboard.php"
                            >
                                Dashboard
                            </a>

                        <?php elseif (currentUserRole() === "landlord"): ?>

                            <a
                                class="nav-link"
                                href="landlord/dashboard.php"
                            >
                                Dashboard
                            </a>

                        <?php elseif (currentUserRole() === "admin"): ?>

                            <a
                                class="nav-link"
                                href="admin/dashboard.php"
                            >
                                Dashboard
                            </a>

                        <?php endif; ?>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="notifications.php"
                        >
                            Notifications
                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="logout.php"
                        >
                            Logout
                        </a>

                    </li>

                <?php else: ?>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="login.php"
                        >
                            Login
                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="register.php"
                        >
                            Register
                        </a>

                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>


<!-- HEADER -->

<div class="page-header">

    <h1>
        Find Your Next Home
    </h1>

    <p class="mb-0">
        Search verified and available properties across Kenya.
    </p>

</div>


<!-- FILTERS -->

<div class="search-container">

    <div class="filter-card">

        <h4 class="filter-title">
            Search & Filter Houses
        </h4>

        <form method="GET">

            <div class="row g-3">


                <!-- COUNTY -->

                <div class="col-md-4">

                    <label class="form-label">
                        County
                    </label>

                    <select
                        name="county"
                        class="form-select"
                    >

                        <option value="">
                            All Counties
                        </option>

                        <?php foreach ($counties as $countyRow): ?>

                            <option
                                value="<?= htmlspecialchars($countyRow["county"]) ?>"
                                <?= $county === $countyRow["county"] ? "selected" : "" ?>
                            >

                                <?= htmlspecialchars($countyRow["county"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- TOWN -->

                <div class="col-md-4">

                    <label class="form-label">
                        Town
                    </label>

                    <input
                        type="text"
                        name="town"
                        class="form-control"
                        placeholder="e.g. Nairobi"
                        value="<?= htmlspecialchars($town) ?>"
                    >

                </div>


                <!-- AREA -->

                <div class="col-md-4">

                    <label class="form-label">
                        Area / Estate
                    </label>

                    <input
                        type="text"
                        name="area"
                        class="form-control"
                        placeholder="e.g. Kilimani"
                        value="<?= htmlspecialchars($area) ?>"
                    >

                </div>


                <!-- PROPERTY TYPE -->

                <div class="col-md-4">

                    <label class="form-label">
                        Property Type
                    </label>

                    <select
                        name="property_type"
                        class="form-select"
                    >

                        <option value="">
                            All Types
                        </option>

                        <?php foreach ($propertyTypes as $typeRow): ?>

                            <option
                                value="<?= htmlspecialchars($typeRow["property_type"]) ?>"
                                <?= $propertyType === $typeRow["property_type"] ? "selected" : "" ?>
                            >

                                <?= htmlspecialchars($typeRow["property_type"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- MAX RENT -->

                <div class="col-md-4">

                    <label class="form-label">
                        Maximum Rent (KSh)
                    </label>

                    <input
                        type="number"
                        name="max_rent"
                        class="form-control"
                        placeholder="e.g. 30000"
                        min="0"
                        value="<?= htmlspecialchars($maxRent) ?>"
                    >

                </div>


                <!-- BEDROOMS -->

                <div class="col-md-4">

                    <label class="form-label">
                        Minimum Bedrooms
                    </label>

                    <select
                        name="min_bedrooms"
                        class="form-select"
                    >

                        <option value="">
                            Any
                        </option>

                        <option
                            value="1"
                            <?= $minBedrooms === "1" ? "selected" : "" ?>
                        >
                            1+
                        </option>

                        <option
                            value="2"
                            <?= $minBedrooms === "2" ? "selected" : "" ?>
                        >
                            2+
                        </option>

                        <option
                            value="3"
                            <?= $minBedrooms === "3" ? "selected" : "" ?>
                        >
                            3+
                        </option>

                        <option
                            value="4"
                            <?= $minBedrooms === "4" ? "selected" : "" ?>
                        >
                            4+
                        </option>

                        <option
                            value="5"
                            <?= $minBedrooms === "5" ? "selected" : "" ?>
                        >
                            5+
                        </option>

                    </select>

                </div>


                <!-- BUTTONS -->

                <div class="col-12">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Search Houses
                    </button>


                    <a
                        href="search.php"
                        class="btn btn-outline-secondary"
                    >
                        Clear Filters
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- RESULTS -->

<div class="results-container">


    <div class="results-header">

        <h4>

            <?= $resultCount ?>

            <?= $resultCount === 1 ? "Property" : "Properties" ?>

            Found

        </h4>

    </div>


    <?php if ($resultCount > 0): ?>

        <div class="row g-4">


            <?php foreach ($properties as $property): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="property-card">


                        <!-- IMAGE -->

                        <?php if (!empty($property["image_path"])): ?>

                            <img
                                src="<?= htmlspecialchars($property["image_path"]) ?>"
                                alt="<?= htmlspecialchars($property["title"]) ?>"
                                class="property-image"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No Image Available
                            </div>

                        <?php endif; ?>


                        <!-- CONTENT -->

                        <div class="property-content">


                            <div class="d-flex justify-content-between align-items-start gap-2">

                                <div class="property-title">

                                    <?= htmlspecialchars($property["title"]) ?>

                                </div>

                                <span class="verified-badge">
                                    ✓ VERIFIED
                                </span>

                            </div>


                            <div class="property-location">

                                📍
                                <?= htmlspecialchars($property["town"]) ?>

                                <?php if (!empty($property["area"])): ?>

                                    ,
                                    <?= htmlspecialchars($property["area"]) ?>

                                <?php endif; ?>

                            </div>


                            <div class="property-rent">

                                KSh
                                <?= number_format($property["rent"], 0) ?>

                                <small class="text-muted fs-6">
                                    / month
                                </small>

                            </div>


                            <div class="property-description">

                                <?php

                                $description = $property["description"] ?? "";

                                if (strlen($description) > 110) {

                                    $description =
                                        substr($description, 0, 110) . "...";
                                }

                                ?>

                                <?= htmlspecialchars($description) ?>

                            </div>


                            <div class="property-features">

                                <span class="me-3">

                                    🏠
                                    <?= htmlspecialchars($property["property_type"]) ?>

                                </span>


                                <span class="me-3">

                                    🛏️
                                    <?= htmlspecialchars($property["bedrooms"]) ?>

                                    Beds

                                </span>


                                <span>

                                    🚿
                                    <?= htmlspecialchars($property["bathrooms"]) ?>

                                    Baths

                                </span>

                            </div>


                            <div class="mt-3">

                                <a
                                    href="property.php?id=<?= (int)$property["id"] ?>"
                                    class="btn btn-primary w-100"
                                >
                                    View Property
                                </a>

                            </div>


                        </div>

                    </div>

                </div>

            <?php endforeach; ?>


        </div>

    <?php else: ?>


        <div class="empty-state">

            <h4>
                No Properties Found
            </h4>

            <p class="text-muted">
                No verified and available properties match your search criteria.
            </p>

            <a
                href="search.php"
                class="btn btn-primary"
            >
                View All Houses
            </a>

        </div>


    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>