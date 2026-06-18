<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAdmin();

$success_msg = "";
$error_msg = "";

// Actions (approve/delete)
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $stmt = mysqli_prepare($conn, "UPDATE agents SET status='Approved' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Agent approved successfully!";
    } else {
        $error_msg = "Failed to update agent status.";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM agents WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Agent record removed from system.";
    } else {
        $error_msg = "Failed to delete agent.";
    }
    mysqli_stmt_close($stmt);
}

// Search filter
$search_query = "";
if (isset($_POST['search'])) {
    $search_query = trim($_POST['search']);
}

// Fetch agents
if (!empty($search_query)) {
    $stmt_fetch = mysqli_prepare($conn, "SELECT id, name, email, phone, status, created_at FROM agents WHERE name LIKE ? OR email LIKE ? ORDER BY name ASC");
    $param_search = "%" . $search_query . "%";
    mysqli_stmt_bind_param($stmt_fetch, "ss", $param_search, $param_search);
    mysqli_stmt_execute($stmt_fetch);
    $agents_res = mysqli_stmt_get_result($stmt_fetch);
} else {
    $agents_res = mysqli_query($conn, "SELECT id, name, email, phone, status, created_at FROM agents ORDER BY name ASC");
}

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary"></i> Manage <span class="gradient-text">Agents</span></h2>
        <p class="text-secondary">Track registered agents, approve login applications, and audit contact metrics.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <form method="POST" action="manage_agents.php" class="d-flex gap-2">
            <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn gradient-btn"><i class="bi bi-search"></i> Search</button>
            <?php if (!empty($search_query)): ?>
                <a href="manage_agents.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
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

<div class="glass-card p-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Agent ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($agents_res) > 0): ?>
                    <?php while ($agent = mysqli_fetch_assoc($agents_res)): ?>
                        <tr>
                            <td><code>#AG-<?php echo str_pad($agent['id'], 4, '0', STR_PAD_LEFT); ?></code></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($agent['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($agent['email']); ?></td>
                            <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                            <td>
                                <?php if ($agent['status'] === 'Approved'): ?>
                                    <span class="badge-custom badge-active"><i class="bi bi-check-circle"></i> Approved</span>
                                <?php else: ?>
                                    <span class="badge-custom badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($agent['created_at'])); ?></small></td>
                            <td class="text-end">
                                <?php if ($agent['status'] === 'Pending'): ?>
                                    <a href="manage_agents.php?approve=<?php echo $agent['id']; ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Approve this agent for login access?');"><i class="bi bi-check-lg"></i> Approve</a>
                                <?php endif; ?>
                                <a href="manage_agents.php?delete=<?php echo $agent['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove agent profile from system? This action is permanent.');"><i class="bi bi-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-emoji-frown fs-2 d-block mb-2"></i> No agents registered yet matching this search.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
