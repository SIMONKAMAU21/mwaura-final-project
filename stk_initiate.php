<?php
session_start();
include "db.php";

if (isset($_POST['submit'])) {
    if (!isset($_SESSION["uid"])) {
        die("User not logged in");
    }
    $user_id = $_SESSION["uid"];

    // ---- START ORDER CREATION LOGIC (Adapted from checkout_process.php) ----

    // 1. Fetch user billing info
    $user_query = mysqli_query($con, "SELECT * FROM user_info WHERE user_id='$user_id'");
    $user_data = mysqli_fetch_array($user_query);
    $f_name = $user_data["first_name"] . ' ' . $user_data["last_name"];
    $email = $user_data['email'];
    $address = $user_data['address1'];
    $city = $user_data['address2'];
    $state = ""; // Not in user_info table
    $zip = "";   // Not in user_info table

    // 2. Determine new order_id
    $sql0 = "SELECT MAX(order_id) AS max_val from `orders_info`";
    $runquery = mysqli_query($con, $sql0);
    $row = mysqli_fetch_array($runquery);
    $order_id = ($row["max_val"] ?? 0) + 1;

    // 3. Get Cart Totals and Items
    $cart_query = "SELECT a.product_id, a.product_price, b.qty FROM products a, cart b WHERE a.product_id=b.p_id AND b.user_id='$user_id'";
    $run_cart = mysqli_query($con, $cart_query);
    $total_count = mysqli_num_rows($run_cart);
    $prod_total = $_POST['amount']; // From m-pesa.php

    // 4. Insert into orders_info
    $sql_info = "INSERT INTO `orders_info` 
    (`order_id`,`user_id`,`f_name`, `email`,`address`, `city`, `state`, `zip`, `cardname`,`cardnumber`,`expdate`,`prod_count`,`total_amt`,`cvv`) 
    VALUES ($order_id, '$user_id','$f_name','$email', '$address', '$city', '$state', '$zip','M-PESA','N/A','N/A','$total_count','$prod_total','N/A')";

    if (mysqli_query($con, $sql_info)) {
        // 5. Loop through cart items for order_products and orders
        while ($cart_item = mysqli_fetch_assoc($run_cart)) {
            $prod_id = $cart_item['product_id'];
            $prod_qty = $cart_item['qty'];
            $prod_price = $cart_item['product_price'];
            $sub_total = $prod_price * $prod_qty;

            // Insert into order_products
            $sql_prods = "INSERT INTO `order_products` (`order_pro_id`,`order_id`,`product_id`,`qty`,`amt`) 
                          VALUES (NULL, '$order_id', '$prod_id', '$prod_qty', '$sub_total')";
            mysqli_query($con, $sql_prods);

            // Insert into orders (for shipper assignment)
            $sql_orders = "INSERT INTO `orders` (`user_id`, `product_id`, `qty`, `trx_id`, `p_status`, `shipper_id`) 
                           VALUES ('$user_id', '$prod_id', '$prod_qty', 'MPESA-INIT-$order_id', 'Pending', NULL)";
            mysqli_query($con, $sql_orders);
        }

        // 6. Clear Cart
        mysqli_query($con, "DELETE FROM cart WHERE user_id='$user_id'");

        // 7. Send Order Received Email (HTML)
        $to = $email;
        $subject = "Order #$order_id Has been Received - SHOPX";

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
                        <p>We are waiting for your M-Pesa payment confirmation. Once confirmed, we will begin processing your order.</p>
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

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@shopx.com" . "\r\n";

        mail($to, $subject, $message, $headers);
    } else {
        die("Order creation failed: " . mysqli_error($con));
    }

    // ---- END ORDER CREATION LOGIC ----

    date_default_timezone_set('Africa/Nairobi');

    # access token
    $consumerKey = getenv('MPESA_CONSUMER_KEY') ?: 'nk16Y74eSbTaGQgc9WF8j6FigApqOMWr';
    $consumerSecret = getenv('MPESA_CONSUMER_SECRET') ?: '40fD1vRXCq90XFaU';

    # define the variales
    $BusinessShortCode = getenv('MPESA_SHORTCODE') ?: '174379';
    $Passkey = getenv('MPESA_PASSKEY') ?: 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

    $phone = $_POST['phone'];

    // Remove spaces
    $phone = str_replace(' ', '', $phone);

    // Normalize phone number
    if (preg_match('/^07\d{8}$/', $phone)) {
        // 0712345678 → 254712345678
        $phone = '254' . substr($phone, 1);
    } elseif (preg_match('/^\+2547\d{8}$/', $phone)) {
        // +254712345678 → 254712345678
        $phone = substr($phone, 1);
    } elseif (preg_match('/^2547\d{8}$/', $phone)) {
        // already correct → do nothing
    } else {
        die("Invalid phone number format");
    }

    $PartyA = $phone;
    $AccountReference = 'Order #' . $order_id;
    $TransactionDesc = 'Payment for Order #' . $order_id;
    $Amount = $_POST['amount'];

    # Get the timestamp, format YYYYmmddhms -> 20181004151020
    $Timestamp = date('YmdHis');

    # Get the base64 encoded string -> $password. The passkey is the M-PESA Public Key
    $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

    # header for access token
    $headers = ['Content-Type:application/json; charset=utf8'];

    # M-PESA endpoint urls
    $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    # Callback URL: This MUST be a publicly accessible HTTPS URL.
    $BaseURL = getenv('BASE_URL') ?: 'https://mwaura-final-project.onrender.com';
    $CallBackURL = $BaseURL . '/callback_url.php';
    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result = json_decode($result);
    $access_token = $result->access_token;
    curl_close($curl);

    # header for stk push
    $stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];

    # initiating the transaction
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $initiate_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader); //setting custom header

    $curl_post_data = array(
        //Fill in the request parameters with valid values
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $Amount,
        'PartyA' => $PartyA,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $PartyA,
        'CallBackURL' => $CallBackURL,
        'AccountReference' => $AccountReference,
        'TransactionDesc' => $TransactionDesc
    );

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);

    $response = json_decode($curl_response, true);
    $checkoutRequestID = $response['CheckoutRequestID'] ?? null;

    if ($checkoutRequestID) {
        mysqli_query($con, "
        INSERT INTO transactions (order_id, phone, amount, status, checkoutRequestID)
        VALUES ('$order_id', '$PartyA', '$Amount', 'pending', '$checkoutRequestID')
    ");
    }
    print_r($curl_response);

    echo $curl_response;
}
;
?>