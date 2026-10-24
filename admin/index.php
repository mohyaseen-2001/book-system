<?php
require_once "../db.php";
requireAdmin();

$reservationRepository = new ReservationRepository($pdo);
$reservationStatuses = [
    "pending" => "Pending",
    "confirmed" => "Confirmed",
    "cancelled" => "Cancelled",
];

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_reservation_status"])
) {
    $reservationId = filter_input(
        INPUT_POST,
        "reservation_id",
        FILTER_VALIDATE_INT
    );
    $status = $_POST["status"] ?? "";

    if (
        $reservationId &&
        $reservationId > 0 &&
        array_key_exists($status, $reservationStatuses)
    ) {
        $reservationRepository->updateStatus($reservationId, $status);
    }

    header("Location: index.php#reservations");
    exit;
}

$reservations = $reservationRepository->getAllWithDetails();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CoworkSpace</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">
        <a href="index.php" class="logo">CoworkSpace Admin</a>
        <nav class="nav-links">
            <a href="../index.php">View Website</a>
            <a href="spaces.php">Manage Spaces</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="section">
    <div class="container">
        <h1 class="section-title">Admin Dashboard</h1>

        <div class="cards">
            <div class="card">
                <h3>Manage Spaces</h3>
                <p>Add, edit, view, and delete coworking spaces.</p>
                <a href="spaces.php" class="btn">Manage Spaces</a>
            </div>

            <div class="card">
                <h3>Reservations</h3>
                <p>Review reservations and update their statuses.</p>
                <a href="#reservations" class="btn">View Reservations</a>
            </div>

            <div class="card">
                <h3>Users</h3>
                <p>User management will be connected later.</p>
                <button class="btn" type="button" disabled>Coming Soon</button>
            </div>
        </div>

        <section id="reservations" class="section">
            <h2 class="section-title">Manage Reservations</h2>

            <?php if (empty($reservations)): ?>
                <p>There are no reservations yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Space</th>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation["user_name"]) ?></td>
                                <td><?= htmlspecialchars($reservation["user_email"]) ?></td>
                                <td><?= htmlspecialchars($reservation["space_name"]) ?></td>
                                <td><?= htmlspecialchars($reservation["booking_date"]) ?></td>
                                <td><?= htmlspecialchars($reservation["start_time"]) ?></td>
                                <td><?= htmlspecialchars($reservation["end_time"]) ?></td>
                                <td><?= htmlspecialchars($reservation["status"]) ?></td>
                                <td>
                                    <form method="POST" action="index.php#reservations">
                                        <input
                                            type="hidden"
                                            name="reservation_id"
                                            value="<?= (int) $reservation["id"] ?>"
                                        >

                                        <select name="status" required>
                                            <?php foreach ($reservationStatuses as $value => $label): ?>
                                                <option
                                                    value="<?= htmlspecialchars($value) ?>"
                                                    <?= $reservation["status"] === $value ? "selected" : "" ?>
                                                >
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button
                                            type="submit"
                                            name="update_reservation_status"
                                            class="btn"
                                        >Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</main>

<footer class="footer">
    <p>&copy; 2026 CoworkSpace Admin</p>
</footer>

</body>
</html>