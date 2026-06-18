<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAdmin();

$success_msg = "";
$error_msg = "";

// Handle approval action
if (isset($_GET['approve'])) {
    $agent_id = intval($_GET['approve']);
    $stmt = mysqli_prepare($conn, "UPDATE agents SET status='Approved' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $agent_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Agent approved successfully!";
    } else {
        $error_msg = "Failed to approve agent.";
    }
    mysqli_stmt_close($stmt);
}

// Handle delete/reject action
if (isset($_GET['delete'])) {
    $agent_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM agents WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $agent_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Agent registration deleted successfully.";
    } else {
        $error_msg = "Failed to delete agent.";
    }
    mysqli_stmt_close($stmt);
}

// Fetch totals
$total_agents = 0;
$total_customers = 0;
$total_policies = 0;
$active_policies = 0;
$expired_policies = 0;
$pending_policies = 0;
$total_revenue = 0.00;

// Total Agents
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM agents");
if ($row = mysqli_fetch_assoc($res)) $total_agents = $row['cnt'];

// Total Customers
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM customers");
if ($row = mysqli_fetch_assoc($res)) $total_customers = $row['cnt'];

// Policy metrics
$res = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status='Expired' THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending
    FROM policies");
if ($row = mysqli_fetch_assoc($res)) {
    $total_policies = $row['total'];
    $active_policies = $row['active'] ? $row['active'] : 0;
    $expired_policies = $row['expired'] ? $row['expired'] : 0;
    $pending_policies = $row['pending'] ? $row['pending'] : 0;
}

// Total Revenue (completed payments)
$res = mysqli_query($conn, "SELECT SUM(amount) as revenue FROM payments");
if ($row = mysqli_fetch_assoc($res)) {
    $total_revenue = $row['revenue'] ? floatval($row['revenue']) : 0.00;
}

// Fetch Pending Agents for Approval list
$pending_agents_query = mysqli_query($conn, "SELECT id, name, email, phone, created_at FROM agents WHERE status = 'Pending' ORDER BY created_at DESC");

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="fw-bold mb-1">Admin <span class="gradient-text">Dashboard</span></h1>
        <p class="text-secondary">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>! Oversee systems activity and approvals.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="reports.php" class="btn gradient-btn"><i class="bi bi-file-earmark-pdf"></i> Generate System Report</a>
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

<!-- Analytics Grid -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Total Agents</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0"><?php echo $total_agents; ?></h2>
                <div class="fs-1 text-primary"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Total Customers</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0"><?php echo $total_customers; ?></h2>
                <div class="fs-1 text-info"><i class="bi bi-person-fill-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Total Policies</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0"><?php echo $total_policies; ?></h2>
                <div class="fs-1 text-warning"><i class="bi bi-file-earmark-lock-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 glass-card metric-card h-100">
            <span class="text-muted d-block mb-1 text-uppercase fw-semibold" style="font-size:0.75rem;">Total Revenue</span>
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="fw-bold mb-0 text-success">$<?php echo number_format($total_revenue, 2); ?></h2>
                <div class="fs-1 text-success"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Chart visualization -->
    <div class="col-lg-5">
        <div class="p-4 glass-card h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-pie-chart-fill"></i> Policies Distribution</h5>
            <div style="max-height: 250px; position: relative;" class="d-flex justify-content-center">
                <canvas id="policiesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Pending Approvals list -->
    <div class="col-lg-7">
        <div class="p-4 glass-card h-100">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-plus-fill"></i> Pending Agent Approvals</h5>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Signed Up</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($pending_agents_query) > 0): ?>
                            <?php while ($agent = mysqli_fetch_assoc($pending_agents_query)): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($agent['name']); ?></div>
                                    </td>
                                    <td>
                                        <small class="d-block text-secondary"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($agent['email']); ?></small>
                                        <small class="d-block text-secondary"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($agent['phone']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('Y-m-d', strtotime($agent['created_at'])); ?></small>
                                    </td>
                                    <td class="text-end">
                                        <a href="dashboard.php?approve=<?php echo $agent['id']; ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Approve this agent?');"><i class="bi bi-check-lg"></i> Approve</a>
                                        <a href="dashboard.php?delete=<?php echo $agent['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject and delete this agent registration?');"><i class="bi bi-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-emoji-smile fs-3 d-block mb-2"></i> No pending agents for approval.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Injection -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('policiesChart').getContext('2d');
    
    const activeCount = <?php echo $active_policies; ?>;
    const expiredCount = <?php echo $expired_policies; ?>;
    const pendingCount = <?php echo $pending_policies; ?>;

    // Check if there is data to display
    const totalCount = activeCount + expiredCount + pendingCount;
    
    const dataValues = totalCount > 0 ? [activeCount, expiredCount, pendingCount] : [1, 1, 1]; // mock distribution if empty
    const labelSet = totalCount > 0 ? ['Active', 'Expired', 'Pending'] : ['No Active Policies', 'No Expired Policies', 'No Pending Policies'];
    const colorSet = totalCount > 0 ? ['#10b981', '#ef4444', '#f59e0b'] : ['#e2e8f0', '#cbd5e1', '#94a3b8'];

    const myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labelSet,
            datasets: [{
                data: dataValues,
                backgroundColor: colorSet,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#0f172a',
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // Listen to theme change to update chart colors dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-theme') {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                myChart.options.plugins.legend.labels.color = currentTheme === 'dark' ? '#f8fafc' : '#0f172a';
                myChart.update();
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
});
</script>

<?php
include_once '../includes/footer.php';
?>
