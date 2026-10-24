<?php
require_once "../db.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: spaces.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid space ID.");
}

$spaceRepository = new SpaceRepository($pdo);
$spaceRepository->delete($id);

header("Location: spaces.php");
exit;