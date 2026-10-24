<?php
session_start();
require_once "db.php";

$userRepository = new UserRepository($pdo);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $user = $userRepository->findByEmail($email);

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = (int) $user["id"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: admin/index.php");
        } else {
            header("Location: index.php");
        }

        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CoworkSpace</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">
        <a href="index.php" class="logo">CoworkSpace</a>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="spaces.php">Spaces</a>
            <a href="register.php" class="btn">Sign Up</a>
        </nav>
    </div>
</header>

<main class="form-container">
    <h1>Welcome Back</h1>

    <p style="text-align:center; margin-bottom:25px;">
        Login to manage your reservations.
    </p>

    <?php if ($error !== ""): ?>
        <p role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
            >
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
        Don't have an account?
        <a href="register.php" style="color:#2563eb;">Register here</a>
    </p>
</main>

<footer class="footer">
    <p>&copy; 2026 CoworkSpace. All rights reserved.</p>
</footer>

</body>
</html>