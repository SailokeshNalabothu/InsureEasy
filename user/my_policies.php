<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkCustomer();

$user_id = $_SESSION['user_id'];
$policy_id = isset($_GET['policy_id']) ? intval($_GET['policy_id']) : 0;

$selected_policy = null;

if ($policy_id > 0) {
    // Fetch specific policy detail for certificate printout
    $stmt = mysqli_prepare($conn, "SELECT p.id, p.policy_name, p.policy_type, p.premium_amount, p.start_date, p.end_date, p.status, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address FROM policies p JOIN customers c ON p.customer_id = c.id WHERE p.id = ? AND p.customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $policy_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $selected_policy = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

// Fetch all policies for standard list fallback
$list_stmt = mysqli_prepare($conn, "SELECT id, policy_name, policy_type, premium_amount, start_date, end_date, status FROM policies WHERE customer_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($list_stmt, "i", $user_id);
mysqli_stmt_execute($list_stmt);
$all_policies = mysqli_stmt_get_result($list_stmt);
?>

<?php include_once '../includes/header.php'; ?>

<div class="mb-4 no-print">
    <?php if ($selected_policy): ?>
        <a href="my_policies.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to All Policies</a>
    <?php else: ?>
        <a href="dashboard.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    <?php endif; ?>
</div>

<?php if ($selected_policy): ?>
    <!-- DETAILED CERTIFICATE DESIGN VIEW -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-end mb-4 no-print">
                <button onclick="window.print()" class="btn gradient-btn"><i class="bi bi-printer"></i> Download / Print Certificate</button>
            </div>
            
            <div class="print-certificate-area p-5 glass-card bg-white text-dark shadow-lg" style="border: 6px double var(--accent-primary); border-radius: 20px; font-family: 'Georgia', serif;">
                <div class="text-center border-bottom pb-4 mb-4">
                    <span class="fs-2 fw-bold text-primary" style="font-family:'Inter', sans-serif;"><i class="bi bi-shield-check-fill"></i> InsureEasy Corp</span>
                    <h2 class="fw-bold text-uppercase mt-2 tracking-wide text-dark" style="letter-spacing: 2px;">Certificate of Insurance</h2>
                    <p class="text-muted mb-0">Policy Coverage & Terms Declaration</p>
                </div>
                
                <div class="row mb-4" style="font-size: 0.95rem;">
                    <div class="col-sm-6">
                        <small class="text-muted d-block text-uppercase">CONTRACT DECLARED TO:</small>
                        <strong><?php echo htmlspecialchars($selected_policy['customer_name']); ?></strong><br>
                        <?php echo htmlspecialchars($selected_policy['customer_email']); ?><br>
                        Phone: <?php echo htmlspecialchars($selected_policy['customer_phone']); ?><br>
                        <?php echo htmlspecialchars($selected_policy['customer_address']); ?>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                        <small class="text-muted d-block text-uppercase">POLICY IDENTIFIER REFERENCE:</small>
                        <strong class="text-primary font-monospace">#POL-<?php echo str_pad($selected_policy['id'], 5, '0', STR_PAD_LEFT); ?></strong><br>
                        <small class="text-muted d-block text-uppercase mt-2">COVERAGE TYPE:</small>
                        <strong><?php echo htmlspecialchars($selected_policy['policy_type']); ?></strong>
                    </div>
                </div>
                
                <div class="p-4 rounded mb-4" style="background-color: rgba(79, 70, 229, 0.05); border: 1px solid rgba(79, 70, 229, 0.15);">
                    <div class="row text-center align-items-center">
                        <div class="col-4">
                            <small class="text-muted d-block text-uppercase" style="font-size:0.8rem;">Start Date</small>
                            <strong class="fs-5 text-dark"><?php echo $selected_policy['start_date']; ?></strong>
                        </div>
                        <div class="col-4 border-start border-end">
                            <small class="text-muted d-block text-uppercase" style="font-size:0.8rem;">Expiry Date</small>
                            <strong class="fs-5 text-dark"><?php echo $selected_policy['end_date']; ?></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block text-uppercase" style="font-size:0.8rem;">Status Badge</small>
                            <span class="fs-5 text-success fw-bold text-uppercase"><?php echo $selected_policy['status']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-bold border-bottom pb-2 text-dark" style="font-family:'Inter', sans-serif;">I. Coverage Specifics</h5>
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Schedule Item</th>
                                <th class="text-end">Cost Valuation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($selected_policy['policy_name']); ?></strong>
                                    <div class="small text-muted">Core Premium Assessment protection covering selected category.</div>
                                </td>
                                <td class="text-end fw-bold text-success">$<?php echo number_format($selected_policy['premium_amount'], 2); ?></td>
                            </tr>
                            <tr class="table-group-divider">
                                <td class="text-end fw-semibold">Total Net Premium Dues:</td>
                                <td class="text-end fw-bold text-primary">$<?php echo number_format($selected_policy['premium_amount'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-5" style="font-size: 0.8rem; line-height: 1.5; color:#555;">
                    <h6 class="fw-bold text-dark text-uppercase mb-2" style="font-family:'Inter', sans-serif;">II. Terms of Contract Assurance</h6>
                    <p>This document serves as proof that the coverage policy listed above is enforced. By accepting this policy schedule, InsureEasy Corp agrees to cover all declared damage and liabilities inside structural limits as defined in the master service agreements. This policy remains active until the expiration date declared unless suspended for non-payment.</p>
                </div>

                <div class="row align-items-center mt-5 pt-4 border-top">
                    <div class="col-6 text-center text-muted" style="font-size: 0.8rem;">
                        <i class="bi bi-qr-code fs-1 d-block mb-1 text-dark"></i>
                        Verification QR Code
                    </div>
                    <div class="col-6 text-center">
                        <div style="font-family:'Dancing Script', cursive; font-size:1.5rem; color:#1e1b4b;" class="mb-1">InsureEasy Central</div>
                        <div class="border-top mx-auto" style="width: 150px;"></div>
                        <small class="text-muted">Authorized Registrar Signature</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- LIST VIEW OF ALL USER POLICIES -->
    <div class="glass-card p-4 animate-fade-in-up">
        <h3 class="fw-bold mb-4"><i class="bi bi-file-earmark-lock-fill text-primary"></i> Your Insurance <span class="gradient-text">Policies</span></h3>
        <p class="text-secondary mb-4">Review all policy contracts, coverage terms, premiums, and print certificates.</p>

        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Policy ID</th>
                        <th>Policy Name</th>
                        <th>Policy Type</th>
                        <th>Premium</th>
                        <th>Coverage Start</th>
                        <th>Coverage End</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($all_policies) > 0): ?>
                        <?php while ($pol = mysqli_fetch_assoc($all_policies)): ?>
                            <tr>
                                <td><code>#POL-<?php echo str_pad($pol['id'], 5, '0', STR_PAD_LEFT); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($pol['policy_name']); ?></strong></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($pol['policy_type']); ?></span></td>
                                <td class="fw-bold text-success">$<?php echo number_format($pol['premium_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($pol['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($pol['end_date']); ?></td>
                                <td>
                                    <?php if ($pol['status'] === 'Active'): ?>
                                        <span class="badge-custom badge-active"><i class="bi bi-check-circle"></i> Active</span>
                                    <?php elseif ($pol['status'] === 'Expired'): ?>
                                        <span class="badge-custom badge-expired"><i class="bi bi-x-circle"></i> Expired</span>
                                    <?php else: ?>
                                        <span class="badge-custom badge-pending"><i class="bi bi-hourglass-split"></i> Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($pol['status'] === 'Pending'): ?>
                                        <a href="make_payment.php?policy_id=<?php echo $pol['id']; ?>" class="btn btn-sm btn-success"><i class="bi bi-credit-card"></i> Pay Now</a>
                                    <?php else: ?>
                                        <a href="my_policies.php?policy_id=<?php echo $pol['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> View Certificate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-emoji-neutral fs-2 d-block mb-2"></i> No policies found for your account. Contact an agent to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
mysqli_stmt_close($list_stmt);
include_once '../includes/footer.php';
?>
