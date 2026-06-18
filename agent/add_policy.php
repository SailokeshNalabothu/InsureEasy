<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAgent();

$agent_id = $_SESSION['agent_id'];
$success_msg = "";
$error_msg = "";

// Pre-selected customer from query param
$pre_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $policy_name = trim($_POST['policy_name']);
    $policy_type = $_POST['policy_type'];
    $premium_amount = floatval($_POST['premium_amount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status']; // 'Active', 'Pending', 'Expired'

    if (empty($policy_name) || empty($policy_type) || $premium_amount <= 0 || empty($start_date) || empty($end_date)) {
        $error_msg = "Please fill in all policy parameters correctly.";
    } else {
        // Insert policy record
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO policies (customer_id, policy_name, policy_type, premium_amount, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins, "issdsss", $customer_id, $policy_name, $policy_type, $premium_amount, $start_date, $end_date, $status);
        
        if (mysqli_stmt_execute($stmt_ins)) {
            $success_msg = "Policy created successfully!";
        } else {
            $error_msg = "Failed to create policy.";
        }
        mysqli_stmt_close($stmt_ins);
    }
}

// Fetch all customers under this agent for selection list
$customers_stmt = mysqli_prepare($conn, "SELECT id, name, email FROM customers WHERE agent_id = ? ORDER BY name ASC");
mysqli_stmt_bind_param($customers_stmt, "i", $agent_id);
mysqli_stmt_execute($customers_stmt);
$customers_res = mysqli_stmt_get_result($customers_stmt);
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="mb-4">
            <a href="manage_customers.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Customers</a>
        </div>
        
        <div class="glass-card p-5 animate-fade-in-up">
            <h3 class="fw-bold mb-3"><i class="bi bi-file-earmark-plus-fill text-primary"></i> Issue New <span class="gradient-text">Policy</span></h3>
            <p class="text-secondary mb-4">Establish coverage details, premiums, and active term durations for registered clients.</p>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_policy.php">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Select Customer *</label>
                        <select class="form-select" name="customer_id" required>
                            <option value="">-- Choose client account --</option>
                            <?php if (mysqli_num_rows($customers_res) > 0): ?>
                                <?php while ($cust = mysqli_fetch_assoc($customers_res)): ?>
                                    <option value="<?php echo $cust['id']; ?>" <?php echo ($pre_customer_id == $cust['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['email']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No clients registered. Create a customer first.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Policy Custom Name *</label>
                        <input type="text" class="form-control" name="policy_name" placeholder="e.g. Life Care Platinum Plus" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Policy Type Category *</label>
                        <select class="form-select" name="policy_type" required>
                            <option value="Health Insurance">Health Insurance</option>
                            <option value="Auto Insurance">Auto Insurance</option>
                            <option value="Home Insurance">Home Insurance</option>
                            <option value="Life Insurance">Life Insurance</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Premium Amount ($) *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" name="premium_amount" placeholder="500.00" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Initial Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="Pending" selected>Pending (Awaiting Premium Payment)</option>
                            <option value="Active">Active (Cover Enforced)</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Coverage Start Date *</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Coverage End/Expiry Date *</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn gradient-btn px-4 py-2"><i class="bi bi-file-earmark-plus"></i> Write Policy</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($customers_stmt);
include_once '../includes/footer.php';
?>
