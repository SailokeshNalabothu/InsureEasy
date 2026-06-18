<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkCustomer();

$user_id = $_SESSION['user_id'];

// Fetch customer demographic information & photo
$cust_stmt = mysqli_prepare($conn, "SELECT name, email, phone, address, profile_photo FROM customers WHERE id = ?");
mysqli_stmt_bind_param($cust_stmt, "i", $user_id);
mysqli_stmt_execute($cust_stmt);
$cust_res = mysqli_stmt_get_result($cust_stmt);
$customer = mysqli_fetch_assoc($cust_res);
mysqli_stmt_close($cust_stmt);

// Fetch policies of this customer
$policies_stmt = mysqli_prepare($conn, "SELECT id, policy_name, policy_type, premium_amount, start_date, end_date, status FROM policies WHERE customer_id = ? ORDER BY end_date ASC");
mysqli_stmt_bind_param($policies_stmt, "i", $user_id);
mysqli_stmt_execute($policies_stmt);
$policies_res = mysqli_stmt_get_result($policies_stmt);

// Check if any policy is expiring within 7 days or pending payment
$pending_payment_count = 0;
$expiring_soon_count = 0;

$policies_array = [];
while ($row = mysqli_fetch_assoc($policies_res)) {
    $policies_array[] = $row;
    if ($row['status'] === 'Pending') {
        $pending_payment_count++;
    }
    
    // Expiration countdown
    if ($row['status'] === 'Active') {
        $days_left = ceil((strtotime($row['end_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
        if ($days_left >= 0 && $days_left <= 7) {
            $expiring_soon_count++;
        }
    }
}
mysqli_stmt_close($policies_stmt);

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-5 animate-fade-in-up">
    <div class="col-md-8 d-flex align-items-center gap-4 flex-wrap flex-md-nowrap">
        <!-- Display Profile Photo or Default Initials -->
        <div class="profile-photo-container m-0 flex-shrink-0">
            <?php if (!empty($customer['profile_photo'])): ?>
                <img src="../uploads/<?php echo htmlspecialchars($customer['profile_photo']); ?>" alt="Profile Photo">
            <?php else: ?>
                <div class="w-100 h-100 bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-2">
                    <?php echo strtoupper(substr($customer['name'], 0, 2)); ?>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <h1 class="fw-bold mb-1">Welcome, <span class="gradient-text"><?php echo htmlspecialchars($customer['name']); ?></span></h1>
            <p class="text-secondary mb-0">Monitor coverage status, download policy sheets, and make premium payments securely.</p>
        </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="profile.php" class="btn btn-outline-primary"><i class="bi bi-person-gear"></i> Account Settings</a>
    </div>
</div>

<!-- Alert Indicators -->
<?php if ($pending_payment_count > 0 || $expiring_soon_count > 0): ?>
    <div class="row mb-4 animate-fade-in-up">
        <div class="col-12">
            <?php if ($pending_payment_count > 0): ?>
                <div class="alert alert-warning d-flex align-items-center mb-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong>Premium Dues:</strong> You have <strong><?php echo $pending_payment_count; ?></strong> policy contract(s) awaiting payment. Click "Pay Now" below to enforce protection cover immediately.
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($expiring_soon_count > 0): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-alarm-fill fs-4 me-3 animate-pulse"></i>
                    <div>
                        <strong>Upcoming Expiration:</strong> <strong><?php echo $expiring_soon_count; ?></strong> of your active policies will expire within 7 days. Please check dates and contact your agent for renewal support.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- My Policies Table Grid -->
<div class="row mb-5">
    <div class="col-md-12">
        <div class="p-4 glass-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock-fill text-primary"></i> Your Insurance Policies</h5>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Policy Code</th>
                            <th>Policy Name</th>
                            <th>Coverage Type</th>
                            <th>Premium Cost</th>
                            <th>Expiry Date</th>
                            <th>Status / Countdown</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($policies_array) > 0): ?>
                            <?php foreach ($policies_array as $pol): 
                                $days_left = ceil((strtotime($pol['end_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                            ?>
                                <tr>
                                    <td><code>#POL-<?php echo str_pad($pol['id'], 5, '0', STR_PAD_LEFT); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($pol['policy_name']); ?></strong></td>
                                    <td><small class="text-secondary"><?php echo htmlspecialchars($pol['policy_type']); ?></small></td>
                                    <td>$<?php echo number_format($pol['premium_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($pol['end_date']); ?></td>
                                    <td>
                                        <?php if ($pol['status'] === 'Active'): ?>
                                            <span class="badge-custom badge-active mb-1 d-inline-block"><i class="bi bi-check-circle"></i> Active</span>
                                            <?php if ($days_left >= 0): ?>
                                                <small class="d-block text-muted text-xs"><i class="bi bi-hourglass-split"></i> <?php echo $days_left; ?> days remaining</small>
                                            <?php else: ?>
                                                <small class="d-block text-danger text-xs fw-bold">Expires Today</small>
                                            <?php endif; ?>
                                        <?php elseif ($pol['status'] === 'Expired' || $days_left < 0): ?>
                                            <span class="badge-custom badge-expired"><i class="bi bi-x-circle"></i> Expired</span>
                                        <?php else: ?>
                                            <span class="badge-custom badge-pending"><i class="bi bi-hourglass-split"></i> Pending Payment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($pol['status'] === 'Pending'): ?>
                                            <a href="make_payment.php?policy_id=<?php echo $pol['id']; ?>" class="btn btn-sm btn-success px-3"><i class="bi bi-credit-card"></i> Pay Now</a>
                                        <?php else: ?>
                                            <a href="my_policies.php?policy_id=<?php echo $pol['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Details</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-slash fs-2 d-block mb-2 text-secondary"></i> You have no policy coverage sheets created.
                                    <p class="small text-muted mb-0">Please contact an InsureEasy agent to write a policy for your account.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Interactive Recommender widget -->
    <div class="col-md-6">
        <div class="p-4 glass-card h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-3"><i class="bi bi-lightbulb-fill text-warning"></i> Policy Advisor Engine</h5>
                <p class="text-secondary text-sm">Not sure what coverage is best for your current life stage? Run our quick 2-minute diagnostic questionnaire to get customized policy recommendations instantly.</p>
            </div>
            <a href="recommendations.php" class="btn gradient-btn w-100 py-2 mt-3"><i class="bi bi-arrow-right-circle"></i> Launch Policy Finder</a>
        </div>
    </div>
    
    <!-- Profile Card widget -->
    <div class="col-md-6">
        <div class="p-4 glass-card h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="fw-bold mb-3"><i class="bi bi-shield-check-fill text-primary"></i> Profile Information</h5>
                <div class="row text-sm">
                    <div class="col-4 text-muted mb-2">Email:</div>
                    <div class="col-8 mb-2 fw-semibold"><?php echo htmlspecialchars($customer['email']); ?></div>
                    <div class="col-4 text-muted mb-2">Phone:</div>
                    <div class="col-8 mb-2 fw-semibold"><?php echo htmlspecialchars($customer['phone']); ?></div>
                    <div class="col-4 text-muted">Address:</div>
                    <div class="col-8 text-secondary"><?php echo htmlspecialchars($customer['address'] ? $customer['address'] : 'No address provided'); ?></div>
                </div>
            </div>
            <a href="profile.php" class="btn btn-outline-secondary w-100 py-2 mt-3">Edit Details</a>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
