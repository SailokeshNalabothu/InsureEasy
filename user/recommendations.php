<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
checkCustomer();

$recommended_policies = [];
$form_submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_submitted = true;
    $age = $_POST['age'];
    $vehicle = $_POST['vehicle'];
    $home = $_POST['home'];
    $concern = $_POST['concern'];
    
    // Diagnostic logic
    if ($concern === 'health' || $age === 'over65') {
        $recommended_policies[] = [
            'title' => 'InsureEasy Health Shield Gold',
            'type' => 'Health Insurance',
            'description' => 'Top-tier cashless medical hospitalization covering up to $50,000 annually, pre-existing conditions waiver, and zero copay on emergency rooms.',
            'premium' => 45.00,
            'features' => ['Cashless Hospitalization', 'Critical Illness Cover', 'Family Floater Add-on']
        ];
    }
    
    if ($vehicle === 'car' || $vehicle === 'bike') {
        $premium_est = ($vehicle === 'car') ? 75.00 : 25.00;
        $recommended_policies[] = [
            'title' => 'Comprehensive Auto Defender',
            'type' => 'Auto Insurance',
            'description' => 'Zero-depreciation bumper protection, rapid roadside recovery assistance, engine protection cover, and 3rd party liability security.',
            'premium' => $premium_est,
            'features' => ['Roadside Assistance', 'Engine Cover Protection', 'No Claim Bonus Protection']
        ];
    }
    
    if ($home === 'own') {
        $recommended_policies[] = [
            'title' => 'SecureHaven Home Guard',
            'type' => 'Home Insurance',
            'description' => 'Insure your home structure, interior fittings, and belongings against fire, structural defects, earthquakes, and burglary.',
            'premium' => 60.00,
            'features' => ['Fire & Hazard Protection', 'Burglary Insurance', 'Temporary Housing Allowance']
        ];
    }
    
    // Always include a Life Policy proposal if age is > 25
    if ($age !== 'under25' || $concern === 'wealth') {
        $recommended_policies[] = [
            'title' => 'LifeCare Term Security Plus',
            'type' => 'Life Insurance',
            'description' => 'Secure the financial future of your dependents. High coverage payouts, tax benefits, and terminal illness premium waivers.',
            'premium' => 35.00,
            'features' => ['Terminal Illness Rider', 'Accidental Death Benefit', 'Flexible Payout Schemes']
        ];
    }
    
    // Fallback if no specific selection matches
    if (empty($recommended_policies)) {
        $recommended_policies[] = [
            'title' => 'InsureEasy Essential Health Cover',
            'type' => 'Health Insurance',
            'description' => 'An entry-level health protection scheme covering basic clinical procedures and medical consultations.',
            'premium' => 15.00,
            'features' => ['Doctor Consultations', 'Diagnostic Tests Cover', 'Pharmacy Discount Benefit']
        ];
    }
}

include_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="mb-4">
            <a href="dashboard.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="glass-card p-5 animate-fade-in-up mb-5">
            <h2 class="fw-bold mb-2 text-center">AI Insurance <span class="gradient-text">Advisor</span></h2>
            <p class="text-secondary text-center mb-4">Complete this quick diagnostic profile sheet to find coverage plans suited for you.</p>

            <form method="POST" action="recommendations.php" class="border-top pt-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">1. What is your age group?</label>
                        <select class="form-select" name="age" required>
                            <option value="under25">Under 25 years</option>
                            <option value="25-45" selected>25 - 45 years</option>
                            <option value="46-65">46 - 65 years</option>
                            <option value="over65">Over 65 years</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">2. Do you own a personal vehicle?</label>
                        <select class="form-select" name="vehicle" required>
                            <option value="car" selected>Yes, I own a car / SUV</option>
                            <option value="bike">Yes, I own a two-wheeler</option>
                            <option value="none">No, I do not own a vehicle</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">3. What is your residential home status?</label>
                        <select class="form-select" name="home" required>
                            <option value="own" selected>Own my house / apartment</option>
                            <option value="rent">Renting an apartment</option>
                            <option value="other">Living with family / others</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">4. What is your primary security risk concern?</label>
                        <select class="form-select" name="concern" required>
                            <option value="health" selected>Medical/Family Hospitalization</option>
                            <option value="vehicle">Road accidents/Vehicle damage</option>
                            <option value="home">Natural hazards/Fire/Theft at home</option>
                            <option value="wealth">Securing wealth for family dependents</option>
                        </select>
                    </div>

                    <div class="col-12 text-center mt-5">
                        <button type="submit" class="btn gradient-btn px-5 py-3"><i class="bi bi-cpu-fill"></i> Generate Recommendations</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($form_submitted): ?>
            <!-- RECOMMENDATION RESULTS -->
            <div class="animate-fade-in-up">
                <h4 class="fw-bold mb-4 text-center text-dark"><i class="bi bi-stars text-warning"></i> Your Personalized <span class="gradient-text">Policy Recommendations</span></h4>
                
                <div class="row g-4">
                    <?php foreach ($recommended_policies as $rec): ?>
                        <div class="col-md-6">
                            <div class="p-4 glass-card h-100 d-flex flex-column justify-content-between border-top-indigo" style="border-top: 4px solid var(--accent-primary);">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge bg-primary mb-1"><?php echo htmlspecialchars($rec['type']); ?></span>
                                            <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($rec['title']); ?></h5>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">EST. COST</small>
                                            <strong class="text-success">$<?php echo number_format($rec['premium'], 2); ?>/mo</strong>
                                        </div>
                                    </div>
                                    <p class="text-secondary text-sm mb-4"><?php echo htmlspecialchars($rec['description']); ?></p>
                                    
                                    <div class="mb-4">
                                        <small class="text-muted d-block mb-2 uppercase fw-bold" style="font-size:0.75rem;">COVERAGE BENEFITS INCLUDED:</small>
                                        <ul class="list-unstyled text-sm">
                                            <?php foreach ($rec['features'] as $feature): ?>
                                                <li class="mb-1 text-dark"><i class="bi bi-patch-check-fill text-info me-2"></i> <?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary w-100" onclick="alert('Your purchase request has been submitted. Our regional agent will contact you shortly to assign this coverage!');">
                                    <i class="bi bi-telephone-outbound"></i> Purchase Policy Cover
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
