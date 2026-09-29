# CampusFix — Campus Complaint Tracking System

## Public deployment (Render + TiDB Cloud)

The Docker image runs PHP/Apache and initializes an empty MySQL-compatible database at startup. Connect this repository to a Render web service using `render.yaml`, and create a TiDB Cloud database first. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in Render. Leave `DB_SSL=true`. Set `ADMIN_EMAIL` and a unique `ADMIN_PASSWORD` of at least 12 characters for the first administrator. The startup script creates the tables and administrator once; it does not reset an existing administrator's password. After the first successful deploy, remove `ADMIN_PASSWORD` from Render's environment settings so it is not retained there.

Do not import `database/campusfix.sql` into a public database: it contains demo users with published passwords. The Docker image excludes that file and the legacy `database/create_admin.php` endpoint. For local XAMPP demonstrations, the demo SQL remains available in the repository.

The older local XAMPP instructions and demo credentials below apply only to local development.

## Read-only demo complaints

The public **Demo Complaints** page (`demo_complaints.php`) shows ten illustrative campus reports with filters, status badges, local SVG placeholders, upvote counts, and a status timeline on each detail page. The sample records live only in `data/demo_complaints.php`; they are never imported into the production `complaints` table. Student and administrator complaint lists keep using real database records and their existing access rules. To replace the samples with API data, change the data source loaded by `demo_complaints.php` and `demo_complaint_view.php`; to remove the showcase, remove those routes and their navigation links.

A full-stack, university-grade web application designed for campus facility maintenance, problem reporting, and structured administrative resolution.

---

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [Problem Statement](#2-problem-statement)
3. [System Objectives](#3-system-objectives)
4. [Features by Role](#4-features-by-role)
5. [Technology Stack](#5-technology-stack)
6. [3-Tier Architecture](#6-3-tier-architecture)
7. [Project Folder Structure](#7-project-folder-structure)
8. [Database Design & ER Relationship](#8-database-design--er-relationship)
9. [XAMPP Setup & Installation](#9-xampp-setup--installation)
10. [Database Import Instructions](#10-database-import-instructions)
11. [Database Configuration](#11-database-configuration)
12. [Administrator Setup](#12-administrator-setup)
13. [Demo Credentials (DEMO ONLY)](#13-demo-credentials-demo-only)
14. [Security Implementation](#14-security-implementation)
15. [Known Limitations](#15-known-limitations)
16. [Future Improvements](#16-future-improvements)
17. [Project Report / Screenshot Checklist](#17-project-report--screenshot-checklist)
18. [Live Demonstration Script](#18-live-demonstration-script)
19. [Viva Cheat Sheet (Questions & Answers)](#19-viva-cheat-sheet-questions--answers)

---

## 1. Project Overview
**CampusFix** is a responsive web application that streamlines how university students report physical campus defects (such as Wi-Fi disruptions, broken lab equipment, electrical failures, washroom sanitation, and damaged furniture) and how university administrators inspect, prioritize, and record official resolutions for each problem.

---

## 2. Problem Statement
In traditional university campuses, students report physical maintenance problems verbally, via informal social media groups, or through handwritten logs. This leads to:
- Lack of accountability and lost complaint records.
- Inability for students to track resolution status in real-time.
- Unorganized administrative queues with no centralized prioritization.
- Duplicate reports for the same classroom or lab issue.

**CampusFix** solves this by establishing a central, database-backed platform providing transparent status tracking, role-based controls, and structured resolution notes.

---

## 3. System Objectives
- **Structured Reporting:** Enable students to submit detailed facility complaints across 10 campus issue categories.
- **Real-Time Tracking:** Provide students with automated status indicators (`Pending`, `In Progress`, `Resolved`).
- **Administrative Queue:** Give campus maintenance supervisors a centralized dashboard to prioritize and resolve complaints.
- **Enterprise Security:** Implement password hashing, PDO prepared statements, output escaping, and CSRF defense.
- **Academic Readiness:** Deliver a clean codebase with zero third-party framework overhead, suitable for university defense (viva).

---

## 4. Features by Role

### Student Capabilities
- **Account Registration & Login:** Create account with full name, student ID, email, and secure password.
- **Personal Dashboard:** View 4 summary cards (Total, Pending, In Progress, Resolved) strictly scoped to their own complaints.
- **Complaint Submission:** File complaints with category, location, priority, and description; automatically assigned unique tracking codes (`CMP-0001`).
- **Ownership Scoping:** View only their own complaints (cross-account URL access strictly prevented).
- **Search & Filter:** Search by keywords (`q`) across title, code, location, and description; filter by category, priority, and status.
- **Complaint Management:** Edit or delete complaints while they remain in `Pending` status.
- **Resolution Inspection:** Review administrator notes upon resolution.
- **Profile Management:** View and update full name and unique student ID (email remains read-only).

### Administrator Capabilities
- **Secure Authentication:** Log in through dedicated administrator credentials.
- **Global Dashboard:** View campus-wide metrics (Total, Pending, In Progress, Resolved, and High Priority) plus 5 latest campus reports.
- **All-Complaints Queue:** Inspect and filter complaints submitted by any student across campus.
- **Management Controls:** Update complaint priority (`Low`, `Medium`, `High`) and lifecycle status (`Pending` &rarr; `In Progress` &rarr; `Resolved`).
- **Mandatory Resolution Notes:** Enforce non-empty resolution notes when marking any complaint as `Resolved`.
- **User Directory:** Access a read-only directory of all registered students and administrators.

---

## 5. Technology Stack
- **Frontend:** HTML5, CSS3, Bootstrap 5.3.3 (via CDN), Bootstrap Icons 1.11.3, Vanilla JavaScript.
- **Backend:** PHP 8.2+ (pure vanilla PHP using PDO, no frameworks).
- **Database:** MySQL / MariaDB (InnoDB engine, utf8mb4 charset).
- **Runtime Environment:** XAMPP for Windows (Apache HTTP Server + MariaDB).

---

## 6. 3-Tier Architecture

```text
+------------------------------------------------------------------+
|                   Presentation Layer (Client)                    |
|  - HTML5 & CSS3 Responsive Academic Theme                        |
|  - Bootstrap 5 Components & Bootstrap Icons                      |
|  - Vanilla JavaScript (Delete confirms, dynamic validations)    |
+------------------------------------------------------------------+
                               |
                               | HTTP(S) Requests & Responses
                               v
+------------------------------------------------------------------+
|                    Application Layer (Server)                    |
|  - Apache Web Server (XAMPP)                                     |
|  - PHP 8+ Core Engine                                            |
|  - Session Authentication & Role Guards (auth.php)              |
|  - CSRF & XSS Security Middleware (functions.php)               |
+------------------------------------------------------------------+
                               |
                               | PDO Prepared Statements (UTF-8)
                               v
+------------------------------------------------------------------+
|                      Data Layer (Database)                       |
|  - MySQL / MariaDB Database (campusfix)                          |
|  - users Table (Credentials, Roles, Timestamps)                 |
|  - complaints Table (Taxonomies, Foreign Keys, ON DELETE CASCADE)|
+------------------------------------------------------------------+
```

---

## 7. Project Folder Structure

```text
CampusFix/
│
├── index.php                 # Public landing page (Hero, categories, Login/Register CTAs)
├── register.php              # Student registration with validation & password hashing
├── login.php                 # Role-aware authentication for Student and Admin
├── logout.php                # Session destruction and cookie clearance
├── dashboard.php             # Student dashboard (4 metric cards, 5 recent complaints)
├── complaints.php            # Student complaint list with multi-parameter search & filters
├── complaint_create.php      # Student complaint submission form (auto-generates CMP-XXXX)
├── complaint_view.php        # Student detail view (resolution notes, Pending edit/delete)
├── complaint_edit.php        # Student edit form (ownership check, Pending-only rule)
├── complaint_delete.php      # POST-only deletion endpoint with CSRF & Pending verification
├── profile.php               # Student profile view and update (unique Student ID check)
│
├── admin/
│   ├── dashboard.php         # Global admin dashboard (5 metric cards, campus-wide list)
│   ├── complaints.php        # Global complaint queue with student search & multi-filtering
│   ├── complaint_view.php    # Detail inspection view with student card & management form
│   ├── complaint_update.php  # POST-only status, priority, and resolution note endpoint
│   └── users.php             # Read-only user directory (IDs, names, emails, roles, dates)
│
├── config/
│   └── database.php          # Centralized PDO connection with ERRMODE_EXCEPTION & UTF-8
│
├── includes/
│   ├── auth.php              # Session initialization, route guards, current user helpers
│   ├── functions.php         # CSRF helpers, e() escaping, flash messages, taxonomies, badges
│   ├── header.php            # Role-aware Bootstrap 5 navbar and flash alert container
│   └── footer.php            # Academic footer and JavaScript bundle imports
│
├── assets/
│   ├── css/
│   │   └── style.css         # Academic teal/blue theme, card layouts, mobile responsiveness
│   └── js/
│       └── app.js            # Vanilla JavaScript delete confirmations & status interactions
│
├── database/
│   ├── campusfix.sql         # Database schema, table constraints, and realistic demo data
│   └── create_admin.php      # Secure one-time administrator account creation utility
│
└── README.md                 # Complete documentation, setup guide, and viva cheat sheet
```

---

## 8. Database Design & ER Relationship

### Entity-Relationship Diagram
```text
  +--------------------------+               +--------------------------------------+
  |          USERS           | 1           * |              COMPLAINTS              |
  +--------------------------+---------------+--------------------------------------+
  | PK  id (INT)             |<-------------+| PK  id (INT)                         |
  |     full_name (VARCHAR)  |               |     complaint_code (VARCHAR, UNIQUE) |
  |     student_id (VARCHAR) |               | FK  user_id (INT)                    |
  |     email (VARCHAR)      |               |     title (VARCHAR)                  |
  |     password (VARCHAR)   |               |     category (ENUM)                  |
  |     role (ENUM)          |               |     location (VARCHAR)               |
  |     created_at (TS)      |               |     priority (ENUM)                  |
  +--------------------------+               |     description (TEXT)               |
                                             |     status (ENUM)                    |
                                             |     resolution_note (TEXT)           |
                                             |     created_at (TIMESTAMP)           |
                                             |     updated_at (TIMESTAMP)           |
                                             +--------------------------------------+
```

### Table: `users`
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | Primary Key, Auto Increment | Unique internal user identifier |
| `full_name` | `VARCHAR(100)` | NOT NULL | User's complete legal name |
| `student_id` | `VARCHAR(30)` | UNIQUE, Nullable | Academic ID (e.g. STU-2024-001) |
| `email` | `VARCHAR(150)` | UNIQUE, NOT NULL | Account login email address |
| `password` | `VARCHAR(255)` | NOT NULL | Bcrypt hash generated via `password_hash()` |
| `role` | `ENUM` | 'student', 'admin' | Access tier (default: 'student') |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Registration timestamp |

### Table: `complaints`
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | Primary Key, Auto Increment | Unique internal complaint identifier |
| `complaint_code` | `VARCHAR(20)` | UNIQUE, NOT NULL | Formatted tracking code (`CMP-0001`) |
| `user_id` | `INT` | Foreign Key &rarr; `users.id` | Submitting student (ON DELETE CASCADE) |
| `title` | `VARCHAR(150)` | NOT NULL | Concise problem summary |
| `category` | `ENUM` | 10 Allowed Categories | Campus issue taxonomy |
| `location` | `VARCHAR(150)` | NOT NULL | Specific physical room/building |
| `priority` | `ENUM` | 'Low', 'Medium', 'High' | Urgency level (default: 'Medium') |
| `description` | `TEXT` | NOT NULL | Detailed problem description |
| `status` | `ENUM` | 'Pending', 'In Progress', 'Resolved' | Lifecycle state (default: 'Pending') |
| `resolution_note` | `TEXT` | Nullable | Official administrator resolution note |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | `TIMESTAMP` | ON UPDATE CURRENT_TIMESTAMP | Last modification timestamp |

---

## 9. XAMPP Setup & Installation
1. Download and install **XAMPP** (with PHP 8.2+ and MySQL) from [apachefriends.org](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel**.
3. Start the **Apache** module and the **MySQL** module.
4. Place the `CampusFix` project directory into XAMPP's web directory:
   ```text
   C:\xampp\htdocs\CampusFix
   ```
5. Access the application in your browser:
   ```text
   http://localhost/CampusFix
   ```

---

## 10. Database Import Instructions
1. Open your web browser and navigate to **phpMyAdmin**:
   ```text
   http://localhost/phpmyadmin
   ```
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select:
   ```text
   C:\xampp\htdocs\CampusFix\database\campusfix.sql
   ```
4. Click **Go** at the bottom of the page.
5. The `campusfix` database and tables (`users`, `complaints`) will be created and seeded with realistic demo records.

*Alternatively, import via Command Prompt / PowerShell:*
```powershell
Get-Content "C:\xampp\htdocs\CampusFix\database\campusfix.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root
```

---

## 11. Database Configuration
Database credentials are centralized in [`config/database.php`](file:///C:/xampp/htdocs/CampusFix/config/database.php):
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'campusfix');
define('DB_USER', 'root');
define('DB_PASS', '');
```
If your local MySQL uses a different port or password, adjust these constants accordingly.

---

## 12. Administrator Setup
An administrator account is pre-seeded in `campusfix.sql`. To create or reset the administrator account manually at any time:
1. Navigate to:
   ```text
   http://localhost/CampusFix/database/create_admin.php
   ```
   *or run via CLI:*
   ```powershell
   & "C:\xampp\php\php.exe" "C:\xampp\htdocs\CampusFix\database\create_admin.php"
   ```
2. The script will securely hash the password with `password_hash()` and initialize `admin@campusfix.edu`.
3. **Security Notice:** Remove or restrict access to `database/create_admin.php` on live servers.

---

## 13. Demo Credentials (DEMO ONLY)

> [!NOTE]
> All passwords listed below are for local evaluation and academic demonstration only.

| Role | Name | Email Address | Password | Student ID |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | System Administrator | `admin@campusfix.edu` | `admin123` | *N/A* |
| **Student** | Nadia Rahman | `nadia@campus.edu` | `password123` | `STU-2024-001` |
| **Student** | Arif Hasan | `arif@campus.edu` | `password123` | `STU-2024-002` |
| **Student** | Demo Student | `demo@campus.edu` | `password123` | `STU-2024-003` |

---

## 14. Security Implementation
- **Password Protection:** Stored using `password_hash($pass, PASSWORD_DEFAULT)` and verified using timing-safe `password_verify()`. Raw passwords are never stored or logged.
- **SQL Injection Prevention:** 100% of database queries containing user parameters use PDO prepared statements with bounded parameters (`?`). Emulation is disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Cross-Site Scripting (XSS) Prevention:** All dynamic data rendered in HTML templates passes through the custom `e()` helper function which executes `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
- **Cross-Site Request Forgery (CSRF):** Every state-changing form (Registration, Login, Create Complaint, Edit Complaint, Delete Complaint, Profile Update, Admin Status Update) includes a hidden cryptographically secure token verified via `hash_equals()`.
- **Insecure Direct Object Reference (IDOR) Protection:** Student detail, edit, and delete operations query using both the record ID and the logged-in session ID (`WHERE id = ? AND user_id = ?`). Attempting to manipulate URL IDs yields immediate access denial.
- **State Integrity:** Newly created complaints are forced to `Pending` on the server. Student edit forms ignore any attempt to modify status or complaint codes.
- **HTTP Method Safety:** Delete actions require `POST` requests with CSRF tokens; `GET` delete requests are rejected (`405 Method Not Allowed`).
- **Session Hardening:** Calls `session_regenerate_id(true)` immediately after successful login to mitigate session fixation attacks.

---

## 15. Known Limitations
- File/photo attachment uploads are intentionally omitted to keep the project lightweight and reliable for viva defense without file permission issues.
- Email/SMS dispatch is omitted to prevent external SMTP dependencies during offline examination environments.
- Single campus organization model (departments are not split into multi-tenant sub-organizations).

---

## 16. Future Improvements
- **Photo Evidence:** Allow students to upload photos of physical damage (JPEG/PNG).
- **Automated Notifications:** Integrate email alerts via PHPMailer when a complaint is marked `In Progress` or `Resolved`.
- **Department Routing:** Auto-assign complaints to specific facility departments (e.g. Electrical, Plumbing, IT).
- **Analytical Charts:** Render Chart.js visual breakdowns of complaints by status, category, and average resolution time.

---

## 17. Project Report / Screenshot Checklist

Capture these 15 screenshots for your laboratory report or presentation slides:

1. **Home Page (`index.php`):** Hero section "Report. Track. Resolve." and category grid.
2. **Registration Page (`register.php`):** Student sign-up form with validation indicators.
3. **Login Page (`login.php`):** Clean login card with demo hints.
4. **Student Dashboard (`dashboard.php`):** 4 summary metric cards and recent complaints table.
5. **New Complaint Form (`complaint_create.php`):** Submission form with category and location inputs.
6. **My Complaints List (`complaints.php`):** Filtered complaint table with status and priority badges.
7. **Search / Filter Results (`complaints.php?q=...`):** Search results showing active filter badges.
8. **Complaint Details View (`complaint_view.php?id=...`):** Full problem description and timeline.
9. **Edit Complaint (`complaint_edit.php?id=...`):** Edit form for a Pending complaint.
10. **Admin Dashboard (`admin/dashboard.php`):** 5 global metric cards and campus-wide recent list.
11. **Admin Complaints Queue (`admin/complaints.php`):** Global complaints list with student names.
12. **Admin Status & Resolution Update (`admin/complaint_view.php?id=...`):** Controls showing status change and resolution note textarea.
13. **Admin User Directory (`admin/users.php`):** Read-only table of registered users.
14. **phpMyAdmin `users` Table:** Screenshot showing hashed passwords and roles.
15. **phpMyAdmin `complaints` Table:** Screenshot showing schema, foreign key, and complaint codes.

---

## 18. Live Demonstration Script

Follow this sequential walkthrough during your project viva or live demonstration:

1. **Open Home Page:** Navigate to `http://localhost/CampusFix`. Explain the system purpose: *"CampusFix is a full-stack campus complaint tracking system built with PHP 8, PDO, and Bootstrap 5."*
2. **Register a New Student:** Click **Register**. Create an account for a new student (e.g., `Tanvir Ahmed`, ID: `STU-2024-099`, email: `tanvir@campus.edu`, password: `password123`).
3. **Login as New Student:** Log in with the newly created student credentials. Observe that the dashboard opens with 0 total complaints and an inviting empty state.
4. **Create a Complaint:** Click **+ New Complaint**. Enter:
   - Title: `Projector lamp failure in Lab 3`
   - Category: `Classroom`
   - Location: `Academic Building 1, Lab 3`
   - Priority: `High`
   - Description: `Projector fails to turn on and displays a solid red warning lamp.`
   Click **Submit Complaint**.
5. **Show Code Generation & Pending State:** Notice the tracking code format (`CMP-0009`) and the initial status badge: **Pending** (yellow).
6. **Demonstrate Combined Filters:** Go to **My Complaints**. Type `Projector` into the search box, set Priority to `High`, and click **Filter**. Show how matching records are filtered dynamically.
7. **Demonstrate Pending Edit:** Open the complaint details, click **Edit Complaint**, modify the location to `Academic Building 1, Lab 3 (North Wing)`, and save.
8. **Demonstrate Ownership Security (URL Manipulation Defense):** Note the complaint ID in the URL (`id=9`). In the address bar, attempt to change the URL to `id=3` (belonging to student Arif). Observe that access is immediately denied: *"You do not have permission to access that complaint."*
9. **Log Out Student:** Click **Logout**.
10. **Log In as Admin:** Enter `admin@campusfix.edu` / `admin123`.
11. **Open Admin Dashboard:** Show the 5 global cards (Total, Pending, In Progress, Resolved, High Priority) and point out the newly submitted complaint in the campus queue.
12. **Update Status to In Progress:** Click **Manage** on the complaint. Update the status to **In Progress** and click **Update Complaint Status**.
13. **Resolve Complaint with Resolution Note:** Return to the management form. Select status **Resolved**, enter the official note: *"Technician replaced the lamp module and calibrated the display on Sep 26."*, and submit.
14. **Verify Student View & Locked Controls:** Log out as admin, log back in as student `tanvir@campus.edu`. Open the complaint:
    - Status now displays **Resolved** (green).
    - The official administrative resolution note is displayed in a dedicated green card.
    - The **Edit** and **Delete** buttons are automatically locked and hidden.
15. **Conclude Demo:** Log out cleanly.

---

## 19. Viva Cheat Sheet (Questions & Answers)

### Q1: Why did you choose PHP 8 and MySQL over other stacks?
> **Answer:** PHP and MySQL are native to standard web hosting environments like XAMPP, require zero external build tools (like npm or Composer), and provide direct native database access through PDO. This makes the architecture simple to explain, highly performant, and fully compliant with university curriculum standards.

### Q2: What does CRUD stand for, and where is it implemented in CampusFix?
> **Answer:** CRUD stands for **Create, Read, Update, and Delete**:
> - **Create:** Students submit complaints (`complaint_create.php`).
> - **Read:** Students view their own complaints (`complaints.php`, `complaint_view.php`); administrators view all complaints (`admin/complaints.php`).
> - **Update:** Students edit details while in Pending status (`complaint_edit.php`); administrators update priority, status, and resolution notes (`admin/complaint_update.php`).
> - **Delete:** Students delete unwanted Pending complaints (`complaint_delete.php`).

### Q3: How are passwords stored and verified securely?
> **Answer:** Passwords are never stored in plain text. When a user registers, PHP's `password_hash($password, PASSWORD_DEFAULT)` creates a salted bcrypt hash. Upon login, `password_verify($input, $hash)` performs a timing-safe cryptographic comparison.

### Q4: How is SQL Injection prevented?
> **Answer:** SQL injection is prevented by exclusively using **PDO prepared statements** with parameter binding (`?`). User input is sent separately from the SQL statement structure to the MySQL parser, ensuring user input can never be executed as SQL commands. Emulated prepares are disabled (`PDO::ATTR_EMULATE_PREPARES => false`).

### Q5: How do you prevent students from viewing or editing another student's complaints?
> **Answer:** We enforce **server-side authorization and ownership scoping**. We never trust the complaint ID from the URL alone. In student queries, we strictly query:
> ```sql
> WHERE id = ? AND user_id = ?
> ```
> where `user_id` is retrieved directly from `$_SESSION['user_id']`. If a student attempts to edit the URL to view another student's ID, the query returns no rows and access is blocked.

### Q6: What is Role-Based Access Control (RBAC) and how does CampusFix enforce it?
> **Answer:** RBAC restricts resource access based on user roles. In `includes/auth.php`, `requireStudent()` restricts pages to students and redirects admins, while `requireAdmin()` ensures only sessions with `$_SESSION['role'] === 'admin'` can access any page in the `/admin/` directory.

### Q7: Why do we have both client-side and server-side validation?
> **Answer:** Client-side validation (HTML5 attributes and JavaScript) enhances user experience by providing immediate feedback without network latency. However, client-side validation can be bypassed by disabling JavaScript or using tools like cURL/Postman. Therefore, **server-side validation in PHP is authoritative and mandatory** for security and data integrity.

### Q8: What database relationship exists between users and complaints?
> **Answer:** A **One-to-Many (1:N)** relationship. One user can submit multiple complaints, but each complaint belongs to exactly one user. This is enforced via a foreign key constraint:
> ```sql
> CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
> ```
> `ON DELETE CASCADE` guarantees that if a user account is deleted, all their associated complaints are cleaned up automatically without leaving orphaned records.

### Q9: Why are PHP sessions used?
> **Answer:** HTTP is a stateless protocol; each request is independent. PHP sessions store authenticated state (`user_id`, `role`, `full_name`) on the server across multiple HTTP requests, identified by an encrypted session cookie sent by the browser.

### Q10: What is CSRF and how does CampusFix defend against it?
> **Answer:** **Cross-Site Request Forgery (CSRF)** occurs when a malicious website tricks an authenticated user's browser into submitting an unauthorized state-changing request (such as deleting a complaint). CampusFix defends against this by generating a unique, cryptographically random `csrf_token` stored in the session and embedding it as a hidden field in all POST forms. The server validates the submitted token using `hash_equals()` before executing any database write.
