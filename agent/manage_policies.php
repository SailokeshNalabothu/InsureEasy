<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAgent();

$agent_id = $_SESSION['agent_id'];
$success_msg = "";
$error_msg = "";

$edit_mode = false;
$edit_policy = null;

// Handle Delete Policy
if (isset($_GET['delete'])) {
    $pol_id = intval($_GET['delete']);
    // Verify the policy belongs to a customer of this agent
    $stmt_del = mysqli_prepare($conn, "DELETE p FROM policies p JOIN customers c ON p.customer_id = c.id WHERE p.id = ? AND c.agent_id = ?");
    mysqli_stmt_bind_param($stmt_del, "ii", $pol_id, $agent_id);
    if (mysqli_stmt_execute($stmt_del)) {
        $success_msg = "Policy removed from the system successfully.";
    } else {
        $error_msg = "Failed to delete policy.";
    }
    mysqli_stmt_close($stmt_del);
}

// Handle Edit Mode transition
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = mysqli_prepare($conn, "SELECT p.id, p.policy_name, p.policy_type, p.premium_amount, p.start_date, p.end_date, p.status, c.name as customer_name FROM policies p JOIN customers c ON p.customer_id = c.id WHERE p.id = ? AND c.agent_id = ?");
    mysqli_stmt_bind_param($stmt_edit, "ii", $edit_id, $agent_id);
    mysqli_stmt_execute($stmt_edit);
    $res_edit = mysqli_stmt_get_result($stmt_edit);
    if ($row_edit = mysqli_fetch_assoc($res_edit)) {
        $edit_mode = true;
        $edit_policy = $row_edit;
    }
    mysqli_stmt_close($stmt_edit);
}

// Handle Update Policy Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_policy'])) {
    $pol_id = intval($_POST['pol_id']);
    $policy_name = trim($_POST['policy_name']);
    $policy_type = $_POST['policy_type'];
    $premium_amount = floatval($_POST['premium_amount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    if (empty($policy_name) || empty($policy_type) || $premium_amount <= 0 || empty($start_date) || empty($end_date)) {
        $error_msg = "Please fill in all details correctly.";
    } else {
        $stmt_up = mysqli_prepare($conn, "UPDATE policies p JOIN customers c ON p.customer_id = c.id SET p.policy_name = ?, p.policy_type = ?, p.premium_amount = ?, p.start_date = ?, p.end_date = ?, p.status = ? WHERE p.id = ? AND c.agent_id = ?");
        mysqli_stmt_bind_param($stmt_up, "ssdsdsii", $policy_name, $policy_type, $premium_amount, $start_date, $end_date, $status, $pol_id, $agent_id);
        
        if (mysqli_stmt_execute($stmt_up)) {
            $success_msg = "Policy details updated successfully!";
            $edit_mode = false;
        } else {
            $error_msg = "Failed to update policy parameters.";
        }
        mysqli_stmt_close($stmt_up);
    }
}

// Search and filters
$search = "";
if (isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
}

// Fetch all policies under this agent's clients
if (!empty($search)) {
    $stmt_fetch = mysqli_prepare($conn, "SELECT p.id, p.policy_name, p.policy_type, p.premium_amount, p.start_date, p.end_date, p.status, c.name as customer_name, c.id as customer_id FROM policies p JOIN customers c ON p.customer_id = c.id WHERE c.agent_id = ? AND (p.policy_name LIKE ? OR c.name LIKE ?) ORDER BY p.id DESC");
    $param_search = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt_fetch, "iss", $agent_id, $param_search, $param_search);
    mysqli_stmt_execute($stmt_fetch);
    $policies_res = mysqli_stmt_get_result($stmt_fetch);
} else {
    $stmt_fetch = mysqli_prepare($conn, "SELECT p.id, p.policy_name, p.policy_type, p.premium_amount, p.start_date, p.end_date, p.status, c.name as customer_name, c.id as customer_id FROM policies p JOIN customers c ON p.customer_id = c.id WHERE c.agent_id = ? ORDER BY p.id DESC");
    mysqli_stmt_bind_param($stmt_fetch, "i", $agent_id);
    mysqli_stmt_execute($stmt_fetch);
    $policies_res = mysqli_stmt_get_result($stmt_fetch);
}
mysqli_stmt_close($stmt_fetch);

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="fw-bold mb-1"><i class="bi bi-file-earmark-lock-fill text-primary"></i> Manage <span class="gradient-text">Policies</span></h2>
        <p class="text-secondary">View contracts, check premium collection, and review expiration periods.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <?php if (!$edit_mode): ?>
            <form method="POST" action="manage_policies.php" class="d-flex gap-2 justify-content-md-end">
                <input type="text" class="form-control w-50" name="search_query" placeholder="Policy name or Customer..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn gradient-btn"><i class="bi bi-search"></i> Search</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_policies.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

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

<?php if ($edit_mode && $edit_policy): ?>
    <!-- EDIT VIEW -->
    <div class="glass-card p-5 animate-fade-in-up">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="bi bi-pencil-square text-warning"></i> Edit Policy (Client: <?php echo htmlspecialchars($edit_policy['customer_name']); ?>)</h4>
            <a href="manage_policies.php" class="btn btn-outline-secondary btn-sm">Cancel Edit</a>
        </div>
        
        <form method="POST" action="manage_policies.php">
            <input type="hidden" name="pol_id" value="<?php echo $edit_policy['id']; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Policy Custom Name</label>
                    <input type="text" class="form-control" name="policy_name" value="<?php echo htmlspecialchars($edit_policy['policy_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category Type</label>
                    <select class="form-select" name="policy_type" required>
                        <option value="Health Insurance" <?php echo ($edit_policy['policy_type'] === 'Health Insurance') ? 'selected' : ''; ?>>Health Insurance</option>
                        <option value="Auto Insurance" <?php echo ($edit_policy['policy_type'] === 'Auto Insurance') ? 'selected' : ''; ?>>Auto Insurance</option>
                        <option value="Home Insurance" <?php echo ($edit_policy['policy_type'] === 'Home Insurance') ? 'selected' : ''; ?>>Home Insurance</option>
                        <option value="Life Insurance" <?php echo ($edit_policy['policy_type'] === 'Life Insurance') ? 'selected' : ''; ?>>Life Insurance</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Premium Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" name="premium_amount" value="<?php echo htmlspecialchars($edit_policy['premium_amount']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Policy Coverage Status</label>
                    <select class="form-select" name="status" required>
                        <option value="Active" <?php echo ($edit_policy['status'] === 'Active') ? 'selected' : ''; ?>>Active (Cover Enforced)</option>
                        <option value="Pending" <?php echo ($edit_policy['status'] === 'Pending') ? 'selected' : ''; ?>>Pending (Awaiting Premium)</option>
                        <option value="Expired" <?php echo ($edit_policy['status'] === 'Expired') ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $edit_policy['start_date']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $edit_policy['end_date']; ?>" required>
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" name="update_policy" class="btn btn-warning px-4 py-2"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- LIST VIEW -->
    <div class="glass-card p-4">
        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Policy ID</th>
                        <th>Customer</th>
                        <th>Policy Name</th>
                        <th>Type</th>
                        <th>Premium</th>
                        <th>Status</th>
                        <th>Duration (Start - End)</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($policies_res) > 0): ?>
                        <?php while ($pol = mysqli_fetch_assoc($policies_res)): ?>
                            <tr>
                                <td><code>#POL-<?php echo str_pad($pol['id'], 5, '0', STR_PAD_LEFT); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($pol['customer_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pol['policy_name']); ?></td>
                                <td><small class="text-secondary"><?php echo htmlspecialchars($pol['policy_type']); ?></small></td>
                                <td class="fw-bold text-dark">$<?php echo number_format($pol['premium_amount'], 2); ?></td>
                                <td>
                                    <?php if ($pol['status'] === 'Active'): ?>
                                        <span class="badge-custom badge-active"><i class="bi bi-shield-fill-check"></i> Active</span>
                                    <?php elseif ($pol['status'] === 'Expired'): ?>
                                        <span class="badge-custom badge-expired"><i class="bi bi-shield-fill-x"></i> Expired</span>
                                    <?php else: ?>
                                        <span class="badge-custom badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="d-block text-secondary">Start: <?php echo htmlspecialchars($pol['start_date']); ?></small>
                                    <small class="d-block text-secondary">End: <?php echo htmlspecialchars($pol['end_date']); ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="send_sms.php?customer_id=<?php echo $pol['customer_id']; ?>&policy_id=<?php echo $pol['id']; ?>" class="btn btn-sm btn-outline-danger" title="Send SMS Expiry Alert"><i class="bi bi-chat-dots"></i> Alert</a>
                                        <a href="manage_policies.php?edit=<?php echo $pol['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit Policy Details"><i class="bi bi-pencil-square"></i></a>
                                        <a href="manage_policies.php?delete=<?php echo $pol['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Purge policy coverage registry? This cannot be undone.');" title="Delete Policy"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-lock fs-2 d-block mb-2"></i> No policy contracts registered yet.
                                <a href="add_policy.php" class="btn btn-link fw-bold p-0">Issue your first policy</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
include_once '../includes/footer.php';
?>
