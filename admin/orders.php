<?php
session_start();
include("../db.php");

error_reporting(0);

// Handle shipper assignment
if (isset($_POST['assign_shipper'])) {
  $order_id = intval($_POST['order_id']);
  $shipper_id = intval($_POST['shipper_id']);
  $new_status = ($shipper_id > 0) ? 'Assigned' : 'Pending';

  if (mysqli_query($con, "UPDATE orders SET shipper_id='$shipper_id', p_status='$new_status' WHERE order_id='$order_id'")) {

    // ---- EMAIL NOTIFICATION ----
    if ($shipper_id > 0) {
      // Get customer email and order details
      $email_query = "SELECT u.email, u.first_name as cust_name, s.first_name as ship_name 
                            FROM orders o 
                            JOIN user_info u ON o.user_id = u.user_id 
                            JOIN user_info s ON s.user_id = '$shipper_id'
                            WHERE o.order_id = '$order_id' LIMIT 1";
      $email_res = mysqli_query($con, $email_query);
      if ($email_data = mysqli_fetch_assoc($email_res)) {
        $to = $email_data['email'];
        $subject = "Order #$order_id Assigned to Shipper";
        $message = "Hello " . $email_data['cust_name'] . ",\n\n";
        $message .= "Your order #$order_id has been assigned to our shipper, " . $email_data['ship_name'] . ".\n";
        $message .= "You will receive further updates as your order moves towards delivery.\n\n";
        $message .= "Thank you for shopping with us!";
        $headers = "From: no-reply@shopx.com";

        // Send email
        @mail($to, $subject, $message, $headers);
      }
    }
    // ---- END EMAIL ----

    header("location: orders.php?page=1&assigned=1");
    exit();
  } else {
    die("Assign query failed");
  }
}

// Handle order delete
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
  $order_id = intval($_GET['order_id']);
  mysqli_query($con, "DELETE FROM orders WHERE order_id='$order_id'") or die("delete query failed");
}

// Pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$page1 = ($page == "" || $page == "1") ? 0 : ($page * 10) - 10;

// Fetch all shippers for the assignment dropdown
$shippers_result = mysqli_query($con, "SELECT user_id, first_name, last_name FROM user_info WHERE role='shipper'");
$shippers = [];
while ($s = mysqli_fetch_assoc($shippers_result)) {
  $shippers[] = $s;
}

include "sidenav.php";
include "topheader.php";
?>
<!-- End Navbar -->
<div class="content">
  <div class="container-fluid">
    <div class="col-md-14">
      <div class="card">
        <div class="card-header card-header-primary">
          <h4 class="card-title">Orders / Page <?php echo $page; ?></h4>
        </div>
        <div class="card-body">

          <?php if (isset($_GET['assigned'])): ?>
            <div class="alert alert-success">Shipper assigned successfully!</div>
          <?php endif; ?>

          <div class="table-responsive ps">
            <table class="table table-hover tablesorter">
              <thead class="text-primary">
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Product</th>
                  <th>Contact | Email</th>
                  <th>Address</th>
                  <th>Qty</th>
                  <th>Status</th>
                  <th>Assign Shipper</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $result = mysqli_query(
                  $con,
                  "SELECT o.order_id, o.qty, o.p_status, o.shipper_id,
                                  p.product_title,
                                  u.first_name, u.mobile, u.email, u.address1, u.address2
                           FROM orders o
                           JOIN products p ON o.product_id = p.product_id
                           JOIN user_info u ON o.user_id = u.user_id
                           LIMIT $page1, 10"
                ) or die("query failed: " . mysqli_error($con));

                while ($row = mysqli_fetch_assoc($result)) {
                  $status_badge = 'badge-warning';
                  if ($row['p_status'] == 'Delivered')
                    $status_badge = 'badge-success';
                  if ($row['p_status'] == 'In Transit')
                    $status_badge = 'badge-info';
                  if ($row['p_status'] == 'Assigned')
                    $status_badge = 'badge-primary';
                  if ($row['p_status'] == 'Failed')
                    $status_badge = 'badge-danger';

                  // Build shipper dropdown options
                  $options = '<option value="0">-- Unassigned --</option>';
                  foreach ($shippers as $s) {
                    $sel = ($s['user_id'] == $row['shipper_id']) ? 'selected' : '';
                    $options .= "<option value='{$s['user_id']}' $sel>{$s['first_name']} {$s['last_name']}</option>";
                  }

                  echo "
                          <tr>
                            <td><strong>#{$row['order_id']}</strong></td>
                            <td>" . htmlspecialchars($row['first_name']) . "</td>
                            <td>" . htmlspecialchars($row['product_title']) . "</td>
                            <td>" . htmlspecialchars($row['email']) . "<br>" . htmlspecialchars($row['mobile']) . "</td>
                            <td>" . htmlspecialchars($row['address1'] . ', ' . $row['address2']) . "</td>
                            <td>{$row['qty']}</td>
                            <td><span class='badge $status_badge'>{$row['p_status']}</span></td>
                            <td>
                              <form action='orders.php' method='POST' style='display:flex; gap:5px;'>
                                <input type='hidden' name='order_id' value='{$row['order_id']}'>
                                <select name='shipper_id' class='form-control form-control-sm' style='width:auto;'>
                                  $options
                                </select>
                                <button type='submit' name='assign_shipper' class='btn btn-sm btn-info'>Assign</button>
                              </form>
                            </td>
                            <td>
                              <a class='btn btn-danger btn-sm' href='orders.php?order_id={$row['order_id']}&action=delete' onclick=\"return confirm('Delete this order?')\">Delete</a>
                            </td>
                          </tr>";
                }
                ?>
              </tbody>
            </table>

            <!-- Pagination -->
            <div style="margin-top:10px;">
              <?php
              $total_res = mysqli_query($con, "SELECT COUNT(*) FROM orders");
              $total_row = mysqli_fetch_array($total_res);
              $total_pages = ceil($total_row[0] / 10);
              for ($i = 1; $i <= $total_pages; $i++) {
                $active = ($i == $page) ? 'btn-primary' : 'btn-default';
                echo "<a href='orders.php?page=$i' class='btn btn-sm $active' style='margin:2px;'>$i</a>";
              }
              ?>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
mysqli_close($con);
include "footer.php";
?>