<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";


/*
|--------------------------------------------------------------------------
| Require Tenant Login
|--------------------------------------------------------------------------
*/

requireRole("tenant");

$tenant_id = currentUserId();


/*
|--------------------------------------------------------------------------
| Make Sure Request Is POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../search.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get Property ID
|--------------------------------------------------------------------------
*/

$property_id = isset($_POST["property_id"])
    ? (int) $_POST["property_id"]
    : 0;


if ($property_id <= 0) {

    header("Location: ../search.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Check Property
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM properties
    WHERE id = ?
    AND verification_status = 'verified'
    AND status = 'available'
    LIMIT 1
");

$stmt->execute([
    $property_id
]);

$property = $stmt->fetch();


if (!$property) {

    header("Location: ../search.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Check Existing Favourite
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM favourites
    WHERE tenant_id = ?
    AND property_id = ?
    LIMIT 1
");

$stmt->execute([
    $tenant_id,
    $property_id
]);

$favourite = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Remove Favourite
|--------------------------------------------------------------------------
*/

if ($favourite) {

    $deleteStmt = $pdo->prepare("
        DELETE FROM favourites
        WHERE tenant_id = ?
        AND property_id = ?
    ");

    $deleteStmt->execute([
        $tenant_id,
        $property_id
    ]);

}


/*
|--------------------------------------------------------------------------
| Add Favourite
|--------------------------------------------------------------------------
*/

else {

    $insertStmt = $pdo->prepare("
        INSERT INTO favourites
        (
            tenant_id,
            property_id
        )
        VALUES
        (
            ?,
            ?
        )
    ");

    $insertStmt->execute([
        $tenant_id,
        $property_id
    ]);

}


/*
|--------------------------------------------------------------------------
| Return To Previous Page
|--------------------------------------------------------------------------
*/

if (!empty($_SERVER["HTTP_REFERER"])) {

    header("Location: " . $_SERVER["HTTP_REFERER"]);

} else {

    header("Location: ../search.php");

}

exit;

