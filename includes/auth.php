<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Check if user is logged in
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/*
|--------------------------------------------------------------------------
| Require login
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require specific role
|--------------------------------------------------------------------------
*/

function requireRole($role)
{
    requireLogin();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: ../index.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Current user ID
|--------------------------------------------------------------------------
*/

function currentUserId()
{
    return $_SESSION['user_id'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Current user role
|--------------------------------------------------------------------------
*/

function currentUserRole()
{
    return $_SESSION['role'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Current user name
|--------------------------------------------------------------------------
*/

function currentUserName()
{
    return $_SESSION['full_name'] ?? null;
}


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

function logoutUser()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}