================================================================================
COLLEGE LIBRARY MANAGEMENT SYSTEM (XAMPP + PHP + MySQL)
================================================================================

1. FOLDER STRUCTURE
-------------------
- config.php        : MySQLi database connection & session auth helper
- header.php        : Shared top navigation bar & layout wrapper
- footer.php        : Shared page footer
- style.css         : Standalone CSS3 stylesheet
- login.php         : Staff authentication using password_verify()
- logout.php        : Staff session logout
- index.php         : Circulation Dashboard (summary cards + priority queue)
- books.php         : Add, Edit, Delete, View & Search Books (Title/Author/Category)
- members.php       : Register, Edit, Suspend/Activate & View Members
- issue.php         : Issue Book (enforces quotas, 14-day due date, stock & status)
- return.php        : Return Book (calculates overdue days & Rs. 2/day fine)
- issued.php        : Currently Issued Books list (queries v_issued_books)
- overdue.php       : Overdue Books list (queries v_overdue_books)
- fines.php         : Fine ledger & payment collection (mark Unpaid fines as Paid)
- schema.sql        : Complete DDL for 6 tables, constraints, 2 views, and trigger
- data.sql          : Seed data (6 categories, 22 books, 12 members, 3 staff, 20 issues, 6 fines)
- queries.sql       : All 8 required analytical SQL queries & 2 view checks

2. HOW TO RUN IN XAMPP
----------------------
Step 1: Copy the `library_management_system` folder into `C:\xampp\htdocs\`.
Step 2: Start Apache and MySQL in the XAMPP Control Panel.
Step 3: Open phpMyAdmin (`http://localhost/phpmyadmin`) and import:
        1) `schema.sql`
        2) `data.sql`
Step 4: Open `http://localhost/library_management_system/login.php` in your browser.
Step 5: Log in with default staff credentials:
        Username: admin
        Password: password
