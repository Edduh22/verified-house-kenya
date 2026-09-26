<?php

require_once "includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                password,
                role,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {

            $error = "Invalid email or password.";

        } elseif (!password_verify($password, $user["password"])) {

            $error = "Invalid email or password.";

        } elseif ($user["status"] !== "active") {

            $error = "Your account is currently inactive.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Login successful
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            /*
            |--------------------------------------------------------------------------
            | Redirect according to role
            |--------------------------------------------------------------------------
            */

            if ($user["role"] === "tenant") {

                header("Location: tenant/dashboard.php");
                exit;

            } elseif ($user["role"] === "landlord") {

                header("Location: landlord/dashboard.php");
                exit;

            } elseif ($user["role"] === "admin") {

                header("Location: admin/dashboard.php");
                exit;

            } else {

                $error = "Invalid account role.";

            }
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

    <title>Login - Verified House Kenya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center mt-5">

        <div class="col-md-5">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="text-center mb-4">
                        Verified House Kenya
                    </h2>

                    <h5 class="text-center mb-4">
                        Login
                    </h5>


                    <?php if ($error): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >

                            Login

                        </button>

                    </form>


                    <div class="text-center mt-3">

                        Don't have an account?

                        <a href="register.php">
                            Create Account
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>