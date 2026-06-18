<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAgent();

$agent_id = $_SESSION['agent_id'];

// Fetch Agent Metrics
$total_customers = 0;
$total_policies = 0;
$due_policies_count = 0;

// Total Customers
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM customers WHERE agent_id = ?");
mysqli_stmt_bind_param($stmt, "i", $agent_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $total_customers = $row['cnt'];
mysqli_stmt_close($stmt);

// Total Policies
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM policies p JOIN customers c ON p.customer_id = c.id WHERE c.agent_id = ?");
mysqli_stmt_bind_param($stmt, "i", $agent_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $total_policies = $row['cnt'];
mysqli_stmt_close($stmt);

// Due Policies within 7 days
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM policies p JOIN customers c ON p.customer_id = c.id WHERE c.agent_id = ? AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND p.end_date >= CURDATE() AND p.status = 'Active'");
mysqli_stmt_bind_param($stmt, "i", $agent_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $due_policies_count = $row['cnt'];
mysqli_stmt_close($stmt);

// Fetch details of due policies for display
$due_policies_stmt = mysqli_prepare($conn, "SELECT 
    p.id as policy_id,
    p.policy_name,
    p.policy_type,
    p.premium_amount,
    p.end_date,
    c.id as customer_id,
    c.name as customer_name,
    c.phone as customer_phone
    FROM policies p 
    JOIN customers c ON p.customer_id = c.id 
    WHERE c.agent_id = ? AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND p.end_date >= CURDATE() AND p.status = 'Active'
    ORDER BY p.end_date ASC");
mysqli_stmt_bind_param($due_policies_stmt, "i", $agent_id);
mysqli_stmt_execute($due_policies_stmt);
$due_policies_res = mysqli_stmt_get_result($due_policies_stmt);

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="fw-bold mb-1">Agent <span class="gradient-text">Dashboard</span></h1>
        <p class="text-secondary">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['agent_name']); ?></strong> &bull; Manage policies and dispatch alerts.</p>
    </div>
    <div class="col-md-4 text-md-end d-flex justify-content-md-end gap-2">
        <a href="add_customer.php" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Add Customer</a>
        <a href="add_policy.php" class="btn gradient-btn"><i class="bi bi-file-earmark-plus"></i> New Policy</a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Your Customers</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0"><?php echo $total_customers; ?></h2>
                <div class="fs-1 text-primary"><i class="bi bi-people"></i></div>
            </div>
            <a href="manage_customers.php" class="small text-decoration-none mt-3 d-inline-block">View Customer List &rarr;</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Policies Written</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0"><?php echo $total_policies; ?></h2>
                <div class="fs-1 text-info"><i class="bi bi-file-earmark-lock"></i></div>
            </div>
            <a href="manage_policies.php" class="small text-decoration-none mt-3 d-inline-block">Manage Coverage Lists &rarr;</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Renewal Dues (7 Days)</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0 text-danger"><?php echo $due_policies_count; ?></h2>
                <div class="fs-1 text-danger"><i class="bi bi-alarm"></i></div>
            </div>
            <a href="#dues-section" class="small text-decoration-none mt-3 d-inline-block text-danger">Review Urgent Dues &darr;</a>
        </div>
    </div>
</div>

<!-- Renewal Alerts list -->
<div class="glass-card p-4 mb-4" id="dues-section">
    <h5 class="fw-bold mb-3"><i class="bi bi-exclamation-octagon text-danger"></i> Immediate Renewal Checklists</h5>
    <p class="text-secondary text-sm">Customers listed below have policies that will expire within the next 7 days. Select "Send Alert" to execute SMS notifications.</p>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Policy Description</th>
                    <th>Premium</th>
                    <th>Expiry Date</th>
                    <th>Days Remaining</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($due_policies_res) > 0): ?>
                    <?php while ($due = mysqli_fetch_assoc($due_policies_res)): 
                        $days_left = (strtotime($due['end_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                    ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($due['customer_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($due['customer_phone']); ?></small>
                            </td>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($due['policy_name']); ?></strong>
                                <div class="text-secondary text-xs"><?php echo htmlspecialchars($due['policy_type']); ?></div>
                            </td>
                            <td>$<?php echo number_format($due['premium_amount'], 2); ?></td>
                            <td><span class="text-danger fw-bold"><?php echo htmlspecialchars($due['end_date']); ?></span></td>
                            <td>
                                <span class="badge bg-danger rounded-pill"><?php echo $days_left; ?> days left</span>
                            </td>
                            <td class="text-end">
                                <a href="send_sms.php?customer_id=<?php echo $due['customer_id']; ?>&policy_id=<?php echo $due['policy_id']; ?>" class="btn btn-sm btn-danger"><i class="bi bi-chat-dots"></i> Send Alert</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-check2-all fs-2 text-success d-block mb-2"></i> All customer coverage plans are active and healthy. No renewal alerts due.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_stmt_close($due_policies_stmt);
include_once '../includes/footer.php';
?>
