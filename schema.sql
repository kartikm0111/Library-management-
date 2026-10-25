-- ============================================================================
-- LIBRARY MANAGEMENT SYSTEM - DATABASE SCHEMA (schema.sql)
-- Database: library_db (local XAMPP) / if0_43264977_library_db (InfinityFree)
-- ============================================================================
-- NOTE FOR HOSTED SERVERS (e.g., InfinityFree):
--   The three lines below are commented out because shared hosts do not allow
--   DROP/CREATE DATABASE, and the database name differs from 'library_db'.
--   Select your database in phpMyAdmin BEFORE importing - USE is not needed.
--
-- DROP DATABASE IF EXISTS library_db;
-- CREATE DATABASE library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE library_db;

-- ----------------------------------------------------------------------------
-- Safe re-import: drop existing tables before recreating
-- (child tables first to respect foreign key order)
-- ----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS fine;
DROP TABLE IF EXISTS issue;
DROP TABLE IF EXISTS book;
DROP TABLE IF EXISTS member;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS category;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Table 1: category
-- Stores distinct book classifications
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 2: book
-- Stores book catalog and copy inventory counts
-- Note: author is stored as a single attribute for scope simplicity; see report
-- for M:N book_author normalization analysis.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS book (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    publisher VARCHAR(150) NOT NULL,
    pub_year YEAR NOT NULL,
    category_id INT NOT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_book_category FOREIGN KEY (category_id)
        REFERENCES category(category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_total_copies CHECK (total_copies >= 0),
    CONSTRAINT chk_available_copies CHECK (available_copies >= 0 AND available_copies <= total_copies)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 3: member
-- Stores student and faculty borrowers
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS member (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    member_type ENUM('Student', 'Faculty') NOT NULL DEFAULT 'Student',
    join_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    status ENUM('Active', 'Suspended') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 4: staff
-- Stores library staff credentials (passwords hashed via PHP password_hash)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 5: issue
-- Stores circulation transactions (loan period = 14 days)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS issue (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    staff_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    CONSTRAINT fk_issue_book FOREIGN KEY (book_id)
        REFERENCES book(book_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_issue_member FOREIGN KEY (member_id)
        REFERENCES member(member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_issue_staff FOREIGN KEY (staff_id)
        REFERENCES staff(staff_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_issue_dates CHECK (due_date >= issue_date)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 6: fine
-- Stores overdue penalties (Rs. 2 per day after due_date)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fine (
    fine_id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL UNIQUE,
    amount DECIMAL(8,2) NOT NULL CHECK (amount > 0),
    paid_status ENUM('Paid', 'Unpaid') NOT NULL DEFAULT 'Unpaid',
    paid_date DATE DEFAULT NULL,
    CONSTRAINT fk_fine_issue FOREIGN KEY (issue_id)
        REFERENCES issue(issue_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================================
-- VIEWS & TRIGGERS
-- ============================================================================
-- NOTE: CREATE VIEW and CREATE TRIGGER require elevated privileges that are
-- NOT available on free shared hosting plans (e.g., InfinityFree).
--
-- The views and triggers have been moved to a separate file:
--   → views_triggers.sql
--
-- Run views_triggers.sql ONLY on:
--   • Local XAMPP (always supported)
--   • Paid/upgraded hosted MySQL plans that grant CREATE VIEW + CREATE TRIGGER
-- ============================================================================
