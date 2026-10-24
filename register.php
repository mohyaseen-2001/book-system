<?php
require_once "db.php";

$userRepository = new UserRepository($pdo);

$error = "";
$success = "";
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "") {
        $error = "Please enter your name.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (
        preg_match(
            "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/",
            $password
        ) !== 1
    ) {
        $error = "Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a symbol.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif ($userRepository->emailExists($email)) {
        $error = "This email is already registered.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userRepository->createUser(
            $name,
            $email,
            $hashedPassword
        );

        $success = "Account created successfully. You can log in now.";
        $name = "";
        $email = "";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | CoworkSpace</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">
        <a href="index.php" class="logo">CoworkSpace</a>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="spaces.php">Spaces</a>
            <a href="login.php">Login</a>
        </nav>
    </div>
</header>

<main class="form-container">
    <h1>Create Account</h1>

    <?php if ($error !== ""): ?>
        <p role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your full name"
                value="<?= htmlspecialchars($name) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                value="<?= htmlspecialchars($email) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Create a password"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm your password"
                required
            >
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
        Already have an account?
        <a href="login.php" style="color:#2563eb;">Login</a>
    </p>
</main>

<footer class="footer">
    <p>&copy; 2026 CoworkSpace. All rights reserved.</p>
</footer>

</body>
</html>