<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAdmin();

// Fetch summary metrics
$summary = array(
    'agents' => 0,
    'customers' => 0,
    'policies' => 0,
    'active_policies' => 0,
    'expired_policies' => 0,
    'pending_policies' => 0,
    'payments_count' => 0,
    'revenue' => 0.00
);

// Count summary metrics
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM agents");
if ($row = mysqli_fetch_assoc($res)) $summary['agents'] = $row['cnt'];

$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers");
if ($row = mysqli_fetch_assoc($res)) $summary['customers'] = $row['cnt'];

$res = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status='Expired' THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending
    FROM policies");
if ($row = mysqli_fetch_assoc($res)) {
    $summary['policies'] = $row['total'];
    $summary['active_policies'] = $row['active'] ? $row['active'] : 0;
    $summary['expired_policies'] = $row['expired'] ? $row['expired'] : 0;
    $summary['pending_policies'] = $row['pending'] ? $row['pending'] : 0;
}

$res = mysqli_query($conn, "SELECT COUNT(*) as cnt, SUM(amount) as revenue FROM payments");
if ($row = mysqli_fetch_assoc($res)) {
    $summary['payments_count'] = $row['cnt'];
    $summary['revenue'] = $row['revenue'] ? floatval($row['revenue']) : 0.00;
}

// Fetch payment transactions details
$payments_query = mysqli_query($conn, "SELECT 
    p.id,
    p.amount,
    p.payment_date,
    p.payment_method,
    p.transaction_id,
    pol.policy_name,
    pol.policy_type,
    c.name as customer_name
    FROM payments p
    JOIN policies pol ON p.policy_id = pol.id
    JOIN customers c ON pol.customer_id = c.id
    ORDER BY p.payment_date DESC");

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-4 no-print">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary"></i> Operations <span class="gradient-text">Reports</span></h2>
        <p class="text-secondary">Track platform KPIs, billing, and download standard compliance transaction logs.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <button onclick="window.print()" class="btn gradient-btn"><i class="bi bi-printer"></i> Print Audit Report</button>
    </div>
</div>

<div class="print-certificate-area">
    <!-- Header info shown ONLY in print layout or styled container -->
    <div class="text-center mb-5 border-bottom pb-4">
        <h1 class="fw-bold gradient-text"><i class="bi bi-shield-check-fill"></i> InsureEasy Corp</h1>
        <p class="text-secondary mb-1">Corporate Headquarters &bull; Central Operations Audit Division</p>
        <span class="badge bg-dark">REPORT GENERATED ON: <?php echo date('Y-m-d H:i:s'); ?></span>
    </div>

    <!-- Core KPIs -->
    <h4 class="fw-bold mb-3 border-bottom pb-2">I. Platform Performance Summary</h4>
    <div class="row g-3 text-center mb-5">
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded bg-light">
                <small class="text-muted d-block uppercase fw-semibold">Active Agents</small>
                <strong class="fs-4"><?php echo $summary['agents']; ?></strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded bg-light">
                <small class="text-muted d-block uppercase fw-semibold">Total Customers</small>
                <strong class="fs-4"><?php echo $summary['customers']; ?></strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded bg-light">
                <small class="text-muted d-block uppercase fw-semibold">Active Policies</small>
                <strong class="fs-4 text-success"><?php echo $summary['active_policies']; ?></strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 border rounded bg-light">
                <small class="text-muted d-block uppercase fw-semibold">Total Revenue</small>
                <strong class="fs-4 text-primary">$<?php echo number_format($summary['revenue'], 2); ?></strong>
            </div>
        </div>
    </div>

    <!-- Policies breakdown -->
    <h4 class="fw-bold mb-3 border-bottom pb-2">II. Policy Distribution</h4>
    <div class="row g-3 mb-5 text-center">
        <div class="col-4">
            <div class="p-3 border rounded">
                <small class="text-success d-block fw-semibold">Active Coverages</small>
                <h3 class="fw-bold"><?php echo $summary['active_policies']; ?></h3>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 border rounded">
                <small class="text-danger d-block fw-semibold">Expired Coverages</small>
                <h3 class="fw-bold"><?php echo $summary['expired_policies']; ?></h3>
            </div>
        </div>
        <div class="col-4">
            <div class="p-3 border rounded">
                <small class="text-warning d-block fw-semibold">Pending Approvals</small>
                <h3 class="fw-bold"><?php echo $summary['pending_policies']; ?></h3>
            </div>
        </div>
    </div>

    <!-- Payment log -->
    <h4 class="fw-bold mb-3 border-bottom pb-2">III. Premium Billings & Transactions Audit</h4>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Customer Name</th>
                    <th>Cover Policy</th>
                    <th>Date Paid</th>
                    <th>Payment Method</th>
                    <th class="text-end">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($payments_query) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($payments_query)): ?>
                        <tr>
                            <td><code>#TXN-<?php echo htmlspecialchars($p['transaction_id']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($p['customer_name']); ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars($p['policy_name']); ?></div>
                                <small class="text-muted text-xs"><?php echo htmlspecialchars($p['policy_type']); ?></small>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($p['payment_date'])); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['payment_method']); ?></span></td>
                            <td class="text-end text-success fw-bold">$<?php echo number_format($p['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No transactions have been logged.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Signature Area visible in Print -->
    <div class="row mt-5 pt-5 border-top d-none d-print-flex">
        <div class="col-6 text-center">
            <div style="height: 60px;"></div>
            <div class="border-top mx-auto" style="width: 200px;"></div>
            <small class="text-muted">Operations Director</small>
        </div>
        <div class="col-6 text-center">
            <div style="height: 60px;"></div>
            <div class="border-top mx-auto" style="width: 200px;"></div>
            <small class="text-muted">Chief Auditor</small>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
