<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkCustomer();

$user_id = $_SESSION['user_id'];

// Fetch payment transactions for this customer
$stmt = mysqli_prepare($conn, "SELECT 
    p.id, 
    p.amount, 
    p.payment_date, 
    p.payment_method, 
    p.transaction_id, 
    pol.policy_name, 
    pol.policy_type
    FROM payments p
    JOIN policies pol ON p.policy_id = pol.id
    WHERE pol.customer_id = ?
    ORDER BY p.payment_date DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$payments_res = mysqli_stmt_get_result($stmt);
?>

<?php include_once '../includes/header.php'; ?>

<div class="mb-4">
    <a href="dashboard.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="glass-card p-4 animate-fade-in-up">
    <h3 class="fw-bold mb-4"><i class="bi bi-credit-card-2-back-fill text-primary"></i> Premium Payment <span class="gradient-text">History</span></h3>
    <p class="text-secondary mb-4">Check past premium logs, payment receipts, and security authorization transaction codes.</p>

    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Receipt ID</th>
                    <th>Transaction Reference</th>
                    <th>Cover Policy Plan</th>
                    <th>Category</th>
                    <th>Date Paid</th>
                    <th>Payment Method</th>
                    <th class="text-end">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($payments_res) > 0): ?>
                    <?php while ($pay = mysqli_fetch_assoc($payments_res)): ?>
                        <tr>
                            <td><code>#REC-<?php echo str_pad($pay['id'], 6, '0', STR_PAD_LEFT); ?></code></td>
                            <td><span class="font-monospace fw-semibold">#TXN-<?php echo htmlspecialchars($pay['transaction_id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($pay['policy_name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($pay['policy_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($pay['payment_date']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($pay['payment_method']); ?></span></td>
                            <td class="text-end text-success fw-bold">$<?php echo number_format($pay['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-2"></i> No transaction receipts found.
                            <p class="small text-muted mb-0">Payments will register here once you execute premium checkout for pending policies.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
include_once '../includes/footer.php';
?>
