<?php
session_start();
include("../db.php");

// Restrict access: only shippers can update statuses
$uid = $_SESSION['user_id'] ?? $_SESSION['uid'] ?? null;
if (!$uid || ($_SESSION['role'] ?? '') !== 'shipper') {
    header("location: ../login.php");
    exit();
}

$shipper_id = $uid;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id   = intval($_POST['order_id']);
    $new_status = $_POST['status'];

    // Whitelist allowed statuses
    $allowed = ['Assigned', 'In Transit', 'Delivered', 'Failed'];
    if (!in_array($new_status, $allowed)) {
        header("location: index.php");
        exit();
    }

    // Only update if the order belongs to this shipper (security check)
    $stmt = mysqli_prepare($con, "UPDATE orders SET p_status=? WHERE order_id=? AND shipper_id=?");
    mysqli_stmt_bind_param($stmt, 'sii', $new_status, $order_id, $shipper_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($con);
header("location: index.php?success=1");
exit();
?>
