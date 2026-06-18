<?php
require_once 'includes/db.php';

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error_msg = "Please fill all fields.";
    } else {
        // Check if email already registered in agents or admins
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM agents WHERE email = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error_msg = "This email is already registered.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = "Pending";
            
            $stmt_insert = mysqli_prepare($conn, "INSERT INTO agents (name, email, phone, password, status) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssss", $name, $email, $phone, $hashed_password, $status);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $success_msg = "Agent registration submitted successfully! Your account status is now Pending approval by the Administrator.";
            } else {
                $error_msg = "Registration failed. Please try again.";
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    }
}

include_once 'includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-6">
        <div class="glass-card p-5 animate-fade-in-up">
            <h2 class="fw-bold mb-3 text-center">Join As An <span class="gradient-text">Agent</span></h2>
            <p class="text-secondary text-center mb-4">Start registering policyholders and managing corporate insurance covers.</p>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo $success_msg; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo $error_msg; ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="name" placeholder="John Doe" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" placeholder="john@example.com" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="tel" class="form-control" name="phone" placeholder="9876543210" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn gradient-btn w-100 py-3 mb-3">Submit Registration</button>
            </form>
            
            <div class="text-center mt-3">
                <span class="text-secondary">Already registered?</span> <a href="login.php" class="text-decoration-none fw-bold">Login here</a>
            </div>
        </div>
    </div>
</div>

<?php
include_once 'includes/footer.php';
?>
