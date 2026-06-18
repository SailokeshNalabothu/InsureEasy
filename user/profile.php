<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkCustomer();

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Fetch current customer details
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, address, profile_photo FROM customers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    
    // Check email uniqueness excluding self
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $error_msg = "This email address is already in use by another account.";
    } else {
        $profile_photo_name = $customer['profile_photo'];
        
        // Handle Photo Upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_photo']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            if (in_array($file_ext, $allowed_exts)) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Remove old photo if exists
                if ($customer['profile_photo'] && file_exists($upload_dir . $customer['profile_photo'])) {
                    @unlink($upload_dir . $customer['profile_photo']);
                }
                
                $profile_photo_name = uniqid('profile_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $profile_photo_name;
                move_uploaded_file($file_tmp, $dest_path);
            } else {
                $error_msg = "Invalid profile photo format. Allowed: JPG, PNG, GIF, WEBP.";
            }
        }
        
        if (empty($error_msg)) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_up = mysqli_prepare($conn, "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, password = ?, profile_photo = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_up, "ssssssi", $name, $email, $phone, $address, $hashed_password, $profile_photo_name, $user_id);
            } else {
                $stmt_up = mysqli_prepare($conn, "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, profile_photo = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_up, "sssssi", $name, $email, $phone, $address, $profile_photo_name, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt_up)) {
                $success_msg = "Profile updated successfully!";
                
                // Fetch updated values
                $_SESSION['user_name'] = $name;
                $customer['name'] = $name;
                $customer['email'] = $email;
                $customer['phone'] = $phone;
                $customer['address'] = $address;
                $customer['profile_photo'] = $profile_photo_name;
            } else {
                $error_msg = "Failed to update profile details.";
            }
            mysqli_stmt_close($stmt_up);
        }
    }
    mysqli_stmt_close($stmt_check);
}

include_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="mb-4">
            <a href="dashboard.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="glass-card p-5 animate-fade-in-up">
            <h3 class="fw-bold mb-3"><i class="bi bi-person-bounding-box text-primary"></i> Edit Profile <span class="gradient-text">Settings</span></h3>
            <p class="text-secondary mb-4">Manage your personal details, reset account passwords, and set avatars.</p>

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

            <form method="POST" action="profile.php" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-12 text-center mb-3">
                        <div class="profile-photo-container">
                            <?php if (!empty($customer['profile_photo'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($customer['profile_photo']); ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="w-100 h-100 bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-2">
                                    <?php echo strtoupper(substr($customer['name'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <label class="form-label mt-2">Change Profile Photo</label>
                        <input type="file" class="form-control w-50 mx-auto" name="profile_photo" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Change Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="password" placeholder="New Password">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Postal Address</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>
                    <div class="col-12 mt-4 text-center">
                        <button type="submit" class="btn gradient-btn px-5 py-2"><i class="bi bi-save"></i> Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
