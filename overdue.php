<?php
require_once __DIR__ . '/header.php';

// Uses view v_overdue_books
$res = $conn->query("SELECT * FROM v_overdue_books ORDER BY days_overdue DESC");
?>

<div class="page-header">
    <h1>Overdue Books List</h1>
</div>

<div class="card">
    <h2>Active Loans Past 14-Day Due Date (View: <code>v_overdue_books</code>)</h2>
    <table>
        <thead>
            <tr>
                <th>Issue ID</th>
                <th>ISBN</th>
                <th>Book Title</th>
                <th>Borrower Name</th>
                <th>Contact Email &amp; Phone</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Days Overdue</th>
                <th>Accrued Fine (₹2/day)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td>#<?= (int)$r['issue_id'] ?></td>
                    <td><?= h($r['isbn']) ?></td>
                    <td><strong><?= h($r['book_title']) ?></strong></td>
                    <td><?= h($r['member_name']) ?> (<?= h($r['member_type']) ?>)</td>
                    <td><?= h($r['member_email']) ?><br><span style="color:var(--muted); font-size:12px;"><?= h($r['member_phone']) ?></span></td>
                    <td><?= h($r['issue_date']) ?></td>
                    <td><?= h($r['due_date']) ?></td>
                    <td><span class="badge badge-danger"><?= (int)$r['days_overdue'] ?> days</span></td>
                    <td><strong>₹<?= number_format((float)$r['accrued_fine_inr'], 2) ?></strong></td>
                    <td><a href="return.php?issue_id=<?= (int)$r['issue_id'] ?>" class="btn btn-accent btn-sm">Return &amp; Bill</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
