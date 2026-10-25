<?php
require_once "../db.php";requireAdmin();if($_SERVER["REQUEST_METHOD"]!=="POST"){header("Location: spaces.php");exit;}$id=filter_input(INPUT_POST,"id",FILTER_VALIDATE_INT);if(!$id||$id<1)die("Invalid space ID.");$deleted=(new SpaceRepository($pdo))->delete($id);header("Location: spaces.php?".($deleted?"deleted=1":"delete_error=1"));exit;
