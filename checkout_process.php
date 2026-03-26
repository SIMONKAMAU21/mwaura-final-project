<?php
session_start();
include "db.php";
if (isset($_SESSION["uid"])) {

    $f_name = $_POST["firstname"];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip = $_POST['zip'];
    $cardname = $_POST['cardname'];
    $cardnumber = $_POST['cardNumber'];
    $expdate = $_POST['expdate'];
    $cvv = $_POST['cvv'];
    $user_id = $_SESSION["uid"];
    $cardnumberstr = (string) $cardnumber;
    $total_count = $_POST['total_count'];
    $prod_total = $_POST['total_price'];


    $sql0 = "SELECT order_id from `orders_info`";
    $runquery = mysqli_query($con, $sql0);
    if (mysqli_num_rows($runquery) == 0) {
        echo (mysqli_error($con));
        $order_id = 1;
    } else if (mysqli_num_rows($runquery) > 0) {
        $sql2 = "SELECT MAX(order_id) AS max_val from `orders_info`";
        $runquery1 = mysqli_query($con, $sql2);
        $row = mysqli_fetch_array($runquery1);
        $order_id = $row["max_val"];
        $order_id = $order_id + 1;
        echo (mysqli_error($con));
    }

    $sql = "INSERT INTO `orders_info` 
	(`order_id`,`user_id`,`f_name`, `email`,`address`, 
	`city`, `state`, `zip`, `cardname`,`cardnumber`,`expdate`,`prod_count`,`total_amt`,`cvv`) 
	VALUES ($order_id, '$user_id','$f_name','$email', 
    '$address', '$city', '$state', '$zip','$cardname','$cardnumberstr','$expdate','$total_count','$prod_total','$cvv')";


    if (mysqli_query($con, $sql)) {
        $i = 1;
        $prod_id_ = 0;
        $prod_price_ = 0;
        $prod_qty_ = 0;
        while ($i <= $total_count) {
            $str = (string) $i;
            $prod_id_ + $str = $_POST['prod_id_' . $i];
            $prod_id = $prod_id_ + $str;
            $prod_price_ + $str = $_POST['prod_price_' . $i];
            $prod_price = $prod_price_ + $str;
            $prod_qty_ + $str = $_POST['prod_qty_' . $i];
            $prod_qty = $prod_qty_ + $str;
            $sub_total = (int) $prod_price * (int) $prod_qty;
            $sql1 = "INSERT INTO `order_products` 
            (`order_pro_id`,`order_id`,`product_id`,`qty`,`amt`) 
            VALUES (NULL, '$order_id','$prod_id','$prod_qty','$sub_total')";

            if (mysqli_query($con, $sql1)) {

                // ---- Insert into orders table (for shipper assignment) ----
                $sql_order = "INSERT INTO `orders` (`user_id`, `product_id`, `qty`, `trx_id`, `p_status`, `shipper_id`) 
                              VALUES ('$user_id', '$prod_id', '$prod_qty', 'CHECKOUT-$order_id', 'Pending', NULL)";
                mysqli_query($con, $sql_order);
                // ---- End orders insert ----

            } else {
                echo (mysqli_error($con));
            }
            $i++;
        }

        // 6. Clear Cart
        mysqli_query($con, "DELETE FROM cart WHERE user_id=$user_id");

        // 7. Send Email Notification (Once per order)
        $to = $email;
        $subject = "Order #$order_id Has been Received - SHOPX";

        // HTML Email Template
        $message = "
        <html>
        <head>
            <style>
                .wrapper { background-color: #f4f4f4; padding: 20px; font-family: sans-serif; }
                .container { background-color: #ffffff; padding: 40px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
                .header { text-align: center; border-bottom: 2px solid #6729ab; padding-bottom: 20px; }
                .header h1 { color: #6729ab; margin: 0; }
                .content { padding-top: 30px; line-height: 1.6; color: #333; }
                .order-id { font-weight: bold; color: #6729ab; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <div class='container'>
                    <div class='header'>
                        <h1>SHOPX</h1>
                    </div>
                    <div class='content'>
                        <h2>Hello $f_name,</h2>
                        <p>Thank you for shopping with us! Your order <span class='order-id'>#$order_id</span> has been successfully received.</p>
                        <p>Our team is now processing it. You will receive further updates as your order moves towards delivery.</p>
                        <p>If you have any questions, feel free to reply to this email.</p>
                        <p>Best regards,<br>The SHOPX Team</p>
                    </div>
                    <div class='footer'>
                        &copy; " . date('Y') . " SHOPX Online Store. All rights reserved.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@shopx.com" . "\r\n";

        mail($to, $subject, $message, $headers);

        echo "<script>window.location.href='store.php'</script>";

    } else {

        echo (mysqli_error($con));

    }

} else {
    echo "<script>window.location.href='index.php'</script>";
}





?>