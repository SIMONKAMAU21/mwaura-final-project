<?php
session_start();
include("../db.php");

// Restrict access: only shippers can see this page
$uid = $_SESSION['user_id'] ?? $_SESSION['uid'] ?? null;
if (!$uid || ($_SESSION['role'] ?? '') !== 'shipper') {
    header("location: ../login.php");
    exit();
}

$shipper_id = $uid;

include "sidenav.php";
include "topheader.php";
?>
      <!-- End Navbar -->
      <div class="content">
        <div class="container-fluid">
          <div class="col-md-12">
            <div class="card">
              <div class="card-header card-header-success">
                <h4 class="card-title">Delivered Orders</h4>
                <p class="card-category">A history of all orders you have successfully delivered.</p>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover tablesorter">
                    <thead class="text-primary">
                      <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Delivery Address</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $query = "SELECT o.order_id, u.first_name, u.last_name, u.address1, u.address2,
                                       p.product_title, o.qty, o.p_status
                                FROM orders o
                                JOIN user_info u ON o.user_id = u.user_id
                                JOIN products p ON o.product_id = p.product_id
                                WHERE o.shipper_id = '$shipper_id' AND o.p_status = 'Delivered'
                                ORDER BY o.order_id DESC";
                      $result = mysqli_query($con, $query) or die("Query failed: " . mysqli_error($con));

                      if (mysqli_num_rows($result) == 0) {
                          echo '<tr><td colspan="6" class="text-center">No delivered orders yet.</td></tr>';
                      }

                      while ($row = mysqli_fetch_assoc($result)) {
                          echo "
                          <tr>
                            <td><strong>#" . $row['order_id'] . "</strong></td>
                            <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                            <td>" . htmlspecialchars($row['product_title']) . "</td>
                            <td>" . $row['qty'] . "</td>
                            <td>" . htmlspecialchars($row['address1'] . ', ' . $row['address2']) . "</td>
                            <td><span class='badge badge-success'>" . htmlspecialchars($row['p_status']) . "</span></td>
                          </tr>";
                      }
                      mysqli_close($con);
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
<?php include "footer.php"; ?>
