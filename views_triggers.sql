-- ============================================================================
-- LIBRARY MANAGEMENT SYSTEM - VIEWS & TRIGGERS (views_triggers.sql)
-- ============================================================================
-- ⚠ IMPORTANT: This file requires elevated MySQL privileges:
--     • CREATE VIEW
--     • CREATE TRIGGER (+ SUPER or TRIGGER privilege)
--
-- These are NOT available on free shared hosting (e.g., InfinityFree #1142).
--
-- Run this file ONLY on:
--   • Local XAMPP (fully supported)
--   • Paid/upgraded hosting plans that grant the above privileges
--
-- Import AFTER schema.sql and data.sql have been successfully imported.
-- ============================================================================

-- ============================================================================
-- REQUIRED VIEWS (Section 6)
-- ============================================================================

-- View 1: v_issued_books
-- All books currently issued and not yet returned.
CREATE OR REPLACE VIEW v_issued_books AS
SELECT
    i.issue_id,
    b.book_id,
    b.isbn,
    b.title          AS book_title,
    b.author,
    c.category_name,
    m.member_id,
    m.name           AS member_name,
    m.member_type,
    s.name           AS issued_by_staff,
    i.issue_date,
    i.due_date,
    DATEDIFF(CURRENT_DATE, i.due_date) AS days_past_due
FROM issue i
JOIN book     b ON i.book_id   = b.book_id
JOIN category c ON b.category_id = c.category_id
JOIN member   m ON i.member_id = m.member_id
JOIN staff    s ON i.staff_id  = s.staff_id
WHERE i.return_date IS NULL;

-- View 2: v_overdue_books
-- Active loans past their 14-day due date with accrued fine (Rs. 2/day).
CREATE OR REPLACE VIEW v_overdue_books AS
SELECT
    i.issue_id,
    b.isbn,
    b.title          AS book_title,
    m.member_id,
    m.name           AS member_name,
    m.email          AS member_email,
    m.phone          AS member_phone,
    m.member_type,
    i.issue_date,
    i.due_date,
    DATEDIFF(CURRENT_DATE, i.due_date)          AS days_overdue,
    (DATEDIFF(CURRENT_DATE, i.due_date) * 2.00) AS accrued_fine_inr
FROM issue i
JOIN book   b ON i.book_id   = b.book_id
JOIN member m ON i.member_id = m.member_id
WHERE i.return_date IS NULL
  AND i.due_date < CURRENT_DATE;

-- ============================================================================
-- DATABASE TRIGGERS
-- ============================================================================

-- Trigger 1: trg_before_issue_insert
-- Before issuing a book, enforce three business rules at the DB level:
--   1. Member must not be Suspended.
--   2. Member must not exceed active loan limit (Student: 3, Faculty: 5).
--   3. Book must have at least 1 available copy.
--   4. Auto-set due_date = issue_date + 14 days if caller omits it.
CREATE TRIGGER trg_before_issue_insert
BEFORE INSERT ON issue
FOR EACH ROW
BEGIN
    DECLARE v_avail        INT;
    DECLARE v_status       VARCHAR(20);
    DECLARE v_type         VARCHAR(20);
    DECLARE v_active_loans INT;
    DECLARE v_max_allowed  INT;

    -- Rule 1: Check member status
    SELECT status, member_type INTO v_status, v_type
    FROM member WHERE member_id = NEW.member_id;

    IF v_status = 'Suspended' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Business Rule Violation: Suspended member cannot borrow books.';
    END IF;

    -- Rule 2: Check active loan limit
    SELECT COUNT(*) INTO v_active_loans
    FROM issue
    WHERE member_id = NEW.member_id AND return_date IS NULL;

    SET v_max_allowed = IF(v_type = 'Faculty', 5, 3);

    IF v_active_loans >= v_max_allowed THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Business Rule Violation: Member has reached maximum allowed active books.';
    END IF;

    -- Rule 3: Check available copies
    SELECT available_copies INTO v_avail
    FROM book WHERE book_id = NEW.book_id;

    IF v_avail <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Business Rule Violation: Book has 0 available copies.';
    END IF;

    -- Rule 4: Auto-set due_date if not provided
    IF NEW.due_date IS NULL THEN
        SET NEW.due_date = DATE_ADD(NEW.issue_date, INTERVAL 14 DAY);
    END IF;
END$$
