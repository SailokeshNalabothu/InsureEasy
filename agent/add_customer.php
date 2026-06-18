<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAgent();

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_id = $_SESSION['agent_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error_msg = "Please fill in all mandatory fields (Name, Email, Phone, Password).";
    } else {
        // Check email uniqueness
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error_msg = "A customer with this email address already exists.";
        } else {
            // Handle Profile Photo Upload
            $profile_photo_name = NULL;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['profile_photo']['tmp_name'];
                $file_orig_name = $_FILES['profile_photo']['name'];
                $file_ext = strtolower(pathinfo($file_orig_name, PATHINFO_EXTENSION));
                $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                
                if (in_array($file_ext, $allowed_exts)) {
                    $upload_dir = '../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $profile_photo_name = uniqid('profile_', true) . '.' . $file_ext;
                    $dest_path = $upload_dir . $profile_photo_name;
                    
                    if (!move_uploaded_file($file_tmp, $dest_path)) {
                        $profile_photo_name = NULL; // upload error fallback
                    }
                } else {
                    $error_msg = "Invalid profile image type. Allowed: JPG, PNG, GIF, WEBP.";
                }
            }

            if (empty($error_msg)) {
                // Hash Password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt_insert = mysqli_prepare($conn, "INSERT INTO customers (agent_id, name, email, phone, address, password, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_insert, "issssss", $agent_id, $name, $email, $phone, $address, $hashed_password, $profile_photo_name);
                
                if (mysqli_stmt_execute($stmt_insert)) {
                    $success_msg = "Customer created successfully! The client can now log in using their email and password.";
                } else {
                    $error_msg = "Failed to insert customer record.";
                }
                mysqli_stmt_close($stmt_insert);
            }
        }
        mysqli_stmt_close($stmt_check);
    }
}

include_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="mb-4">
            <a href="dashboard.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="glass-card p-5 animate-fade-in-up">
            <h3 class="fw-bold mb-3"><i class="bi bi-person-plus-fill text-primary"></i> Create Customer <span class="gradient-text">Account</span></h3>
            <p class="text-secondary mb-4">Register client demographic details, profile photos, and assign secure login passwords.</p>

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

            <form method="POST" action="add_customer.php" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" class="form-control" name="name" placeholder="Client Name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" placeholder="client@domain.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" name="phone" placeholder="e.g. 9876543210" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Portal Access Password *</label>
                        <input type="password" class="form-control" name="password" placeholder="Assign password" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Home / Office Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="Enter full address details"></textarea>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Profile Image Upload (Optional)</label>
                        <input type="file" class="form-control" name="profile_photo" accept="image/*">
                        <small class="text-muted text-xs">Supports JPG, PNG, GIF, or WEBP formats.</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn gradient-btn px-4 py-2"><i class="bi bi-person-check"></i> Register Customer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
