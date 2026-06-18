<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAgent();

$agent_id = $_SESSION['agent_id'];
$success_msg = "";
$error_msg = "";

$edit_mode = false;
$edit_customer = null;

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $cust_id = intval($_GET['delete']);
    // Verify customer belongs to this agent
    $stmt_del = mysqli_prepare($conn, "DELETE FROM customers WHERE id = ? AND agent_id = ?");
    mysqli_stmt_bind_param($stmt_del, "ii", $cust_id, $agent_id);
    if (mysqli_stmt_execute($stmt_del)) {
        $success_msg = "Customer profile deleted successfully.";
    } else {
        $error_msg = "Failed to delete customer profile.";
    }
    mysqli_stmt_close($stmt_del);
}

// Handle entering Edit Mode
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = mysqli_prepare($conn, "SELECT id, name, email, phone, address, profile_photo FROM customers WHERE id = ? AND agent_id = ?");
    mysqli_stmt_bind_param($stmt_edit, "ii", $edit_id, $agent_id);
    mysqli_stmt_execute($stmt_edit);
    $res_edit = mysqli_stmt_get_result($stmt_edit);
    if ($row_edit = mysqli_fetch_assoc($res_edit)) {
        $edit_mode = true;
        $edit_customer = $row_edit;
    }
    mysqli_stmt_close($stmt_edit);
}

// Handle Update Profile Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $cust_id = intval($_POST['cust_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    
    // Check email uniqueness excluding this customer
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($stmt_check, "si", $email, $cust_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $error_msg = "This email address is already in use by another customer.";
    } else {
        // Fetch existing photo
        $stmt_photo = mysqli_prepare($conn, "SELECT profile_photo FROM customers WHERE id = ?");
        mysqli_stmt_bind_param($stmt_photo, "i", $cust_id);
        mysqli_stmt_execute($stmt_photo);
        $res_photo = mysqli_stmt_get_result($stmt_photo);
        $row_photo = mysqli_fetch_assoc($res_photo);
        $profile_photo_name = $row_photo['profile_photo'];
        mysqli_stmt_close($stmt_photo);

        // Upload photo check
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_photo']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            if (in_array($file_ext, $allowed_exts)) {
                $upload_dir = '../uploads/';
                $profile_photo_name = uniqid('profile_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $profile_photo_name;
                move_uploaded_file($file_tmp, $dest_path);
            } else {
                $error_msg = "Invalid profile photo format.";
            }
        }

        if (empty($error_msg)) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_up = mysqli_prepare($conn, "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, password = ?, profile_photo = ? WHERE id = ? AND agent_id = ?");
                mysqli_stmt_bind_param($stmt_up, "ssssssii", $name, $email, $phone, $address, $hashed_password, $profile_photo_name, $cust_id, $agent_id);
            } else {
                $stmt_up = mysqli_prepare($conn, "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, profile_photo = ? WHERE id = ? AND agent_id = ?");
                mysqli_stmt_bind_param($stmt_up, "sssssiii", $name, $email, $phone, $address, $profile_photo_name, $cust_id, $agent_id);
            }
            
            if (mysqli_stmt_execute($stmt_up)) {
                $success_msg = "Customer details updated successfully!";
                $edit_mode = false;
            } else {
                $error_msg = "Failed to update customer details.";
            }
            mysqli_stmt_close($stmt_up);
        }
    }
    mysqli_stmt_close($stmt_check);
}

// Search queries
$search = "";
if (isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
}

// Fetch all customers under this agent
if (!empty($search)) {
    $stmt_fetch = mysqli_prepare($conn, "SELECT id, name, email, phone, address, profile_photo, created_at FROM customers WHERE agent_id = ? AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY name ASC");
    $param_search = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt_fetch, "isss", $agent_id, $param_search, $param_search, $param_search);
    mysqli_stmt_execute($stmt_fetch);
    $customers_res = mysqli_stmt_get_result($stmt_fetch);
} else {
    $stmt_fetch = mysqli_prepare($conn, "SELECT id, name, email, phone, address, profile_photo, created_at FROM customers WHERE agent_id = ? ORDER BY name ASC");
    mysqli_stmt_bind_param($stmt_fetch, "i", $agent_id);
    mysqli_stmt_execute($stmt_fetch);
    $customers_res = mysqli_stmt_get_result($stmt_fetch);
}
mysqli_stmt_close($stmt_fetch);

include_once '../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary"></i> Manage <span class="gradient-text">Customers</span></h2>
        <p class="text-secondary">Register coverage policies, audit phone alerts, and edit customer accounts.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <?php if (!$edit_mode): ?>
            <form method="POST" action="manage_customers.php" class="d-flex gap-2 justify-content-md-end">
                <input type="text" class="form-control w-50" name="search_query" placeholder="Name, Email, or Phone..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn gradient-btn"><i class="bi bi-search"></i> Search</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_customers.php" class="btn btn-outline-secondary">Clear</a>
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

<?php if ($edit_mode && $edit_customer): ?>
    <!-- EDIT CUSTOMER FORM VIEW -->
    <div class="glass-card p-5 animate-fade-in-up">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold"><i class="bi bi-pencil-square text-warning"></i> Edit Customer: <?php echo htmlspecialchars($edit_customer['name']); ?></h4>
            <a href="manage_customers.php" class="btn btn-outline-secondary btn-sm">Cancel Edit</a>
        </div>
        
        <form method="POST" action="manage_customers.php" enctype="multipart/form-data">
            <input type="hidden" name="cust_id" value="<?php echo $edit_customer['id']; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_customer['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($edit_customer['email']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($edit_customer['phone']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reset Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" name="password" placeholder="New Password">
                </div>
                <div class="col-12">
                    <label class="form-label">Home / Office Address</label>
                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($edit_customer['address']); ?></textarea>
                </div>
                <div class="col-12 d-flex align-items-center gap-3 my-3">
                    <?php if (!empty($edit_customer['profile_photo'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($edit_customer['profile_photo']); ?>" alt="Current Avatar" class="profile-photo-mini" style="width: 50px; height: 50px;">
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Upload New Profile Photo</label>
                        <input type="file" class="form-control" name="profile_photo" accept="image/*">
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" name="update_customer" class="btn btn-warning px-4 py-2"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- LIST CUSTOMERS VIEW -->
    <div class="glass-card p-4">
        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Registration Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customers_res) > 0): ?>
                        <?php while ($cust = mysqli_fetch_assoc($customers_res)): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($cust['profile_photo'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($cust['profile_photo']); ?>" alt="Avatar" class="profile-photo-mini">
                                    <?php else: ?>
                                        <div class="profile-photo-mini bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem; border-radius: 50%;">
                                            <?php echo strtoupper(substr($cust['name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-dark"><?php echo htmlspecialchars($cust['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($cust['email']); ?></td>
                                <td><?php echo htmlspecialchars($cust['phone']); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars(substr($cust['address'], 0, 40)); ?><?php echo strlen($cust['address']) > 40 ? '...' : ''; ?></small></td>
                                <td><small><?php echo date('Y-m-d', strtotime($cust['created_at'])); ?></small></td>
                                <td class="text-end">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="add_policy.php?customer_id=<?php echo $cust['id']; ?>"><i class="bi bi-file-earmark-plus-fill text-success"></i> Create Policy</a></li>
                                            <li><a class="dropdown-item" href="send_sms.php?customer_id=<?php echo $cust['id']; ?>"><i class="bi bi-chat-left-text-fill text-info"></i> Send SMS Alert</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="manage_customers.php?edit=<?php echo $cust['id']; ?>"><i class="bi bi-pencil-square text-warning"></i> Edit Profile</a></li>
                                            <li><a class="dropdown-item text-danger" href="manage_customers.php?delete=<?php echo $cust['id']; ?>" onclick="return confirm('Are you sure you want to delete this customer? This will also purge their policies, payments, and SMS logs!');"><i class="bi bi-trash-fill"></i> Delete Customer</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2"></i> You have not registered any clients yet.
                                <a href="add_customer.php" class="btn btn-link fw-bold p-0">Register your first customer</a>
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
