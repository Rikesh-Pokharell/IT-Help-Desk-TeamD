# 💻 College IT Helpdesk System
A full-featured PHP helpdesk for colleges — ticket submission, tracking, admin management.

---

## 📁 Project Structure
```
helpdesk/
├── index.php               → Redirects to login/dashboard
├── login.php               → Login page
├── register.php            → User registration
├── forgot_password.php     → Password reset
├── dashboard.php           → User & admin dashboard
├── submit_ticket.php       → Submit new ticket (users only)
├── my_tickets.php          → View own tickets (users)
├── ticket.php              → View/reply to single ticket
├── logout.php              → Logout
├── api.php
│
├── admin/
│   ├── tickets.php         → All tickets dashboard (admin)
│   └── users.php  
    └── submit_ticket.php 
│
├── includes/
│   ├── config.php          → DB config, helpers, session
│   ├── header.php          → Shared HTML header + navbar
│   └── footer.php          → Shared footer
│
├── assets/
│   └── css/style.css       → Full stylesheet
│
└── database.sql            → Database setup script
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx with PHP support (XAMPP, WAMP, Laragon, etc.)

### 2. Database Setup
1. Open **phpMyAdmin** or your MySQL client
2. Create a new database called `it_helpdesk`
3. Import `database.sql` (File → Import)
4. This creates all tables and an admin account

### 3. Configure Database
Open `includes/config.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // ← your MySQL username
define('DB_PASS', '');           // ← your MySQL password
define('DB_NAME', 'it_helpdesk');
define('APP_URL', 'http://localhost/helpdesk'); // ← your local URL
```

### 4. Place Files
Copy the `helpdesk/` folder to your web server root:
- XAMPP: `C:/xampp/htdocs/helpdesk/`
- WAMP: `C:/wamp64/www/helpdesk/`
- Laragon: `C:/laragon/www/helpdesk/`

### 5. Access the App
Open: `http://localhost/helpdesk`

---

## 🔐 Default Login

| Role  | Email                 | Password   |
|-------|-----------------------|------------|
| Admin | admin@college.edu     | password   |

> ⚠️ **Change the admin password immediately after first login!**

---

## ✨ Features

### Users (Students / Faculty / Staff)
- ✅ Register with college email & ID
- ✅ Login / Forgot password (verified by college ID)
- ✅ Submit tickets with category, priority, description
- ✅ Track ticket status (Pending / In Progress / Resolved / Closed)
- ✅ View IT support replies
- ✅ Add replies to own tickets
- ✅ Filter & search own tickets

### Admin (IT Support)
- ✅ View all tickets with full details
- ✅ Filter by status, category, priority, user
- ✅ Update ticket status & priority
- ✅ Reply to users from ticket page
- ✅ View all registered users
- ✅ See per-user ticket count
- ✅ Critical tickets highlighted in dashboard
- ✅ Submit ticket by entering user email
- ✅ System automatically finds user by email
- ✅ Ticket is assigned to that user
- ✅ If email not found → error shown
- ✅ Admin-created tickets auto set to "In Progress"
---

## 🛡️ Security Features
- Passwords hashed with `password_hash()` (bcrypt)
- Prepared statements (SQL injection protection)
- `htmlspecialchars()` on all output (XSS protection)
- Session-based auth with role checks
- Users can only access their own tickets
