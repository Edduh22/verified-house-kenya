<?php

require_once "includes/db.php";
require_once "includes/auth.php";

/*
|--------------------------------------------------------------------------
| Get verified properties for homepage
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
        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS image_path
    FROM properties p
    WHERE p.verification_status = 'verified'
    AND p.status = 'available'
    ORDER BY p.created_at DESC
    LIMIT 6
");

$featuredProperties = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get available counties
|--------------------------------------------------------------------------
*/

$countyStmt = $pdo->query("
    SELECT DISTINCT county
    FROM properties
    WHERE verification_status = 'verified'
    AND status = 'available'
    AND county IS NOT NULL
    AND county != ''
    ORDER BY county ASC
");

$counties = $countyStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Login information
|--------------------------------------------------------------------------
*/

$isLoggedIn = isLoggedIn();
$userRole = currentUserRole();

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
        Verified House Kenya | Find a House You Can Trust
    </title>

    <meta
        name="description"
        content="Find verified houses, apartments, bedsitters, studios and other properties across Kenya."
    >

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           GENERAL
        ===================================================== */

        body {
            background: #f7fcf7;
            color: #172033;
            font-family: Arial, sans-serif;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            background: #09234d;
            padding: 15px 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 22px;
        }

        .navbar-brand span {
            color: #4da3ff;
        }

        .navbar .nav-link {
            color: #dce8f7 !important;
            margin-left: 12px;
            font-weight: 500;
        }

        .navbar .nav-link:hover {
            color: white !important;
        }


        /* LOGIN BUTTON */

        .btn-login {
            border: 1px solid #4da3ff;
            color: white;
            background: transparent;
        }

        .btn-login:hover {
            background: #4da3ff;
            color: white;
        }


        /* REGISTER BUTTON */

        .btn-register {
            background: #1769d1;
            color: white;
            border: none;
        }

        .btn-register:hover {
            background: #0f56ad;
            color: white;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {

            background:

                linear-gradient(
                    rgba(9, 35, 77, 0.52),
                    rgba(9, 35, 77, 0.52)
                ),

                url("assets/images/house-bg.jpg");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            padding: 85px 0 110px;

            color: white;
        }


        .hero-content {
            max-width: 850px;
            margin: auto;
            text-align: center;
        }


        .hero h1 {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 18px;
        }


        .hero h1 span {
            color: #4da3ff;
        }


        .hero p {
            font-size: 19px;
            color: #e3edf9;
            max-width: 700px;
            margin: 0 auto 35px;
            line-height: 1.7;
        }


        /* =====================================================
           SEARCH BOX
        ===================================================== */

        .search-box {

            background: white;

            border-radius: 14px;

            padding: 20px;

            max-width: 1050px;

            margin: 0 auto;

            box-shadow:
                0 15px 40px rgba(0, 0, 0, 0.18);
        }


        .search-box label {

            color: #45556e;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 6px;
        }


        .search-box .form-control,
        .search-box .form-select {

            height: 48px;

            border: 1px solid #dbe3ee;
        }


        .search-btn {

            height: 48px;

            background: #1769d1;

            color: white;

            border: none;

            font-weight: 600;
        }


        .search-btn:hover {

            background: #0f56ad;

            color: white;
        }


        /* =====================================================
           SECTIONS
        ===================================================== */

        .section {
            padding: 70px 0;
        }


        .section-title {

            text-align: center;

            margin-bottom: 45px;
        }


        .section-title h2 {

            font-size: 32px;

            font-weight: 750;

            margin-bottom: 10px;
        }


        .section-title p {

            color: #68778c;

            max-width: 650px;

            margin: auto;
        }


        /* =====================================================
           TRUST CARDS
        ===================================================== */

        .trust-card {

            background: white;

            border: 1px solid #e7edf5;

            border-radius: 12px;

            padding: 30px 20px;

            height: 100%;

            text-align: center;

            transition: 0.2s;
        }


        .trust-card:hover {

            transform: translateY(-4px);

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.07);
        }


        .trust-icon {

            width: 60px;

            height: 60px;

            border-radius: 50%;

            background: #eaf3ff;

            color: #1769d1;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 18px;

            font-size: 25px;
        }


        .trust-card h5 {

            font-weight: 700;
        }


        .trust-card p {

            color: #718096;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =====================================================
           PROPERTY CARDS
        ===================================================== */

        .property-card {

            background: white;

            border: 1px solid #e5ebf2;

            border-radius: 12px;

            overflow: hidden;

            height: 100%;

            transition: 0.2s;
        }


        .property-card:hover {

            transform: translateY(-4px);

            box-shadow:
                0 12px 28px rgba(0, 0, 0, 0.08);
        }


        .property-image {

            height: 210px;

            width: 100%;

            object-fit: cover;

            background: #e9eef5;
        }


        .property-body {

            padding: 20px;
        }


        .property-title {

            font-size: 18px;

            font-weight: 700;

            margin-bottom: 8px;
        }


        .property-location {

            color: #68778c;

            font-size: 14px;

            margin-bottom: 15px;
        }


        .property-rent {

            font-size: 21px;

            font-weight: 750;

            color: #1769d1;
        }


        .property-rent small {

            font-size: 13px;

            color: #7b8798;

            font-weight: normal;
        }


        .property-details {

            border-top: 1px solid #edf1f5;

            margin-top: 15px;

            padding-top: 14px;

            color: #66758a;

            font-size: 13px;
        }


        .verified-badge {

            background: #e8f7ee;

            color: #16834a;

            font-size: 12px;

            padding: 5px 9px;

            border-radius: 20px;

            font-weight: 600;

            white-space: nowrap;
        }


        /* =====================================================
           HOW IT WORKS
        ===================================================== */

        .step {

            text-align: center;

            padding: 20px;
        }


        .step-number {

            width: 55px;

            height: 55px;

            background: #1769d1;

            color: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 18px;

            font-size: 20px;

            font-weight: 700;
        }


        .step h5 {

            font-weight: 700;
        }


        .step p {

            color: #718096;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =====================================================
           LANDLORD SECTION
        ===================================================== */

        .landlord-section {

            background: #09234d;

            color: white;

            border-radius: 18px;

            padding: 55px;
        }


        .landlord-section p {

            color: #d9e5f4;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            background: #071a39;

            color: #c8d5e5;

            padding: 45px 0 25px;
        }


        footer h5 {

            color: white;

            font-weight: 700;

            margin-bottom: 15px;
        }


        footer a {

            color: #c8d5e5;

            text-decoration: none;

            display: block;

            margin-bottom: 8px;

            font-size: 14px;
        }


        footer a:hover {

            color: white;
        }


        .footer-bottom {

            border-top:
                1px solid rgba(255, 255, 255, 0.1);

            margin-top: 30px;

            padding-top: 20px;

            font-size: 13px;

            text-align: center;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .hero {

                padding:
                    60px 15px 80px;
            }


            .hero h1 {

                font-size: 36px;
            }


            .hero p {

                font-size: 16px;
            }


            .search-box {

                padding: 15px;
            }


            .landlord-section {

                padding: 35px 25px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg">

    <div class="container">

        <!-- BRAND -->

        <a
            class="navbar-brand"
            href="index.php"
        >
            Verified House <span>Kenya</span>
        </a>


        <!-- MOBILE MENU BUTTON -->

        <button
            class="navbar-toggler bg-light"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">


                <!-- HOME -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php"
                    >
                        Home
                    </a>

                </li>


                <!-- FIND HOUSES -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="search.php"
                    >
                        Find Houses
                    </a>

                </li>


                <!-- HOW IT WORKS -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#how-it-works"
                    >
                        How It Works
                    </a>

                </li>


                <!-- CONTACT -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="contact.php"
                    >
                        Contact
                    </a>

                </li>


                <?php if ($isLoggedIn): ?>

                    <?php

                    if ($userRole === "tenant") {

                        $dashboard =
                            "tenant/dashboard.php";

                    } elseif ($userRole === "landlord") {

                        $dashboard =
                            "landlord/dashboard.php";

                    } else {

                        $dashboard =
                            "admin/dashboard.php";
                    }

                    ?>


                    <!-- DASHBOARD -->

                    <li class="nav-item ms-lg-3">

                        <a
                            href="<?= $dashboard ?>"
                            class="btn btn-login btn-sm px-4"
                        >

                            <i class="bi bi-speedometer2 me-1"></i>

                            Dashboard

                        </a>

                    </li>


                    <!-- LOGOUT -->

                    <li class="nav-item ms-lg-2">

                        <a
                            href="logout.php"
                            class="btn btn-register btn-sm px-4"
                        >

                            <i class="bi bi-box-arrow-right me-1"></i>

                            Logout

                        </a>

                    </li>


                <?php else: ?>


                    <!-- LOGIN -->

                    <li class="nav-item ms-lg-3">

                        <a
                            href="login.php"
                            class="btn btn-login btn-sm px-4"
                        >

                            <i class="bi bi-box-arrow-in-right me-1"></i>

                            Login

                        </a>

                    </li>


                    <!-- REGISTER -->

                    <li class="nav-item ms-lg-2">

                        <a
                            href="register.php"
                            class="btn btn-register btn-sm px-4"
                        >

                            <i class="bi bi-person-plus me-1"></i>

                            Register

                        </a>

                    </li>


                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">

    <div class="container">

        <div class="hero-content">

            <h1>

                Find a House

                <span>
                    You Can Trust
                </span>

            </h1>


            <p>

                Search verified properties across Kenya and connect
                with landlords through a simple, secure and transparent
                house-hunting platform.

            </p>

        </div>


        <!-- SEARCH BOX -->

        <div class="search-box mt-4">

            <form
                action="search.php"
                method="GET"
            >

                <div class="row g-3 align-items-end">


                    <!-- COUNTY -->

                    <div class="col-lg-3 col-md-6">

                        <label>
                            County
                        </label>

                        <select
                            name="county"
                            class="form-select"
                        >

                            <option value="">
                                All Counties
                            </option>


                            <?php foreach ($counties as $county): ?>

                                <option
                                    value="<?= htmlspecialchars($county['county']) ?>"
                                >

                                    <?= htmlspecialchars($county['county']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- TOWN -->

                    <div class="col-lg-3 col-md-6">

                        <label>
                            Town
                        </label>

                        <input
                            type="text"
                            name="town"
                            class="form-control"
                            placeholder="e.g. Nairobi"
                        >

                    </div>


                    <!-- PROPERTY TYPE -->

                    <div class="col-lg-3 col-md-6">

                        <label>
                            Property Type
                        </label>

                        <select
                            name="property_type"
                            class="form-select"
                        >

                            <option value="">
                                Any Type
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

                        </select>

                    </div>


                    <!-- SEARCH BUTTON -->

                    <div class="col-lg-3 col-md-6">

                        <button
                            type="submit"
                            class="btn search-btn w-100"
                        >

                            <i class="bi bi-search me-2"></i>

                            Search Houses

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</section>



<!-- =========================================================
     TRUST FEATURES
========================================================= -->

<section class="section">

    <div class="container">


        <div class="section-title">

            <h2>
                House Hunting Made Simpler
            </h2>

            <p>

                Verified House Kenya helps tenants discover properties
                while giving landlords a trusted platform to manage
                their listings.

            </p>

        </div>


        <div class="row g-4">


            <!-- VERIFIED -->

            <div class="col-lg-3 col-md-6">

                <div class="trust-card">

                    <div class="trust-icon">

                        <i class="bi bi-patch-check"></i>

                    </div>

                    <h5>
                        Verified Properties
                    </h5>

                    <p>

                        Properties go through an administrative
                        verification process before appearing as verified.

                    </p>

                </div>

            </div>


            <!-- REAL LISTINGS -->

            <div class="col-lg-3 col-md-6">

                <div class="trust-card">

                    <div class="trust-icon">

                        <i class="bi bi-house-check"></i>

                    </div>

                    <h5>
                        Real Listings
                    </h5>

                    <p>

                        Browse property information including rent,
                        location, bedrooms, bathrooms and amenities.

                    </p>

                </div>

            </div>


            <!-- VIEWINGS -->

            <div class="col-lg-3 col-md-6">

                <div class="trust-card">

                    <div class="trust-icon">

                        <i class="bi bi-calendar-check"></i>

                    </div>

                    <h5>
                        Easy Viewings
                    </h5>

                    <p>

                        Tenants can request property viewings directly
                        through the platform.

                    </p>

                </div>

            </div>


            <!-- SECURITY -->

            <div class="col-lg-3 col-md-6">

                <div class="trust-card">

                    <div class="trust-icon">

                        <i class="bi bi-shield-lock"></i>

                    </div>

                    <h5>
                        Secure Accounts
                    </h5>

                    <p>

                        User accounts and passwords are protected using
                        secure authentication practices.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     FEATURED PROPERTIES
========================================================= -->

<section class="section bg-white">

    <div class="container">


        <div class="section-title">

            <h2>
                Featured Verified Properties
            </h2>

            <p>

                Explore some of the latest available properties
                listed on Verified House Kenya.

            </p>

        </div>


        <div class="row g-4">


            <?php if (count($featuredProperties) > 0): ?>


                <?php foreach ($featuredProperties as $property): ?>


                    <div class="col-lg-4 col-md-6">

                        <div class="property-card">


                            <!-- PROPERTY IMAGE -->

                            <?php if (!empty($property['image_path'])): ?>

                                <img
                                    src="<?= htmlspecialchars($property['image_path']) ?>"
                                    alt="<?= htmlspecialchars($property['title']) ?>"
                                    class="property-image"
                                >

                            <?php else: ?>

                                <div
                                    class="property-image d-flex align-items-center justify-content-center"
                                >

                                    <div
                                        class="text-center text-secondary"
                                    >

                                        <i
                                            class="bi bi-house fs-1"
                                        ></i>

                                        <div>
                                            No Image
                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>


                            <!-- PROPERTY BODY -->

                            <div class="property-body">


                                <div
                                    class="d-flex justify-content-between align-items-start gap-2"
                                >

                                    <div class="property-title">

                                        <?= htmlspecialchars(
                                            $property['title']
                                        ) ?>

                                    </div>


                                    <span class="verified-badge">

                                        <i
                                            class="bi bi-patch-check-fill"
                                        ></i>

                                        Verified

                                    </span>

                                </div>


                                <!-- LOCATION -->

                                <div class="property-location">

                                    <i
                                        class="bi bi-geo-alt me-1"
                                    ></i>

                                    <?= htmlspecialchars(
                                        $property['area']
                                    ) ?>,

                                    <?= htmlspecialchars(
                                        $property['town']
                                    ) ?>,

                                    <?= htmlspecialchars(
                                        $property['county']
                                    ) ?>

                                </div>


                                <!-- RENT -->

                                <div class="property-rent">

                                    KSh
                                    <?= number_format(
                                        $property['rent'],
                                        0
                                    ) ?>

                                    <small>
                                        / month
                                    </small>

                                </div>


                                <!-- DETAILS -->

                                <div class="property-details">


                                    <span class="me-3">

                                        <i
                                            class="bi bi-house me-1"
                                        ></i>

                                        <?= htmlspecialchars(
                                            $property['property_type']
                                        ) ?>

                                    </span>


                                    <span class="me-3">

                                        <i
                                            class="bi bi-door-open me-1"
                                        ></i>

                                        <?= (int)$property['bedrooms'] ?>
                                        Beds

                                    </span>


                                    <span>

                                        <i
                                            class="bi bi-droplet me-1"
                                        ></i>

                                        <?= (int)$property['bathrooms'] ?>
                                        Baths

                                    </span>

                                </div>


                                <!-- VIEW BUTTON -->

                                <a
                                    href="property.php?id=<?= (int)$property['id'] ?>"
                                    class="btn btn-outline-primary w-100 mt-3"
                                >

                                    View Property

                                </a>

                            </div>

                        </div>

                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- NO PROPERTIES -->

                <div class="col-12">

                    <div class="text-center py-5">

                        <i
                            class="bi bi-house-x fs-1 text-secondary"
                        ></i>

                        <h5 class="mt-3">

                            No verified properties yet

                        </h5>

                        <p class="text-secondary">

                            Verified properties will appear here once
                            landlords add them and they are approved.

                        </p>

                        <a
                            href="search.php"
                            class="btn btn-primary"
                        >

                            Browse Houses

                        </a>

                    </div>

                </div>


            <?php endif; ?>

        </div>


        <?php if (count($featuredProperties) > 0): ?>

            <div class="text-center mt-5">

                <a
                    href="search.php"
                    class="btn btn-primary px-4"
                >

                    View All Houses

                    <i
                        class="bi bi-arrow-right ms-2"
                    ></i>

                </a>

            </div>

        <?php endif; ?>


    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section
    class="section"
    id="how-it-works"
>

    <div class="container">


        <div class="section-title">

            <h2>
                How It Works
            </h2>

            <p>
                Finding your next home can be simple.
            </p>

        </div>


        <div class="row g-4">


            <!-- STEP 1 -->

            <div class="col-lg-3 col-md-6">

                <div class="step">

                    <div class="step-number">
                        1
                    </div>

                    <h5>
                        Search
                    </h5>

                    <p>

                        Search houses by county, town, property type
                        and other available criteria.

                    </p>

                </div>

            </div>


            <!-- STEP 2 -->

            <div class="col-lg-3 col-md-6">

                <div class="step">

                    <div class="step-number">
                        2
                    </div>

                    <h5>
                        View Property
                    </h5>

                    <p>

                        Check the property's rent, location, features,
                        images and verification status.

                    </p>

                </div>

            </div>


            <!-- STEP 3 -->

            <div class="col-lg-3 col-md-6">

                <div class="step">

                    <div class="step-number">
                        3
                    </div>

                    <h5>
                        Request Viewing
                    </h5>

                    <p>

                        Choose a preferred date and time to request
                        a viewing from the landlord.

                    </p>

                </div>

            </div>


            <!-- STEP 4 -->

            <div class="col-lg-3 col-md-6">

                <div class="step">

                    <div class="step-number">
                        4
                    </div>

                    <h5>
                        Find Your Home
                    </h5>

                    <p>

                        Communicate with the landlord and proceed with
                        your house-hunting journey.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     LANDLORD CTA
========================================================= -->

<section class="section">

    <div class="container">

        <div class="landlord-section">

            <div class="row align-items-center">


                <div class="col-lg-8">

                    <h2 class="fw-bold mb-3">

                        Are You a Landlord?

                    </h2>

                    <p class="mb-lg-0">

                        List your property on Verified House Kenya,
                        manage your listings and receive viewing
                        requests from interested tenants.

                    </p>

                </div>


                <div
                    class="col-lg-4 text-lg-end mt-4 mt-lg-0"
                >


                    <?php if (
                        $isLoggedIn &&
                        $userRole === "landlord"
                    ): ?>


                        <a
                            href="landlord/add-property.php"
                            class="btn btn-light px-4"
                        >

                            Add Your Property

                        </a>


                    <?php else: ?>


                        <a
                            href="register.php"
                            class="btn btn-light px-4"
                        >

                            Register as Landlord

                        </a>


                    <?php endif; ?>


                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="container">

        <div class="row g-4">


            <!-- ABOUT -->

            <div class="col-lg-5">

                <h5>
                    Verified House Kenya
                </h5>

                <p class="small">

                    A property platform designed to make house hunting
                    simpler by connecting tenants with verified property
                    listings.

                </p>

            </div>


            <!-- EXPLORE -->

            <div class="col-lg-2 col-md-4">

                <h5>
                    Explore
                </h5>

                <a href="index.php">
                    Home
                </a>

                <a href="search.php">
                    Find Houses
                </a>

                <a href="#how-it-works">
                    How It Works
                </a>

            </div>


            <!-- ACCOUNT -->

            <div class="col-lg-2 col-md-4">

                <h5>
                    Account
                </h5>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Register
                </a>

            </div>


            <!-- CONTACT -->

            <div class="col-lg-3 col-md-4">

                <h5>
                    Contact
                </h5>

                <a href="contact.php">
                    Contact Us
                </a>

                <a href="mailto:info@verifiedhouse.co.ke">
                    info@verifiedhouse.co.ke
                </a>

            </div>

        </div>


        <div class="footer-bottom">

            © <?= date("Y") ?>
            Verified House Kenya.
            All rights reserved.

        </div>

    </div>

</footer>



<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>