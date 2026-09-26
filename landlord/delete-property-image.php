<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole("landlord");

$landlord_id = currentUserId();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$image_id = isset($_POST["image_id"])
    ? (int) $_POST["image_id"]
    : 0;

if ($image_id <= 0) {
    header("Location: my-properties.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get image and confirm ownership
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pi.id,
        pi.property_id,
        pi.image_path,
        pi.is_primary
    FROM property_images pi
    INNER JOIN properties p
        ON pi.property_id = p.id
    WHERE pi.id = ?
    AND p.landlord_id = ?
    LIMIT 1
");

$stmt->execute([
    $image_id,
    $landlord_id
]);

$image = $stmt->fetch();

if (!$image) {
    header("Location: my-properties.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent deleting the only image
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM property_images
    WHERE property_id = ?
");

$countStmt->execute([
    $image["property_id"]
]);

$totalImages = (int) $countStmt->fetch()["total"];

if ($totalImages <= 1) {

    header(
        "Location: edit-property.php?id="
        . $image["property_id"]
        . "&error=last_image"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Delete database record
|--------------------------------------------------------------------------
*/

$deleteStmt = $pdo->prepare("
    DELETE FROM property_images
    WHERE id = ?
");

$deleteStmt->execute([
    $image_id
]);

/*
|--------------------------------------------------------------------------
| Delete physical image
|--------------------------------------------------------------------------
*/

$filePath = "../" . $image["image_path"];

if (file_exists($filePath)) {
    unlink($filePath);
}

/*
|--------------------------------------------------------------------------
| If primary image was deleted,
| make another image primary
|--------------------------------------------------------------------------
*/

if ((int) $image["is_primary"] === 1) {

    $newPrimaryStmt = $pdo->prepare("
        SELECT id
        FROM property_images
        WHERE property_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");

    $newPrimaryStmt->execute([
        $image["property_id"]
    ]);

    $newPrimary = $newPrimaryStmt->fetch();

    if ($newPrimary) {

        $updatePrimaryStmt = $pdo->prepare("
            UPDATE property_images
            SET is_primary = 1
            WHERE id = ?
        ");

        $updatePrimaryStmt->execute([
            $newPrimary["id"]
        ]);
    }
}

header(
    "Location: edit-property.php?id="
    . $image["property_id"]
    . "&success=image_deleted"
);

exit;