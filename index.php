<?php
require_once __DIR__ . '/header.php';

// 1. Total Books & Available Copies
$res_books = $conn->query("SELECT COUNT(*) AS titles, COALESCE(SUM(total_copies), 0) AS total_copies, COALESCE(SUM(available_copies), 0) AS avail_copies FROM book");
$book_stats = $res_books->fetch_assoc();

// 2. Currently Issued Books
$res_issued = $conn->query("SELECT COUNT(*) AS active_issues FROM issue WHERE return_date IS NULL");
$issued_stats = $res_issued->fetch_assoc();

// 3. Overdue Books
$res_overdue = $conn->query("SELECT COUNT(*) AS overdue_count FROM issue WHERE return_date IS NULL AND due_date < CURRENT_DATE");
$overdue_stats = $res_overdue->fetch_assoc();

// 4. Unpaid Fines
$res_fines = $conn->query("SELECT COUNT(*) AS unpaid_count, COALESCE(SUM(amount), 0.00) AS unpaid_sum FROM fine WHERE paid_status = 'Unpaid'");
$fine_stats = $res_fines->fetch_assoc();

// Recent 8 Active Issues
$recent_sql = "
    SELECT i.issue_id, b.isbn, b.title, m.name AS member_name, m.member_type,
           i.issue_date, i.due_date,
           DATEDIFF(CURRENT_DATE, i.due_date) AS days_overdue
    FROM issue i
    JOIN book b ON i.book_id = b.book_id
    JOIN member m ON i.member_id = m.member_id
    WHERE i.return_date IS NULL
    ORDER BY i.due_date ASC
    LIMIT 8
";
$recent_issues = $conn->query($recent_sql);
?>

<div class="page-header">
    <h1>Library Circulation Dashboard</h1>
    <div>
        <a href="issue.php" class="btn btn-primary">+ Issue Book</a>
        <a href="return.php" class="btn btn-accent">Return Book</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Book Copies</div>
        <div class="stat-value"><?= (int)$book_stats['total_copies'] ?></div>
        <div class="stat-sub"><?= (int)$book_stats['titles'] ?> distinct titles &bull; <?= (int)$book_stats['avail_copies'] ?> available</div>
    </div>
    <div class="stat-card accent">
        <div class="stat-label">Currently Issued Books</div>
        <div class="stat-value"><?= (int)$issued_stats['active_issues'] ?></div>
        <div class="stat-sub">14-day standard loan window</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label">Overdue Books</div>
        <div class="stat-value"><?= (int)$overdue_stats['overdue_count'] ?></div>
        <div class="stat-sub">Fine rate: ₹2.00 per overdue day</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-label">Unpaid Fines</div>
        <div class="stat-value">₹<?= number_format((float)$fine_stats['unpaid_sum'], 2) ?></div>
        <div class="stat-sub"><?= (int)$fine_stats['unpaid_count'] ?> pending fine invoice(s)</div>
    </div>
</div>

<div class="card">
    <h2>Active Circulation Priority Queue (Sorted by Earliest Due Date)</h2>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>ISBN</th>
                <th>Book Title</th>
                <th>Borrower</th>
                <th>Type</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $recent_issues->fetch_assoc()): ?>
                <tr>
                    <td>#<?= (int)$row['issue_id'] ?></td>
                    <td><?= h($row['isbn']) ?></td>
                    <td><strong><?= h($row['title']) ?></strong></td>
                    <td><?= h($row['member_name']) ?></td>
                    <td><span class="badge badge-info"><?= h($row['member_type']) ?></span></td>
                    <td><?= h($row['issue_date']) ?></td>
                    <td><?= h($row['due_date']) ?></td>
                    <td>
                        <?php if ((int)$row['days_overdue'] > 0): ?>
                            <span class="badge badge-danger">Overdue (<?= (int)$row['days_overdue'] ?>d &bull; ₹<?= (int)$row['days_overdue'] * 2 ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-success">On Time</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="return.php?issue_id=<?= (int)$row['issue_id'] ?>" class="btn btn-accent btn-sm">Process Return</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
