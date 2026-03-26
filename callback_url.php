<?php
    header("Content-Type: application/json");
    include "db.php";

    $response = '{
        "ResultCode": 0, 
        "ResultDesc": "Confirmation Received Successfully"
    }';

    // Get the JSON response from M-Pesa
    $mpesaResponse = file_get_contents('php://input');

    // 1. Log the raw response for debugging
    $logFile = "M_PESAConfirmationResponse.txt";
    $log = fopen($logFile, "a");
    fwrite($log, $mpesaResponse . PHP_EOL);
    fclose($log);

    // 2. Parse the response
    $data = json_decode($mpesaResponse, true);

    if (isset($data['Body']['stkCallback'])) {
        $callbackData = $data['Body']['stkCallback'];
        $checkoutRequestID = $callbackData['CheckoutRequestID'];
        $resultCode = $callbackData['ResultCode'];
        $resultDesc = $callbackData['ResultDesc'];
        
        $status = ($resultCode == 0) ? 'completed' : 'failed';
        if ($resultCode == 1032) $status = 'cancelled';

        // Extract Metadata for successful payments
        $receiptNumber = "";
        if ($resultCode == 0 && isset($callbackData['CallbackMetadata']['Item'])) {
            foreach ($callbackData['CallbackMetadata']['Item'] as $item) {
                if ($item['Name'] == 'MpesaReceiptNumber') {
                    $receiptNumber = $item['Value'];
                }
            }
        }

        // 3. Update the transactions table
        $update_txn = "UPDATE transactions SET status='$status' WHERE checkoutRequestID='$checkoutRequestID'";
        mysqli_query($con, $update_txn);

        // 4. If successful, update the orders table
        if ($resultCode == 0) {
            // First, get the order_id from the transaction
            $get_order = mysqli_query($con, "SELECT order_id FROM transactions WHERE checkoutRequestID='$checkoutRequestID' LIMIT 1");
            if ($row = mysqli_fetch_assoc($get_order)) {
                $order_id = $row['order_id'];
                
                // Update orders status and trx_id
                $update_order = "UPDATE orders SET p_status='Paid', trx_id='$receiptNumber' WHERE trx_id LIKE 'MPESA-INIT-$order_id'";
                mysqli_query($con, $update_order);
            }
        }
    }

    echo $response;
?>
