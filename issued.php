<?php
require_once __DIR__ . '/header.php';

// Uses view v_issued_books
$res = $conn->query("SELECT * FROM v_issued_books ORDER BY due_date ASC");
?>

<div class="page-header">
    <h1>Currently Issued Books (Active Loans)</h1>
    <a href="issue.php" class="btn btn-primary">+ Issue New Book</a>
</div>

<div class="card">
    <h2>All Unreturned Books (View: <code>v_issued_books</code>)</h2>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>ISBN</th>
                <th>Book Title</th>
                <th>Category</th>
                <th>Borrower</th>
                <th>Member Type</th>
                <th>Issued By</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td>#<?= (int)$r['issue_id'] ?></td>
                    <td><?= h($r['isbn']) ?></td>
                    <td><strong><?= h($r['book_title']) ?></strong></td>
                    <td><?= h($r['category_name']) ?></td>
                    <td><?= h($r['member_name']) ?></td>
                    <td><span class="badge badge-info"><?= h($r['member_type']) ?></span></td>
                    <td><?= h($r['issued_by_staff']) ?></td>
                    <td><?= h($r['issue_date']) ?></td>
                    <td><?= h($r['due_date']) ?></td>
                    <td>
                        <?php if ((int)$r['days_past_due'] > 0): ?>
                            <span class="badge badge-danger"><?= (int)$r['days_past_due'] ?>d Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-success"><?= abs((int)$r['days_past_due']) ?>d Remaining</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
