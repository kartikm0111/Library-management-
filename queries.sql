-- ============================================================================
-- LIBRARY MANAGEMENT SYSTEM - 8 ANALYTICAL SQL QUERIES & 2 VIEWS (queries.sql)
-- Section 6 SQL Requirements
-- ============================================================================

-- NOTE: Remove or comment out USE <dbname> when running on hosted servers
-- (e.g., InfinityFree) where the database name differs from local.
-- phpMyAdmin already targets the selected database; USE is not needed.
-- USE library_db;

-- ----------------------------------------------------------------------------
-- Query 1 [JOIN of 3 Tables]: List all currently issued books along with
-- borrower name, member type, category name, issue date, and due date.
-- ----------------------------------------------------------------------------
SELECT 
    i.issue_id,
    b.isbn,
    b.title AS book_title,
    c.category_name,
    m.name AS member_name,
    m.member_type,
    i.issue_date,
    i.due_date
FROM issue i
JOIN book b ON i.book_id = b.book_id
JOIN category c ON b.category_id = c.category_id
JOIN member m ON i.member_id = m.member_id
WHERE i.return_date IS NULL
ORDER BY i.due_date ASC;

-- ----------------------------------------------------------------------------
-- Query 2 [JOIN of 3 Tables]: Retrieve complete fine details showing member
-- name, book title, return date, fine amount, and payment status.
-- ----------------------------------------------------------------------------
SELECT 
    f.fine_id,
    m.name AS member_name,
    m.member_type,
    b.title AS book_title,
    i.due_date,
    i.return_date,
    f.amount AS fine_amount_inr,
    f.paid_status,
    f.paid_date
FROM fine f
JOIN issue i ON f.issue_id = i.issue_id
JOIN member m ON i.member_id = m.member_id
JOIN book b ON i.book_id = b.book_id
ORDER BY f.fine_id ASC;

-- ----------------------------------------------------------------------------
-- Query 3 [GROUP BY]: Count the total titles, total physical copies, and
-- currently available copies in each book category.
-- ----------------------------------------------------------------------------
SELECT 
    c.category_id,
    c.category_name,
    COUNT(b.book_id) AS distinct_titles,
    SUM(b.total_copies) AS total_physical_copies,
    SUM(b.available_copies) AS available_copies
FROM category c
LEFT JOIN book b ON c.category_id = b.category_id
GROUP BY c.category_id, c.category_name
ORDER BY total_physical_copies DESC;

-- ----------------------------------------------------------------------------
-- Query 4 [GROUP BY / HAVING]: Find all members who currently hold 2 or more
-- issued books (unreturned).
-- ----------------------------------------------------------------------------
SELECT 
    m.member_id,
    m.name AS member_name,
    m.member_type,
    COUNT(i.issue_id) AS active_issued_books
FROM member m
JOIN issue i ON m.member_id = i.member_id
WHERE i.return_date IS NULL
GROUP BY m.member_id, m.name, m.member_type
HAVING COUNT(i.issue_id) >= 2
ORDER BY active_issued_books DESC;

-- ----------------------------------------------------------------------------
-- Query 5 [Aggregates]: Calculate summary fine metrics (total fines generated,
-- total collected/Paid, total pending/Unpaid, and average fine amount).
-- ----------------------------------------------------------------------------
SELECT 
    COUNT(fine_id) AS total_fine_records,
    SUM(amount) AS total_fines_assessed_inr,
    SUM(CASE WHEN paid_status = 'Paid' THEN amount ELSE 0 END) AS total_fines_collected_inr,
    SUM(CASE WHEN paid_status = 'Unpaid' THEN amount ELSE 0 END) AS total_fines_unpaid_inr,
    ROUND(AVG(amount), 2) AS average_fine_inr
FROM fine;

-- ----------------------------------------------------------------------------
-- Query 6 [Subquery]: List all books in the catalog that have NEVER been issued
-- to any member.
-- ----------------------------------------------------------------------------
SELECT 
    b.book_id,
    b.isbn,
    b.title,
    b.author,
    b.total_copies
FROM book b
WHERE b.book_id NOT IN (
    SELECT DISTINCT book_id FROM issue
)
ORDER BY b.book_id ASC;

-- ----------------------------------------------------------------------------
-- Query 7 [Date Functions - DATEDIFF]: Calculate overdue days and accrued fine
-- (at Rs. 2/day) for all currently unreturned overdue books as of 2026-10-24.
-- ----------------------------------------------------------------------------
SELECT 
    i.issue_id,
    b.title AS book_title,
    m.name AS member_name,
    i.issue_date,
    i.due_date,
    DATEDIFF('2026-10-24', i.due_date) AS overdue_days,
    (DATEDIFF('2026-10-24', i.due_date) * 2.00) AS estimated_fine_inr
FROM issue i
JOIN book b ON i.book_id = b.book_id
JOIN member m ON i.member_id = m.member_id
WHERE i.return_date IS NULL
  AND i.due_date < '2026-10-24'
ORDER BY overdue_days DESC;

-- ----------------------------------------------------------------------------
-- Query 8 [Subquery + Aggregate]: Identify the most borrowed book(s) across
-- all historical and active circulation records.
-- ----------------------------------------------------------------------------
SELECT 
    b.book_id,
    b.isbn,
    b.title,
    b.author,
    COUNT(i.issue_id) AS times_issued
FROM book b
JOIN issue i ON b.book_id = i.book_id
GROUP BY b.book_id, b.isbn, b.title, b.author
HAVING COUNT(i.issue_id) = (
    SELECT MAX(issue_cnt)
    FROM (
        SELECT COUNT(issue_id) AS issue_cnt
        FROM issue
        GROUP BY book_id
    ) AS sub
);

-- ----------------------------------------------------------------------------
-- View Verification Queries
-- ----------------------------------------------------------------------------
SELECT * FROM v_issued_books;
SELECT * FROM v_overdue_books;
