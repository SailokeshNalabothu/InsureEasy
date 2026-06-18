<?php
// To run: php send_due_alerts.php (CLI) or request http://localhost/InsureEasy/cron/send_due_alerts.php
require_once __DIR__ . '/../includes/db.php';

echo "=== InsureEasy Automated Due Alerts Daemon ===\n";
echo "Run Date: " . date('Y-m-d H:i:s') . "\n";

// Query policies ending in <= 7 days that are still Active
$query = "SELECT 
    p.id as policy_id,
    p.policy_name,
    p.end_date,
    c.id as customer_id,
    c.name as customer_name,
    c.phone as customer_phone
    FROM policies p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
      AND p.end_date >= CURDATE() 
      AND p.status = 'Active'";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Database Query Failed: " . mysqli_error($conn) . "\n");
}

$count = mysqli_num_rows($result);
echo "Found {$count} policies due for renewal in the next 7 days.\n\n";

if ($count > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $policy_id = $row['policy_id'];
        $policy_name = $row['policy_name'];
        $end_date = $row['end_date'];
        $customer_id = $row['customer_id'];
        $customer_name = $row['customer_name'];
        $customer_phone = $row['customer_phone'];
        
        $days_left = ceil((strtotime($end_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
        
        $message = "Dear {$customer_name},\n\nYour Insurance Policy [{$policy_name}] expires in {$days_left} days (on {$end_date}). Please renew immediately.\n\nThank You,\nInsureEasy Support";
        
        // Log to SMS Logs
        $status = "AutoSimulated";
        $stmt_log = mysqli_prepare($conn, "INSERT INTO sms_logs (customer_id, message, status) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt_log, "iss", $customer_id, $message, $status);
        
        if (mysqli_stmt_execute($stmt_log)) {
            echo "[SUCCESS] Alert logged for Customer '{$customer_name}' (#{$customer_id}) regarding Policy '{$policy_name}' (#{$policy_id}). Days remaining: {$days_left}\n";
        } else {
            echo "[ERROR] Failed to log alert for Customer '{$customer_name}'\n";
        }
        mysqli_stmt_close($stmt_log);
    }
} else {
    echo "No automatic notifications required today.\n";
}

echo "\nDaemon Execution Complete.\n";
?>
