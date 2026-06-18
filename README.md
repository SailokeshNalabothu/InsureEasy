# 🛡️ InsureEasy - Online Insurance Management Platform

InsureEasy is a cutting-edge, secure, and intuitive web application designed to streamline the insurance management process for administrators, insurance agents, and policyholders. Built with PHP, MySQL, Bootstrap 5, and Chart.js, the system supports user registrations, policy issuance, simulated premium payments, automatic expiry notifications, and dynamic status reports.

---

## 🚀 Key Features

*   **Multi-Portal Authentication:** Secure access levels separating operations for **Administrators**, **Insurance Agents**, and **Customers (Users)**.
*   **Analytics Dashboard:** The Admin portal features a dynamic **Chart.js** representation showing active, expired, and pending coverage status ratios.
*   **Automatic Expiry Check (Cron System):** A background daemon checks for coverages expiring within 7 days and dispatches alerts.
*   **Mock SMS Gateway & Phone UI:** Renders SMS reminders on a simulated mobile phone screen. Integrates with the **Fast2SMS** cURL API.
*   **PDF Policy Certificates:** A print media-style CSS layout that formats policy information pages as official printable/saveable PDF certificates.
*   **AI Policy Finder:** Interactive questionnaires checking age groups, vehicle ownership, home status, and safety risks to recommend optimal insurance plans.
*   **Dark Mode Support:** A persistent light/dark theme toggler using CSS custom properties (`data-theme`) and browser `localStorage`.
*   **Profile Management:** Custom demographic editing and profile avatar image uploads.

---

## 🛠️ Technology Stack

*   **Frontend:** HTML5, Vanilla CSS3 (Custom Grid & Glassmorphism variables), Bootstrap v5.3, Bootstrap Icons
*   **Backend:** PHP v7.4+
*   **Database:** MySQL / MariaDB (via phpMyAdmin)
*   **Server Environment:** XAMPP (Apache HTTP Server)
*   **Visual Charts:** Chart.js (CDN)
*   **SMS API:** Fast2SMS Bulk SMS Gateway API

---

## 📂 Project Structure

```text
InsureEasy/
├── assets/
│   ├── css/style.css            # Custom glassmorphic styles and dark mode variables
│   └── js/main.js              # Theme switcher & global logic
├── includes/
│   ├── db.php                   # Database connection helper
│   ├── header.php               # Dynamic navbar layouts
│   ├── footer.php               # Standard page scripts and closing tags
│   └── auth.php                 # Middleware role validators (Admin, Agent, User)
├── admin/
│   ├── dashboard.php            # Analytics charts & agent approvals
│   ├── manage_agents.php        # Audit and status actions for agent list
│   ├── reports.php              # Comprehensive payments transaction audit log
│   └── logout.php               # Admin session purge controller
├── agent/
│   ├── dashboard.php            # Expiry countdown checkers & action prompts
│   ├── add_customer.php         # Customer registry form
│   ├── manage_customers.php     # Edit client profiles and upload profile images
│   ├── add_policy.php           # Policy assignment tool
│   ├── manage_policies.php      # Policies tracking list
│   ├── send_sms.php             # SMS alert generator & phone simulator
│   └── logout.php               # Agent session purge controller
├── user/
│   ├── dashboard.php            # Policy summaries & quick-pay prompts
│   ├── my_policies.php          # Detailed policy certificates with print layout
│   ├── make_payment.php         # Card payment simulator & coverage activation
│   ├── payment_history.php      # Transaction receipt ledger
│   ├── profile.php              # Upload profile photos & update contact details
│   ├── recommendations.php      # AI Advisor match quiz
│   └── logout.php               # Customer session purge controller
├── uploads/                     # Client profile photo folder
├── cron/
│   └── send_due_alerts.php      # Automated daily check script
├── index.php                    # Product landing page
├── login.php                    # Unified login switcher
├── register.php                 # Agent signup requests
├── database.sql                 # SQL schema and seed scripts
└── .gitignore                   # Excludes configurations from Git
```

---

## 💻 Installation & Local Setup (XAMPP)

### Prerequisites
1. Install **XAMPP** (Apache and MySQL).
2. Install **Git** (if pushing/pulling files).

### Step 1: Clone or Copy Project
Move the `InsureEasy` directory to your local XAMPP web server directory:
```bash
C:\xampp\htdocs\InsureEasy
```

### Step 2: Database Setup
1. Turn **ON** Apache and MySQL in your **XAMPP Control Panel**.
2. Open your web browser and navigate to: **`http://localhost/phpmyadmin`**
3. Create a new database named **`insureeasy`**.
4. Select the `insureeasy` database, go to the **SQL** tab, copy the contents of `database.sql` inside the repository, paste it, and run it.

### Step 3: Run the Application
Open your browser and navigate to:
👉 **`http://localhost/InsureEasy/index.php`**

---

## 🔑 Seeding / Default Credentials

The following accounts have been pre-seeded to make evaluation quick and straightforward:

| Role | Username / Email | Password | Account Status |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Active |
| **Insurance Agent** | `agent@insureeasy.com` | `agent123` | Approved (Active) |
| **Customer (User)** | `sailokeshnalabothu@gmail.com` | `123123` | Active |

---

## 📡 Expiry Check Cron Command
To run the background coverage auditor check manually, visit this URL or run via terminal:
👉 **`http://localhost/InsureEasy/cron/send_due_alerts.php`**
