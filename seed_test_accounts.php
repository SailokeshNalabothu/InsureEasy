<?php
require_once 'includes/db.php';

echo "<div style='font-family:sans-serif; max-width:600px; margin:40px auto; padding:20px; border:1px solid #cff4fc; background-color:#cff4fc; color:#055160; border-radius:10px;'>";
echo "<h2>InsureEasy Test Account Seeder</h2>";

// 1. Create default Agent if not exists
$agent_email = 'agent@insureeasy.com';
$agent_password_hash = password_hash('agent123', PASSWORD_DEFAULT);

$res_agent = mysqli_query($conn, "SELECT id FROM agents WHERE email = '$agent_email'");
if (mysqli_num_rows($res_agent) == 0) {
    $ins_agent = "INSERT INTO agents (name, email, phone, password, status) VALUES ('System Agent', '$agent_email', '9999988888', '$agent_password_hash', 'Approved')";
    if (mysqli_query($conn, $ins_agent)) {
        $agent_id = mysqli_insert_id($conn);
        echo "<p>✔️ Created Agent: <strong>agent@insureeasy.com</strong> (Password: <strong>agent123</strong>)</p>";
    } else {
        echo "<p>❌ Failed to create Agent: " . mysqli_error($conn) . "</p>";
        exit();
    }
} else {
    $row = mysqli_fetch_assoc($res_agent);
    $agent_id = $row['id'];
    echo "<p>ℹ️ Agent <strong>agent@insureeasy.com</strong> already exists.</p>";
}

// 2. Create customer if not exists
$cust_email = 'sailokeshnalabothu@gmail.com';
$cust_password_hash = password_hash('123123', PASSWORD_DEFAULT);

$res_cust = mysqli_query($conn, "SELECT id FROM customers WHERE email = '$cust_email'");
if (mysqli_num_rows($res_cust) == 0) {
    $ins_cust = "INSERT INTO customers (agent_id, name, email, phone, address, password) VALUES ($agent_id, 'Sai Lokesh', '$cust_email', '9876543210', '123 Main Street, Metro City', '$cust_password_hash')";
    if (mysqli_query($conn, $ins_cust)) {
        echo "<p>✔️ Created Customer: <strong>sailokeshnalabothu@gmail.com</strong> (Password: <strong>123123</strong>)</p>";
    } else {
        echo "<p>❌ Failed to create Customer: " . mysqli_error($conn) . "</p>";
    }
} else {
    // If exists, update password to 123123
    $up_cust = "UPDATE customers SET password = '$cust_password_hash' WHERE email = '$cust_email'";
    if (mysqli_query($conn, $up_cust)) {
         echo "<p>✔️ Customer <strong>sailokeshnalabothu@gmail.com</strong> password updated to <strong>123123</strong>.</p>";
    } else {
         echo "<p>❌ Failed to update Customer: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr><p>🎉 Seeding completed! You can now log in at <a href='login.php'>login.php</a>.</p>";
echo "</div>";
?>
