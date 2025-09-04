<?php

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function login($username, $password, $db)
{
    $stmt = $db->prepare("SELECT id, username, password FROM usuarios WHERE username = :username");
    $stmt->bindValue(':username', $username);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function logout()
{
    session_unset();
    session_destroy();
}

function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}
