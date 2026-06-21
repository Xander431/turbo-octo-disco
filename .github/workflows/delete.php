<?php
include "config.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {

    // look up the image first, so the uploaded file gets cleaned up too
    $find = mysqli_prepare($conn, "SELECT image FROM notes WHERE id = ?");
    mysqli_stmt_bind_param($find, "i", $id);
    mysqli_stmt_execute($find);
    $findResult = mysqli_stmt_get_result($find);
    $row = $findResult ? mysqli_fetch_assoc($findResult) : null;

    if ($row && !empty($row['image']) && is_file("uploads/" . $row['image'])) {
        unlink("uploads/" . $row['image']);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM notes WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}

header("Location: index.php");
exit();
?>