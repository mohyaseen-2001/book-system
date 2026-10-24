<?php
session_start();
require_once "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "user"
) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$reservationRepository = new ReservationRepository($pdo);
$reservations = $reservationRepository->getByUserId($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations | CoworkSpace</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">
        <a href="index.php" class="logo">CoworkSpace</a>

        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="spaces.php">Spaces</a>
            <a href="my-reservations.php">My Reservations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="section">
    <div class="container">
        <h1 class="section-title">My Reservations</h1>

        <?php if (empty($reservations)): ?>
            <p>You don't have any reservations yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Space</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($reservation["space_name"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($reservation["booking_date"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($reservation["start_time"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($reservation["end_time"]) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($reservation["status"]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<footer class="footer">
    <p>&copy; 2026 CoworkSpace. All rights reserved.</p>
</footer>

</body>
</html>