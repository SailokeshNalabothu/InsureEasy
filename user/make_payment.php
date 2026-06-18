<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkCustomer();

$user_id = $_SESSION['user_id'];
$policy_id = isset($_GET['policy_id']) ? intval($_GET['policy_id']) : 0;

$success_msg = "";
$error_msg = "";

// Verify the policy belongs to the logged-in customer and is Pending payment
$stmt = mysqli_prepare($conn, "SELECT id, policy_name, premium_amount, status FROM policies WHERE id = ? AND customer_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $policy_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$policy = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$policy) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardholder = trim($_POST['cardholder']);
    $card_number = trim($_POST['card_number']);
    $expiry = trim($_POST['expiry']);
    $cvv = trim($_POST['cvv']);
    
    // Simple verification
    if (empty($cardholder) || strlen($card_number) < 16 || empty($expiry) || strlen($cvv) < 3) {
        $error_msg = "Please verify your credit card details. Card number must be 16 digits and CVV must be 3 digits.";
    } else {
        // Generate a random transaction ID
        $transaction_id = strtoupper(bin2hex(random_bytes(8)));
        $amount = $policy['premium_amount'];
        $payment_method = "Card";
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Insert into payments
            $stmt_pay = mysqli_prepare($conn, "INSERT INTO payments (policy_id, amount, payment_date, payment_method, transaction_id) VALUES (?, ?, CURDATE(), ?, ?)");
            mysqli_stmt_bind_param($stmt_pay, "idss", $policy_id, $amount, $payment_method, $transaction_id);
            mysqli_stmt_execute($stmt_pay);
            mysqli_stmt_close($stmt_pay);
            
            // 2. Update policy status to 'Active'
            $stmt_pol = mysqli_prepare($conn, "UPDATE policies SET status = 'Active' WHERE id = ? AND customer_id = ?");
            mysqli_stmt_bind_param($stmt_pol, "ii", $policy_id, $user_id);
            mysqli_stmt_execute($stmt_pol);
            mysqli_stmt_close($stmt_pol);
            
            mysqli_commit($conn);
            
            $success_msg = "Payment processed successfully! Your policy status is now Active. Transaction ID: " . $transaction_id;
            // Reload policy details to update UI state
            $policy['status'] = 'Active';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_msg = "Payment failed to log. Please try again.";
        }
    }
}

include_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="mb-4">
            <a href="dashboard.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Cancel and Return</a>
        </div>
        
        <div class="glass-card p-5 animate-fade-in-up">
            <h3 class="fw-bold mb-3"><i class="bi bi-credit-card-2-front-fill text-primary"></i> Premium <span class="gradient-text">Checkout</span></h3>
            <p class="text-secondary mb-4">Complete payment of due premiums to activate your coverage benefits.</p>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                    <div>
                        <strong>Success!</strong> <?php echo $success_msg; ?><br>
                        <a href="dashboard.php" class="alert-link fw-bold d-block mt-2">Go to My Dashboard &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div><?php echo $error_msg; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($policy['status'] === 'Pending'): ?>
                <!-- Invoice Info Box -->
                <div class="p-3 bg-light rounded mb-4 text-dark" style="border-left: 5px solid var(--accent-primary);">
                    <div class="row">
                        <div class="col-7">
                            <small class="text-muted d-block">POLICY PLAN</small>
                            <strong><?php echo htmlspecialchars($policy['policy_name']); ?></strong>
                        </div>
                        <div class="col-5 text-end">
                            <small class="text-muted d-block">AMOUNT DUE</small>
                            <strong class="text-success fs-5">$<?php echo number_format($policy['premium_amount'], 2); ?></strong>
                        </div>
                    </div>
                </div>

                <form method="POST" action="make_payment.php?policy_id=<?php echo $policy_id; ?>" id="payment-form">
                    <div class="mb-3">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control" name="cardholder" placeholder="John Doe" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                            <input type="text" class="form-control" name="card_number" id="card_number" maxlength="16" placeholder="4111 2222 3333 4444" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label">Expiration (MM/YY)</label>
                            <input type="text" class="form-control" name="expiry" placeholder="12/28" maxlength="5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">CVV</label>
                            <input type="password" class="form-control" name="cvv" id="cvv" maxlength="3" placeholder="•••" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn gradient-btn w-100 py-3"><i class="bi bi-shield-lock"></i> Submit Payment ($<?php echo number_format($policy['premium_amount'], 2); ?>)</button>
                </form>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-check-circle-fill text-success fs-1 mb-3"></i>
                    <h5>This policy has already been paid and is Active.</h5>
                    <p class="text-secondary text-sm">No action is required. Thank you for securing your coverage with InsureEasy.</p>
                    <a href="dashboard.php" class="btn gradient-btn px-4 py-2 mt-2">Return to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
