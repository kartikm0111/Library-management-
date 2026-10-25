-- ============================================================================
-- LIBRARY MANAGEMENT SYSTEM - SEED DATA (data.sql)
-- Includes: 6 categories, 22 books, 12 members, 3 staff, 20 issues, 6 fines
-- Reference Date for Seed Data: 2026-10-24
-- ============================================================================

-- NOTE: Remove or comment out USE <dbname> when importing on hosted servers
-- (e.g., InfinityFree) where the database name differs from local.
-- phpMyAdmin already targets the selected database; USE is not needed.
-- USE library_db;

-- ============================================================
-- DATA RESET: DELETE instead of TRUNCATE to avoid MySQL #1701
-- (TRUNCATE on a FK-referenced table fails in some phpMyAdmin
--  versions even with FOREIGN_KEY_CHECKS = 0; DELETE does not)
-- Delete order: child tables first, then parent tables
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM fine;
DELETE FROM issue;
DELETE FROM book;
DELETE FROM member;
DELETE FROM staff;
DELETE FROM category;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Seed Categories (6 rows)
INSERT INTO category (category_id, category_name) VALUES
(1, 'Computer Science'),
(2, 'Mathematics & Statistics'),
(3, 'Electronics & Communication'),
(4, 'Mechanical Engineering'),
(5, 'Literature & Humanities'),
(6, 'Management & Economics');

-- 2. Seed Books (22 rows)
-- Note: Book 6 (Clean Code) starts with available_copies = 2 during issue seeding
-- and is updated to 0 immediately after Issues #12 and #15 are inserted below.
INSERT INTO book (book_id, isbn, title, author, publisher, pub_year, category_id, total_copies, available_copies) VALUES
(1,  '978-0262033848', 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 1, 5, 4),
(2,  '978-0073523323', 'Database System Concepts', 'Abraham Silberschatz', 'McGraw-Hill', 2019, 1, 4, 3),
(3,  '978-1119800361', 'Operating System Concepts', 'Abraham Silberschatz', 'Wiley', 2021, 1, 4, 3),
(4,  '978-0133594140', 'Computer Networks', 'Andrew S. Tanenbaum', 'Pearson', 2011, 1, 3, 2),
(5,  '978-0134685991', 'Effective Java', 'Joshua Bloch', 'Addison-Wesley', 2018, 1, 3, 3),
(6,  '978-0132350884', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 1, 2, 2),
(7,  '978-0201633610', 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', 1994, 1, 3, 2),
(8,  '978-0136042594', 'Artificial Intelligence: A Modern Approach', 'Stuart Russell', 'Pearson', 2020, 1, 4, 3),
(9,  '978-0470458365', 'Advanced Engineering Mathematics', 'Erwin Kreyszig', 'Wiley', 2011, 2, 5, 4),
(10, '978-0980232776', 'Introduction to Linear Algebra', 'Gilbert Strang', 'Wellesley-Cambridge', 2016, 2, 3, 3),
(11, '978-0134995599', 'Probability and Statistics for Engineers', 'Ronald E. Walpole', 'Pearson', 2016, 2, 3, 3),
(12, '978-0071289566', 'Discrete Mathematics and Its Applications', 'Kenneth H. Rosen', 'McGraw-Hill', 2018, 2, 4, 3),
(13, '978-0199339136', 'Microelectronic Circuits', 'Adel S. Sedra', 'Oxford University Press', 2014, 3, 4, 4),
(14, '978-0133943238', 'Digital Design', 'M. Morris Mano', 'Pearson', 2017, 3, 4, 3),
(15, '978-0132139619', 'Signals and Systems', 'Alan V. Oppenheim', 'Prentice Hall', 1996, 3, 2, 2),
(16, '978-0073398174', 'Shigley''s Mechanical Engineering Design', 'Richard G. Budynas', 'McGraw-Hill', 2019, 4, 3, 3),
(17, '978-1259822674', 'Thermodynamics: An Engineering Approach', 'Yunus A. Cengel', 'McGraw-Hill', 2018, 4, 4, 4),
(18, '978-0073380322', 'Fluid Mechanics', 'Frank M. White', 'McGraw-Hill', 2015, 4, 3, 3),
(19, '978-0140449136', 'The Odyssey', 'Homer', 'Penguin Classics', 2003, 5, 2, 2),
(20, '978-8172234980', 'Wings of Fire', 'A. P. J. Abdul Kalam', 'Universities Press', 1999, 5, 5, 5),
(21, '978-0356500553', 'Principles of Microeconomics', 'N. Gregory Mankiw', 'Cengage Learning', 2020, 6, 3, 3),
(22, '978-0134202266', 'Marketing Management', 'Philip Kotler', 'Pearson', 2016, 6, 3, 3);

-- 3. Seed Members (12 rows: 9 Students, 3 Faculty)
-- Note: Members 5 (Vikram Singh) and 9 (Arjun Reddy) were Active when they borrowed
-- books in September 2026, and are updated to 'Suspended' below after their unpaid fines.
INSERT INTO member (member_id, name, email, phone, member_type, join_date, status) VALUES
(1,  'Aarav Sharma',      'aarav.sharma@college.edu.in',      '9876543210', 'Student', '2025-08-01', 'Active'),
(2,  'Priya Patel',       'priya.patel@college.edu.in',       '9876543211', 'Student', '2025-08-03', 'Active'),
(3,  'Rohan Verma',       'rohan.verma@college.edu.in',       '9876543212', 'Student', '2025-08-10', 'Active'),
(4,  'Ananya Iyer',       'ananya.iyer@college.edu.in',       '9876543213', 'Student', '2025-08-15', 'Active'),
(5,  'Vikram Singh',      'vikram.singh@college.edu.in',      '9876543214', 'Student', '2025-09-01', 'Active'),
(6,  'Sneha Gupta',       'sneha.gupta@college.edu.in',       '9876543215', 'Student', '2025-09-05', 'Active'),
(7,  'Kabir Nair',        'kabir.nair@college.edu.in',        '9876543216', 'Student', '2025-09-12', 'Active'),
(8,  'Meera Joshi',       'meera.joshi@college.edu.in',       '9876543217', 'Student', '2026-01-10', 'Active'),
(9,  'Arjun Reddy',       'arjun.reddy@college.edu.in',       '9876543218', 'Student', '2026-01-20', 'Active'),
(10, 'Dr. Rajesh Kumar',  'rajesh.kumar@college.edu.in',      '9811122233', 'Faculty', '2024-07-01', 'Active'),
(11, 'Dr. Sunita Rao',    'sunita.rao@college.edu.in',        '9811122234', 'Faculty', '2024-07-15', 'Active'),
(12, 'Prof. Amitabh Das', 'amitabh.das@college.edu.in',       '9811122235', 'Faculty', '2025-01-05', 'Active');

-- 4. Seed Staff (3 rows)
-- Default password for all staff accounts is: password (verified via PHP password_verify)
INSERT INTO staff (staff_id, name, username, password_hash) VALUES
(1, 'Ramesh Chandra (Chief Librarian)', 'admin',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'Kavita Mishra (Assistant Staff)',  'kavita',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'Suresh Yadav (Circulation Desk)',  'suresh',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 5. Seed Issue Records (20 rows: 9 Returned, 11 Currently Issued [4 Overdue, 7 On-time])
-- Reference current date: 2026-10-24
INSERT INTO issue (issue_id, book_id, member_id, staff_id, issue_date, due_date, return_date) VALUES
-- Returned on time (no fine)
(1,  1,  2,  1, '2026-09-01', '2026-09-15', '2026-09-14'),
(2,  5,  3,  2, '2026-09-05', '2026-09-19', '2026-09-18'),
(3,  10, 4,  1, '2026-09-10', '2026-09-24', '2026-09-22'),
-- Returned late (with fine rows in `fine` table)
(4,  2,  1,  1, '2026-09-01', '2026-09-15', '2026-09-20'),
(5,  4,  5,  2, '2026-09-02', '2026-09-16', '2026-09-26'),
(6,  8,  6,  3, '2026-09-10', '2026-09-24', '2026-09-28'),
(7,  13, 7,  1, '2026-09-12', '2026-09-26', '2026-10-03'),
(8,  16, 9,  2, '2026-09-15', '2026-09-29', '2026-10-09'),
(9,  20, 10, 1, '2026-09-20', '2026-10-04', '2026-10-07'),
-- Currently Issued & Overdue (return_date IS NULL, due_date < '2026-10-24')
(10, 1,  1,  1, '2026-10-01', '2026-10-15', NULL),
(11, 2,  1,  2, '2026-10-03', '2026-10-17', NULL),
(12, 6,  2,  1, '2026-10-05', '2026-10-19', NULL),
(13, 9,  3,  3, '2026-10-02', '2026-10-16', NULL),
-- Currently Issued & Within Due Date (return_date IS NULL, due_date >= '2026-10-24')
(14, 3,  1,  1, '2026-10-15', '2026-10-29', NULL),
(15, 6,  4,  2, '2026-10-16', '2026-10-30', NULL),
(16, 4,  10, 1, '2026-10-18', '2026-11-01', NULL),
(17, 7,  10, 2, '2026-10-19', '2026-11-02', NULL),
(18, 8,  11, 3, '2026-10-20', '2026-11-03', NULL),
(19, 12, 6,  1, '2026-10-17', '2026-10-31', NULL),
(20, 14, 7,  2, '2026-10-20', '2026-11-03', NULL);

-- 6. Seed Fine Records (6 rows for the 6 late returns in issues 4..9)
INSERT INTO fine (fine_id, issue_id, amount, paid_status, paid_date) VALUES
(1, 4, 10.00, 'Paid',   '2026-09-20'),
(2, 5, 20.00, 'Unpaid', NULL),
(3, 6,  8.00, 'Paid',   '2026-09-29'),
(4, 7, 14.00, 'Paid',   '2026-10-04'),
(5, 8, 20.00, 'Unpaid', NULL),
(6, 9,  6.00, 'Paid',   '2026-10-07');

-- 7. Sync Final Inventory & Member Suspension State for Test Cases 2 & 3
-- Book 6 (Clean Code) has both copies issued in Issues #12 and #15 -> available_copies = 0
UPDATE book SET available_copies = 0 WHERE book_id = 6;

-- Members 5 (Vikram Singh) and 9 (Arjun Reddy) are suspended due to unpaid Rs. 20 fines
UPDATE member SET status = 'Suspended' WHERE member_id IN (5, 9);