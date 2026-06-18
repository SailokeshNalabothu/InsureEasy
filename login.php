<?php
require_once 'includes/db.php';

$error_msg = "";
$active_tab = "customer"; // default tab

if (isset($_GET['error']) && $_GET['error'] === 'unapproved') {
    $error_msg = "Your Agent registration is pending Administrator approval.";
    $active_tab = "agent";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role']; // 'admin', 'agent', 'customer'
    $identifier = trim($_POST['identifier']); // username or email
    $password = $_POST['password'];
    $active_tab = $role;

    if (empty($identifier) || empty($password)) {
        $error_msg = "Please fill in all credentials.";
    } else {
        if ($role === 'admin') {
            // Admin authentication
            $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM admin WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $identifier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    $error_msg = "Invalid Admin credentials.";
                }
            } else {
                $error_msg = "Invalid Admin credentials.";
            }
            mysqli_stmt_close($stmt);

        } elseif ($role === 'agent') {
            // Agent authentication
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, status FROM agents WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $identifier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                if ($row['status'] !== 'Approved') {
                    $error_msg = "Your agent registration is still pending approval.";
                } elseif (password_verify($password, $row['password'])) {
                    $_SESSION['agent_id'] = $row['id'];
                    $_SESSION['agent_name'] = $row['name'];
                    header("Location: agent/dashboard.php");
                    exit();
                } else {
                    $error_msg = "Invalid Agent credentials.";
                }
            } else {
                $error_msg = "Invalid Agent credentials.";
            }
            mysqli_stmt_close($stmt);

        } elseif ($role === 'customer') {
            // Customer authentication
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, password FROM customers WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $identifier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    header("Location: user/dashboard.php");
                    exit();
                } else {
                    $error_msg = "Invalid Customer credentials.";
                }
            } else {
                $error_msg = "Invalid Customer credentials.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

include_once 'includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-6">
        <div class="glass-card p-5 animate-fade-in-up">
            <h2 class="fw-bold mb-3 text-center">Access <span class="gradient-text">InsureEasy</span></h2>
            <p class="text-secondary text-center mb-4">Select your portal role below to sign in.</p>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo $error_msg; ?></div>
                </div>
            <?php endif; ?>

            <!-- Switcher Tabs -->
            <div class="auth-tabs">
                <div class="auth-tab <?php echo ($active_tab === 'customer') ? 'active' : ''; ?>" onclick="selectRole('customer')">Customer</div>
                <div class="auth-tab <?php echo ($active_tab === 'agent') ? 'active' : ''; ?>" onclick="selectRole('agent')">Agent</div>
                <div class="auth-tab <?php echo ($active_tab === 'admin') ? 'active' : ''; ?>" onclick="selectRole('admin')">Admin</div>
            </div>

            <form method="POST" action="login.php" id="loginForm">
                <input type="hidden" name="role" id="formRole" value="<?php echo $active_tab; ?>">
                
                <div class="mb-3">
                    <label class="form-label" id="identifierLabel">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text" id="identifierIcon"><i class="bi bi-envelope"></i></span>
                        <input type="text" class="form-control" name="identifier" id="identifierInput" placeholder="name@domain.com" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn gradient-btn w-100 py-3 mb-2">Secure Sign In</button>
            </form>

            <div class="text-center mt-4" id="agentRegisterLink" style="display: <?php echo ($active_tab === 'agent') ? 'block' : 'none'; ?>;">
                <span class="text-secondary">New agent?</span> <a href="register.php" class="text-decoration-none fw-bold">Submit your application</a>
            </div>
        </div>
    </div>
</div>

<script>
function selectRole(role) {
    document.getElementById('formRole').value = role;
    
    // Update tabs active state
    const tabs = document.querySelectorAll('.auth-tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Set clicked tab active
    event.currentTarget.classList.add('active');
    
    // Toggle fields based on role
    const label = document.getElementById('identifierLabel');
    const icon = document.getElementById('identifierIcon');
    const input = document.getElementById('identifierInput');
    const agentRegister = document.getElementById('agentRegisterLink');

    if (role === 'admin') {
        label.innerText = 'Administrator Username';
        icon.innerHTML = '<i class="bi bi-person-badge"></i>';
        input.placeholder = 'e.g. admin';
        agentRegister.style.display = 'none';
    } else if (role === 'agent') {
        label.innerText = 'Agent Email Address';
        icon.innerHTML = '<i class="bi bi-envelope"></i>';
        input.placeholder = 'e.g. agent@insureeasy.com';
        agentRegister.style.display = 'block';
    } else {
        label.innerText = 'Customer Email Address';
        icon.innerHTML = '<i class="bi bi-envelope"></i>';
        input.placeholder = 'e.g. customer@domain.com';
        agentRegister.style.display = 'none';
    }
}
// Initial toggle styling based on POST reload state
selectRole('<?php echo $active_tab; ?>');
</script>

<?php
include_once 'includes/footer.php';
?>
