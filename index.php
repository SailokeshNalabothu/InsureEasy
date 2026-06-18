<?php
require_once 'includes/db.php';
include_once 'includes/header.php';

// Fetch some metrics to display dynamically
$active_agents = 0;
$active_policies = 0;

$res_ag = mysqli_query($conn, "SELECT COUNT(*) as count FROM agents WHERE status = 'Approved'");
if ($res_ag) {
    $row = mysqli_fetch_assoc($res_ag);
    $active_agents = $row['count'] > 0 ? $row['count'] : 12; // fallback mock if empty
}

$res_pol = mysqli_query($conn, "SELECT COUNT(*) as count FROM policies WHERE status = 'Active'");
if ($res_pol) {
    $row = mysqli_fetch_assoc($res_pol);
    $active_policies = $row['count'] > 0 ? $row['count'] : 340; // fallback mock if empty
}
?>

<div class="row align-items-center py-5 my-3 animate-fade-in-up">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <span class="badge bg-indigo-100 text-primary px-3 py-2 rounded-pill mb-3 fw-bold"><i class="bi bi-patch-check"></i> InsureEasy V2.0 Launch</span>
        <h1 class="display-4 fw-bold lh-sm mb-3">
            Streamlining Your Insurance <span class="gradient-text">Simply & Swiftly</span>
        </h1>
        <p class="lead text-secondary mb-4">
            Protect your health, auto, home, and life using our online portal. Easily manage policy coverage, premium payments, and alerts.
        </p>
        <div class="d-flex flex-wrap gap-3">
            <a href="login.php" class="btn gradient-btn btn-lg px-4 py-3"><i class="bi bi-box-arrow-in-right"></i> Customer Login Portal</a>
            <a href="register.php" class="btn btn-outline-secondary btn-lg px-4 py-3"><i class="bi bi-person-plus"></i> Register as Agent</a>
        </div>
    </div>
    <div class="col-lg-6 text-center">
        <!-- Render a beautiful glassmorphic visual presentation of policy cards -->
        <div class="p-4 p-md-5 glass-card position-relative shadow-lg border border-light">
            <div class="position-absolute top-0 start-50 translate-middle badge bg-primary px-3 py-2 text-white">INSUREEASY CARD</div>
            <div class="text-start mb-4">
                <h3 class="fw-bold"><i class="bi bi-shield-fill text-primary"></i> Premium Protection</h3>
                <p class="text-secondary text-sm">Policy ID: IE-77391-AUTO</p>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-6 text-start">
                    <small class="text-muted d-block">COVERAGE TYPE</small>
                    <strong class="text-primary fs-5">Comprehensive Auto</strong>
                </div>
                <div class="col-6 text-start">
                    <small class="text-muted d-block">ANNUAL PREMIUM</small>
                    <strong class="text-success fs-5">$1,200.00</strong>
                </div>
                <div class="col-6 text-start">
                    <small class="text-muted d-block">START DATE</small>
                    <strong>2026-01-01</strong>
                </div>
                <div class="col-6 text-start">
                    <small class="text-muted d-block">RENEWAL DUE</small>
                    <span class="text-danger fw-bold">In 7 Days</span>
                </div>
            </div>

            <div class="p-3 bg-light rounded text-start d-flex align-items-center gap-3">
                <div class="spinner-grow spinner-grow-sm text-danger" role="status"></div>
                <small class="text-dark"><strong>SMS Alert Triggered:</strong> Premium due reminder dispatched to policyholder.</small>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row text-center g-4 py-5" id="services">
    <h2 class="fw-bold mb-4">Our Premium <span class="gradient-text">Insurance Plans</span></h2>
    <div class="col-md-3">
        <div class="p-4 glass-card h-100 border-top-indigo">
            <div class="fs-1 text-primary mb-3"><i class="bi bi-heart-pulse-fill"></i></div>
            <h4 class="fw-bold">Health Cover</h4>
            <p class="text-secondary text-sm">Comprehensive cashless hospitalization, critical illness cover, and family medical packages.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 glass-card h-100">
            <div class="fs-1 text-info mb-3"><i class="bi bi-car-front-fill"></i></div>
            <h4 class="fw-bold">Auto Cover</h4>
            <p class="text-secondary text-sm">Zero-depreciation bumper protection, rapid roadside recovery assistance, and liability security.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 glass-card h-100">
            <div class="fs-1 text-warning mb-3"><i class="bi bi-house-door-fill"></i></div>
            <h4 class="fw-bold">Home Cover</h4>
            <p class="text-secondary text-sm">Insure against structural hazards, fire, theft, and natural disasters for ultimate peace of mind.</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-4 glass-card h-100">
            <div class="fs-1 text-danger mb-3"><i class="bi bi-balloon-heart-fill"></i></div>
            <h4 class="fw-bold">Life Cover</h4>
            <p class="text-secondary text-sm">Term assurance policies ensuring the safety and financial health of your family's future.</p>
        </div>
    </div>
</div>

<!-- About Us Section -->
<div class="row py-5 align-items-center" id="about">
    <div class="col-md-6 order-md-2">
        <h2 class="fw-bold mb-3">About <span class="gradient-text">InsureEasy</span></h2>
        <p class="text-secondary">
            InsureEasy is a cutting-edge platform built to remove the complexities of managing, tracking, and executing insurance policies. By bridging the communication barrier between managers, regional agents, and policyholders, we provide transparent cover sheets and automated reminders.
        </p>
        <p class="text-secondary">
            We operate fully online, allowing customers to check details, monitor expiry countdowns, view complete transaction histories, and get SMS notification alerts.
        </p>
        <div class="d-flex gap-4 mt-4">
            <div>
                <h3 class="fw-bold gradient-text"><?php echo $active_agents; ?>+</h3>
                <small class="text-muted">Approved Active Agents</small>
            </div>
            <div>
                <h3 class="fw-bold gradient-text"><?php echo $active_policies; ?>+</h3>
                <small class="text-muted">Policies Secured</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 order-md-1">
        <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=600&q=80" alt="Insurance Planning" class="img-fluid rounded shadow border">
    </div>
</div>

<!-- Contact Section -->
<div class="py-5" id="contact">
    <div class="glass-card p-5">
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <h2 class="fw-bold mb-3">Get in Touch</h2>
                <p class="text-secondary">Need customized commercial packages or have operational questions? Reach out to our customer care team.</p>
                <div class="mt-4">
                    <p class="mb-2"><i class="bi bi-geo-alt-fill text-primary"></i> 101, FinTech Plaza, Metro City</p>
                    <p class="mb-2"><i class="bi bi-telephone-fill text-primary"></i> +1 (555) 019-2834</p>
                    <p class="mb-2"><i class="bi bi-envelope-fill text-primary"></i> support@insureeasy.com</p>
                </div>
            </div>
            <div class="col-md-7">
                <form method="POST" action="#contact" onsubmit="alert('Thank you for contacting InsureEasy! Our team will get back to you shortly.'); return true;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" placeholder="Your full name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" placeholder="Your email address" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" placeholder="What is your query about?" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="4" placeholder="Type your message here..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn gradient-btn px-4 py-2">Submit Query</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once 'includes/footer.php';
?>
