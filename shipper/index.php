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
$shipper_name = $_SESSION['first_name'] ?? $_SESSION['name'] ?? 'Shipper';

include "sidenav.php";
include "topheader.php";
?>
<!-- End Navbar -->
<div class="content">
  <div class="container-fluid">

    <!-- Stats Row -->
    <div class="row">
      <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-warning card-header-icon">
            <div class="card-icon">
              <i class="material-icons">local_shipping</i>
            </div>
            <p class="card-category">Total Assigned</p>
            <h3 class="card-title">
              <?php
              $res = mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE shipper_id='$shipper_id'");
              $row = mysqli_fetch_array($res);
              echo $row[0];
              ?>
            </h3>
          </div>
          <div class="card-footer">
            <div class="stats"><i class="material-icons">assignment</i> Orders assigned to you</div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-success card-header-icon">
            <div class="card-icon">
              <i class="material-icons">check_circle</i>
            </div>
            <p class="card-category">Delivered</p>
            <h3 class="card-title">
              <?php
              $res2 = mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE shipper_id='$shipper_id' AND p_status='Delivered'");
              $row2 = mysqli_fetch_array($res2);
              echo $row2[0];
              ?>
            </h3>
          </div>
          <div class="card-footer">
            <div class="stats"><i class="material-icons">done_all</i> Successfully delivered</div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 col-sm-6">
        <div class="card card-stats">
          <div class="card-header card-header-danger card-header-icon">
            <div class="card-icon">
              <i class="material-icons">pending</i>
            </div>
            <p class="card-category">Pending / In Transit</p>
            <h3 class="card-title">
              <?php
              $res3 = mysqli_query($con, "SELECT COUNT(*) FROM orders WHERE shipper_id='$shipper_id' AND p_status != 'Delivered'");
              $row3 = mysqli_fetch_array($res3);
              echo $row3[0];
              ?>
            </h3>
          </div>
          <div class="card-footer">
            <div class="stats"><i class="material-icons">local_shipping</i> Orders in progress</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary">
            <h4 class="card-title">My Assigned Orders</h4>
            <p class="card-category">Welcome, <?php echo htmlspecialchars($shipper_name); ?>. Update the delivery status
              for each order below.</p>
          </div>
          <div class="card-body">

            <?php if (isset($_GET['success'])): ?>
              <div class="alert alert-success">Order status updated successfully!</div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-hover tablesorter">
                <thead class="text-primary">
                  <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Delivery Address</th>
                    <th>Contact</th>
                    <th>Current Status</th>
                    <th>Update Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $query = "SELECT o.order_id, u.first_name, u.last_name, u.mobile, u.email, u.address1, u.address2,
                                         p.product_title, o.qty, o.p_status
                                  FROM orders o
                                  JOIN user_info u ON o.user_id = u.user_id
                                  JOIN products p ON o.product_id = p.product_id
                                  WHERE o.shipper_id = '$shipper_id'
                                  ORDER BY o.order_id DESC";
                  $result = mysqli_query($con, $query) or die("Query failed: " . mysqli_error($con));

                  if (mysqli_num_rows($result) == 0) {
                    echo '<tr><td colspan="8" class="text-center">No orders assigned to you yet.</td></tr>';
                  }

                  while ($row = mysqli_fetch_assoc($result)) {
                    $status_badge = 'badge-warning';
                    if ($row['p_status'] == 'Delivered')
                      $status_badge = 'badge-success';
                    if ($row['p_status'] == 'In Transit')
                      $status_badge = 'badge-info';
                    if ($row['p_status'] == 'Failed')
                      $status_badge = 'badge-danger';
                    echo "
                            <tr>
                              <td><strong>#" . $row['order_id'] . "</strong></td>
                              <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                              <td>" . htmlspecialchars($row['product_title']) . "</td>
                              <td>" . $row['qty'] . "</td>
                              <td>" . htmlspecialchars($row['address1'] . ', ' . $row['address2']) . "</td>
                              <td>" . htmlspecialchars($row['mobile']) . "<br><small>" . htmlspecialchars($row['email']) . "</small></td>
                              <td><span class='badge $status_badge'>" . htmlspecialchars($row['p_status']) . "</span></td>
                              <td>
                                <form action='update_status.php' method='POST' style='display:flex; gap:5px; color:#ffffff;'>
                                  <input type='hidden' name='order_id' value='" . $row['order_id'] . "'>
                                  <select name='status' class='form-control form-control-sm' style='width:auto; background-color: #2b2323ff; color: #ffffff;'>
                                    <option value='Assigned'" . ($row['p_status'] == 'Assigned' ? ' selected' : '') . ">Assigned</option>
                                    <option value='In Transit'" . ($row['p_status'] == 'In Transit' ? ' selected' : '') . ">In Transit</option>
                                    <option value='Delivered'" . ($row['p_status'] == 'Delivered' ? ' selected' : '') . ">Delivered</option>
                                    <option value='Failed'" . ($row['p_status'] == 'Failed' ? ' selected' : '') . ">Failed</option>
                                  </select>
                                  <button type='submit' class='btn btn-sm btn-primary'>Update Status</button>
                                </form>
                              </td>
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
</div>
<?php include "footer.php"; ?>