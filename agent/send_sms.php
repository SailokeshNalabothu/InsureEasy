<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkAgent();

$agent_id = $_SESSION['agent_id'];
$success_msg = "";
$error_msg = "";

$pre_customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$pre_policy_id = isset($_GET['policy_id']) ? intval($_GET['policy_id']) : 0;

$selected_customer = null;
$selected_policy = null;

// Fetch pre-selected customer and policy details
if ($pre_customer_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, phone, email FROM customers WHERE id = ? AND agent_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $pre_customer_id, $agent_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $selected_customer = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if ($pre_policy_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, policy_name, end_date FROM policies WHERE id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $pre_policy_id, $pre_customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $selected_policy = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

// Handle Send SMS Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $policy_id = intval($_POST['policy_id']);
    $message = trim($_POST['message']);
    $api_key = trim($_POST['api_key']); // Optional Fast2SMS key input
    
    // Fetch customer phone number
    $stmt_cust = mysqli_prepare($conn, "SELECT name, phone FROM customers WHERE id = ? AND agent_id = ?");
    mysqli_stmt_bind_param($stmt_cust, "ii", $customer_id, $agent_id);
    mysqli_stmt_execute($stmt_cust);
    $res_cust = mysqli_stmt_get_result($stmt_cust);
    $customer_info = mysqli_fetch_assoc($res_cust);
    mysqli_stmt_close($stmt_cust);
    
    if (!$customer_info) {
        $error_msg = "Selected customer is invalid.";
    } elseif (empty($message)) {
        $error_msg = "Message content cannot be blank.";
    } else {
        $phone_number = $customer_info['phone'];
        $sms_status = "Simulated";
        
        if (!empty($api_key)) {
            // Fast2SMS API implementation
            $fields = array(
                "variables_values" => $message,
                "route" => "otp", // fast route
                "numbers" => $phone_number,
            );
            
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($fields),
                CURLOPT_HTTPHEADER => array(
                    "authorization: " . $api_key,
                    "accept: */*",
                    "cache-control: no-cache",
                    "content-type: application/json"
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                $error_msg = "Fast2SMS Curl Error: " . $err;
                $sms_status = "Failed";
            } else {
                $response_decoded = json_decode($response, true);
                if (isset($response_decoded['return']) && $response_decoded['return'] === true) {
                    $success_msg = "SMS dispatched successfully using Fast2SMS API!";
                    $sms_status = "Success";
                } else {
                    $error_msg = "Fast2SMS API responded with failure: " . (isset($response_decoded['message']) ? $response_decoded['message'] : $response);
                    $sms_status = "Failed";
                }
            }
        } else {
            // Simulator Mode (Success mock)
            $success_msg = "Simulation Mode: SMS request logged. (Reason: Fast2SMS API key omitted)";
            $sms_status = "Simulated";
        }
        
        // Always log to sms_logs table
        $stmt_log = mysqli_prepare($conn, "INSERT INTO sms_logs (customer_id, message, status) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt_log, "iss", $customer_id, $message, $sms_status);
        mysqli_stmt_execute($stmt_log);
        mysqli_stmt_close($stmt_log);
    }
}

// Fetch all customers for this agent to populate select options
$all_customers_stmt = mysqli_prepare($conn, "SELECT id, name, phone FROM customers WHERE agent_id = ? ORDER BY name ASC");
mysqli_stmt_bind_param($all_customers_stmt, "i", $agent_id);
mysqli_stmt_execute($all_customers_stmt);
$all_customers_res = mysqli_stmt_get_result($all_customers_stmt);

// Default Message Template calculation
$default_msg = "";
if ($selected_customer && $selected_policy) {
    $days_left = ceil((strtotime($selected_policy['end_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
    if ($days_left < 0) {
        $default_msg = "Dear " . htmlspecialchars($selected_customer['name']) . ",\n\nYour Insurance Policy [" . htmlspecialchars($selected_policy['policy_name']) . "] has EXPIRED. Please renew immediately to restore coverage benefits.\n\nThank You,\nInsureEasy Support";
    } else {
        $default_msg = "Dear " . htmlspecialchars($selected_customer['name']) . ",\n\nYour Insurance Policy [" . htmlspecialchars($selected_policy['policy_name']) . "] expires in " . $days_left . " days (on " . htmlspecialchars($selected_policy['end_date']) . "). Please renew immediately.\n\nThank You,\nInsureEasy Support";
    }
} else {
    $default_msg = "Dear Customer,\n\nYour Insurance Policy is due for renewal. Please log in to your dashboard to complete the premium payment.\n\nThank You,\nInsureEasy Support";
}
?>

<div class="row">
    <div class="col-lg-7 mb-4 mb-lg-0">
        <div class="glass-card p-5 animate-fade-in-up">
            <h3 class="fw-bold mb-3"><i class="bi bi-chat-left-dots-fill text-primary"></i> SMS Notification <span class="gradient-text">Module</span></h3>
            <p class="text-secondary mb-4">Send renewal notifications. Submit with an API key for live transmission, or leave empty to simulate the delivery.</p>

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

            <form method="POST" action="send_sms.php">
                <div class="mb-3">
                    <label class="form-label">Select Recipient Customer *</label>
                    <select class="form-select" name="customer_id" id="sms_customer_select" required onchange="updateCustomerInfo()">
                        <option value="">-- Choose customer --</option>
                        <?php 
                        mysqli_data_seek($all_customers_res, 0);
                        while ($cust = mysqli_fetch_assoc($all_customers_res)): 
                            $selected = ($pre_customer_id == $cust['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $cust['id']; ?>" data-phone="<?php echo htmlspecialchars($cust['phone']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($cust['name']); ?> (<?php echo htmlspecialchars($cust['phone']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Associated Policy *</label>
                    <select class="form-select" name="policy_id" id="sms_policy_select" required>
                        <?php if ($selected_policy): ?>
                            <option value="<?php echo $selected_policy['id']; ?>" selected><?php echo htmlspecialchars($selected_policy['policy_name']); ?> (Ends: <?php echo htmlspecialchars($selected_policy['end_date']); ?>)</option>
                        <?php else: ?>
                            <option value="0">-- Select customer first --</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fast2SMS Authorization Key (Optional)</label>
                    <input type="password" class="form-control" name="api_key" placeholder="Paste your API key here to send a real SMS">
                    <small class="text-muted text-xs">Leave blank to log and mock-send without an API account.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">Message Content *</label>
                    <textarea class="form-control" name="message" id="sms_message" rows="6" required><?php echo htmlspecialchars($default_msg); ?></textarea>
                </div>

                <button type="submit" class="btn gradient-btn px-4 py-2"><i class="bi bi-send-fill"></i> Dispatch SMS Notification</button>
            </form>
        </div>
    </div>

    <!-- Phone Simulator Preview panel -->
    <div class="col-lg-5">
        <div class="glass-card p-4 text-center sticky-top" style="top: 90px; border-radius: 40px; border: 12px solid var(--bg-sidebar); background-color:#000000; color:#ffffff; min-height: 500px; max-width:320px; margin: 0 auto;">
            <div class="d-flex justify-content-between px-3 text-xs mb-4" style="font-size: 0.75rem; color:#888;">
                <span>9:41 AM</span>
                <span><i class="bi bi-wifi"></i> <i class="bi bi-battery-full"></i></span>
            </div>
            
            <div class="text-center mb-4 mt-2">
                <span class="badge bg-secondary py-2 px-3 rounded-pill text-white" style="font-size:0.7rem;"><i class="bi bi-shield-fill text-info"></i> InsureEasy Alert</span>
            </div>

            <!-- Simulated SMS Bubble -->
            <div class="p-3 bg-dark text-start text-white border border-secondary rounded-4 shadow mb-4" style="font-size: 0.85rem; max-width: 90%; margin: 0 auto; min-height: 120px;">
                <div class="fw-bold mb-1" style="color:var(--accent-secondary);">INSUREEASY ALERT</div>
                <div id="phone_preview_message" style="white-space: pre-wrap; font-size:0.8rem;"><?php echo htmlspecialchars($default_msg); ?></div>
            </div>

            <div class="text-muted mt-5 pt-5" style="font-size:0.75rem;">
                <i class="bi bi-phone-vibrate fs-3 d-block mb-2 text-info animate-pulse"></i>
                Simulated Screen Interface
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('sms_message').addEventListener('input', function() {
    document.getElementById('phone_preview_message').innerText = this.value;
});

function updateCustomerInfo() {
    const customerSelect = document.getElementById('sms_customer_select');
    const customerId = customerSelect.value;
    const policySelect = document.getElementById('sms_policy_select');
    
    if (!customerId) {
        policySelect.innerHTML = '<option value="0">-- Select customer first --</option>';
        return;
    }

    // Fetch customer policies dynamically using a quick AJAX fetch
    fetch('../agent/send_sms.php?ajax_fetch_policies=1&cust_id=' + customerId)
        .then(response => response.json())
        .then(data => {
            policySelect.innerHTML = '';
            if (data.length > 0) {
                data.forEach(pol => {
                    policySelect.innerHTML += `<option value="${pol.id}" data-name="${pol.policy_name}" data-end="${pol.end_date}">${pol.policy_name} (Ends: ${pol.end_date})</option>`;
                });
                
                // Trigger preview builder
                generateMessage();
            } else {
                policySelect.innerHTML = '<option value="0">No policies found for this customer</option>';
            }
        });
}

function generateMessage() {
    const customerSelect = document.getElementById('sms_customer_select');
    const name = customerSelect.options[customerSelect.selectedIndex].text.split('(')[0].trim();
    const policySelect = document.getElementById('sms_policy_select');
    if (policySelect.value == 0 || !policySelect.options[policySelect.selectedIndex]) return;
    
    const policyName = policySelect.options[policySelect.selectedIndex].getAttribute('data-name');
    const endDate = policySelect.options[policySelect.selectedIndex].getAttribute('data-end');
    
    const today = new Date();
    const expiry = new Date(endDate);
    const timeDiff = expiry.getTime() - today.getTime();
    const daysLeft = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    let msg = "";
    if (daysLeft < 0) {
        msg = `Dear ${name},\n\nYour Insurance Policy [${policyName}] has EXPIRED. Please renew immediately to restore coverage benefits.\n\nThank You,\nInsureEasy Support`;
    } else {
        msg = `Dear ${name},\n\nYour Insurance Policy [${policyName}] expires in ${daysLeft} days (on ${endDate}). Please renew immediately.\n\nThank You,\nInsureEasy Support`;
    }
    
    document.getElementById('sms_message').value = msg;
    document.getElementById('phone_preview_message').innerText = msg;
}

document.getElementById('sms_policy_select').addEventListener('change', generateMessage);

// Handle AJAX policy loading request
<?php
if (isset($_GET['ajax_fetch_policies']) && isset($_GET['cust_id'])) {
    ob_clean();
    header('Content-Type: application/json');
    $cust_id = intval($_GET['cust_id']);
    $stmt_ajax = mysqli_prepare($conn, "SELECT id, policy_name, end_date FROM policies WHERE customer_id = ?");
    mysqli_stmt_bind_param($stmt_ajax, "i", $cust_id);
    mysqli_stmt_execute($stmt_ajax);
    $res_ajax = mysqli_stmt_get_result($stmt_ajax);
    $policies = [];
    while ($row = mysqli_fetch_assoc($res_ajax)) {
        $policies[] = $row;
    }
    mysqli_stmt_close($stmt_ajax);
    echo json_encode($policies);
    exit();
}
?>
</script>

<?php
mysqli_stmt_close($all_customers_stmt);
include_once '../includes/footer.php';
?>
