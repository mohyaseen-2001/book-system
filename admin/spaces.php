<?php
require_once "../db.php";
requireAdmin();

$spaceRepository = new SpaceRepository($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_space"])) {
    $name = trim($_POST["name"] ?? "");
    $capacity = (int) ($_POST["capacity"] ?? 0);
    $price = (float) ($_POST["price"] ?? 0);
    $description = trim($_POST["description"] ?? "");

    if ($name !== "" && $capacity > 0 && $price >= 0) {
        $spaceRepository->create($name, $capacity, $price, $description);

        header("Location: spaces.php");
        exit;
    }
}

$spaces = $spaceRepository->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Spaces</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Manage Spaces</h1>

    <h2>Add New Space</h2>

    <form method="POST" action="spaces.php">
        <label>Space Name:</label>
        <input type="text" name="name" required>

        <label>Capacity:</label>
        <input type="number" name="capacity" min="1" required>

        <label>Price:</label>
        <input type="number" name="price" min="0" step="0.01" required>

        <label>Description:</label>
        <textarea name="description"></textarea>

        <button type="submit" name="add_space" class="btn">
            Add Space
        </button>
    </form>

    <h2>All Spaces</h2>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Capacity</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($spaces as $space): ?>
                <tr>
                    <td><?= htmlspecialchars($space["name"]) ?></td>
                    <td><?= (int) $space["capacity"] ?></td>
                    <td><?= htmlspecialchars($space["price"]) ?></td>
                    <td>
                        <a
                            href="edit-space.php?id=<?= (int) $space["id"] ?>"
                            class="btn"
                        >Edit</a>

                        <form
                            action="delete-space.php"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this space?')"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $space["id"] ?>"
                            >
                            <button type="submit" class="btn btn-outline">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="index.php">Back to Dashboard</a></p>

</body>
</html>