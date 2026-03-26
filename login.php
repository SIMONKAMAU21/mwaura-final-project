<?php
include "db.php";
session_start();

// ---------------------------------------------------------------
// UNIFIED ROLE-BASED LOGIN
// Checks user_info (customers, shippers, admins with role column)
// Falls back to legacy admin_info table for hard-coded admin
// ---------------------------------------------------------------

if (isset($_POST["email"]) && isset($_POST["password"])) {
    $email    = mysqli_real_escape_string($con, $_POST["email"]);
    $password = $_POST["password"];
    $ip_add   = getenv("REMOTE_ADDR");

    // --- Step 1: Check user_info (all registered users + roles) ---
    $sql       = "SELECT * FROM user_info WHERE email = '$email' AND password = '$password'";
    $run_query = mysqli_query($con, $sql);

    if ($run_query && mysqli_num_rows($run_query) == 1) {
        $row = mysqli_fetch_assoc($run_query);

        // Store session variables
        $_SESSION["uid"]        = $row["user_id"];
        $_SESSION["user_id"]    = $row["user_id"];
        $_SESSION["name"]       = $row["first_name"];
        $_SESSION["first_name"] = $row["first_name"];
        $_SESSION["role"]       = $row["role"] ?? 'customer';

        // Merge any guest-cart items into the logged-in user's cart
        if (isset($_COOKIE["product_list"])) {
            $p_list       = stripcslashes($_COOKIE["product_list"]);
            $product_list = json_decode($p_list, true);
            foreach ($product_list as $p_id) {
                $p_id         = intval($p_id);
                $verify_cart  = "SELECT id FROM cart WHERE user_id = '{$row['user_id']}' AND p_id = $p_id";
                $result       = mysqli_query($con, $verify_cart);
                if (mysqli_num_rows($result) < 1) {
                    $update_cart = "UPDATE cart SET user_id = '{$row['user_id']}' WHERE ip_add = '$ip_add' AND user_id = -1";
                    mysqli_query($con, $update_cart);
                } else {
                    $delete_existing = "DELETE FROM cart WHERE user_id = -1 AND ip_add = '$ip_add' AND p_id = $p_id";
                    mysqli_query($con, $delete_existing);
                }
            }
            setcookie("product_list", "", strtotime("-1 day"), "/");
            echo "cart_login";
            exit();
        }

        // Redirect based on role
        echo "login_success";
        switch ($_SESSION["role"]) {
            case 'admin':
                echo "<script>location.href='admin/index.php';</script>";
                break;
            case 'shipper':
                echo "<script>location.href='shipper/index.php';</script>";
                break;
            default: // customer
                echo "<script>location.href='store.php';</script>";
                break;
        }
        exit();
    }

    // --- Step 2: Fallback – check legacy admin_info table ---
    $admin_password = md5($password);
    $sql2           = "SELECT * FROM admin_info WHERE admin_email = '$email' AND admin_password = '$admin_password'";
    $run_query2     = mysqli_query($con, $sql2);

    if ($run_query2 && mysqli_num_rows($run_query2) == 1) {
        $row2 = mysqli_fetch_assoc($run_query2);
        $_SESSION["uid"]     = $row2["admin_id"];
        $_SESSION["user_id"] = $row2["admin_id"];
        $_SESSION["name"]    = $row2["admin_name"];
        $_SESSION["role"]    = 'admin';

        echo "login_success";
        echo "<script>location.href='admin/index.php';</script>";
        exit();
    }

    // --- Step 3: No match found ---
    echo "<span style='color:red;'>Invalid email or password. Please try again.</span>";
}
?>