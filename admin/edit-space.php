<?php
require_once "../db.php";
requireAdmin();

$spaceRepository = new SpaceRepository($pdo);
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid space ID.");
}

$space = $spaceRepository->findById($id);

if (!$space) {
    die("Space not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $capacity = (int) ($_POST["capacity"] ?? 0);
    $price = (float) ($_POST["price"] ?? 0);
    $description = trim($_POST["description"] ?? "");

    if ($name !== "" && $capacity > 0 && $price >= 0) {
        $spaceRepository->update(
            $id,
            $name,
            $capacity,
            $price,
            $description
        );

        header("Location: spaces.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Space</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <h1>Edit Space</h1>

    <form method="POST">
        <label>Space Name:</label>
        <input
            type="text"
            name="name"
            value="<?= htmlspecialchars($space["name"]) ?>"
            required
        >

        <label>Capacity:</label>
        <input
            type="number"
            name="capacity"
            value="<?= (int) $space["capacity"] ?>"
            min="1"
            required
        >

        <label>Price:</label>
        <input
            type="number"
            name="price"
            value="<?= htmlspecialchars($space["price"]) ?>"
            min="0"
            step="0.01"
            required
        >

        <label>Description:</label>
        <textarea name="description"><?= htmlspecialchars($space["description"] ?? "") ?></textarea>

        <button type="submit">Save Changes</button>
    </form>

</body>
</html>