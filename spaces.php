<?php
require_once "db.php";

$spaceRepository = new SpaceRepository($pdo);
$spaces = $spaceRepository->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Spaces | CoworkSpace</title>
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
            <a href="login.php">Login</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container">
            <h1>Explore Our Workspaces</h1>
            <p>Find the right space for work, study, and meetings.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Available Spaces</h2>

            <div class="cards">
                <?php foreach ($spaces as $space): ?>
                    <article class="card">
                        <h3><?= htmlspecialchars($space["name"]) ?></h3>

                        <p>
                            <?= htmlspecialchars($space["description"] ?? "") ?>
                        </p>

                        <p>
                            Capacity: <?= (int) $space["capacity"] ?> people
                        </p>

                        <p class="price">
                            <?= htmlspecialchars($space["price"]) ?> JOD / hour
                        </p>

                        <a
                            href="book.php?space_id=<?= (int) $space["id"] ?>"
                            class="btn"
                        >Book Now</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <p>&copy; 2026 CoworkSpace. All rights reserved.</p>
</footer>

</body>
</html>