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

$spaceId = filter_input(INPUT_GET, "space_id", FILTER_VALIDATE_INT);

if (!$spaceId || $spaceId < 1) {
    die("Invalid space ID.");
}

$spaceRepository = new SpaceRepository($pdo);
$space = $spaceRepository->findById($spaceId);

if (!$space) {
    die("Space not found.");
}

$reservationRepository = new ReservationRepository($pdo);

$error = "";
$bookingDate = "";
$startTime = "";
$endTime = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedSpaceId = filter_input(
        INPUT_POST,
        "space_id",
        FILTER_VALIDATE_INT
    );

    $bookingDate = trim($_POST["booking_date"] ?? "");
    $startTime = trim($_POST["start_time"] ?? "");
    $endTime = trim($_POST["end_time"] ?? "");

    $date = DateTime::createFromFormat("Y-m-d", $bookingDate);

    if (!$postedSpaceId || $postedSpaceId !== $spaceId) {
        $error = "Invalid space selection.";
    } elseif (
        !$date ||
        $date->format("Y-m-d") !== $bookingDate
    ) {
        $error = "Please enter a valid booking date.";
    } elseif ($bookingDate < date("Y-m-d")) {
        $error = "The booking date cannot be in the past.";
    } elseif ($startTime === "" || $endTime === "") {
        $error = "Please enter both start and end times.";
    } elseif ($startTime >= $endTime) {
        $error = "End time must be later than start time.";
    } elseif (
        $reservationRepository->hasConflict(
            $spaceId,
            $bookingDate,
            $startTime,
            $endTime
        )
    ) {
        $error = "This space is already booked during that time.";
    } else {
        $reservationRepository->create(
            (int) $_SESSION["user_id"],
            $spaceId,
            $bookingDate,
            $startTime,
            $endTime
        );

        header("Location: my-reservations.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Space | CoworkSpace</title>
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

<main class="form-container">
    <h1>Book a Space</h1>

    <p>
        Space:
        <?= htmlspecialchars($space["name"]) ?>
    </p>

    <p>
        Price:
        <?= htmlspecialchars($space["price"]) ?> JOD / hour
    </p>

    <?php if ($error !== ""): ?>
        <p role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input
            type="hidden"
            name="space_id"
            value="<?= (int) $spaceId ?>"
        >

        <div class="form-group">
            <label for="booking_date">Booking Date</label>
            <input
                type="date"
                id="booking_date"
                name="booking_date"
                min="<?= date("Y-m-d") ?>"
                value="<?= htmlspecialchars($bookingDate) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="start_time">Start Time</label>
            <input
                type="time"
                id="start_time"
                name="start_time"
                value="<?= htmlspecialchars($startTime) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="end_time">End Time</label>
            <input
                type="time"
                id="end_time"
                name="end_time"
                value="<?= htmlspecialchars($endTime) ?>"
                required
            >
        </div>

        <button type="submit" class="btn">Confirm Booking</button>
    </form>
</main>

<footer class="footer">
    <p>&copy; 2026 CoworkSpace. All rights reserved.</p>
</footer>

</body>
</html>