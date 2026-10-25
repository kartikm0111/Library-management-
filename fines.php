<?php
require_once __DIR__ . '/header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay_fine') {
    $fine_id = (int)($_POST['fine_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE fine SET paid_status = 'Paid', paid_date = CURRENT_DATE WHERE fine_id = ?");
    $stmt->bind_param("i", $fine_id);
    if ($stmt->execute()) {
        $msg = "Fine #" . $fine_id . " marked as Paid.";
    }
    $stmt->close();
}

$fines_res = $conn->query("
    SELECT f.fine_id, f.issue_id, f.amount, f.paid_status, f.paid_date,
           i.due_date, i.return_date,
            DATEDIFF(i.return_date, i.due_date) AS days_late,
           b.title AS book_title, m.name AS member_name, m.member_type
    FROM fine f
    JOIN issue i ON f.issue_id = i.issue_id
    JOIN book b ON i.book_id = b.book_id
    JOIN member m ON i.member_id = m.member_id
    ORDER BY f.paid_status DESC, f.fine_id DESC
");
?>

<div class="page-header">
    <h1>Library Fine Ledger &amp; Payment Collection</h1>
</div>

<?php if ($msg !== ''): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<div class="card">
    <h2>Assessed Late Return Fines (₹2.00 / Overdue Day)</h2>
    <table>
        <thead>
            <tr>
                <th>Fine ID</th>
                <th>Issue ID</th>
                <th>Member Name</th>
                <th>Book Title</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Days Late</th>
                <th>Fine Amount</th>
                <th>Status</th>
                <th>Paid Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($f = $fines_res->fetch_assoc()): ?>
                <tr>
                    <td>#<?= (int)$f['fine_id'] ?></td>
                    <td>#<?= (int)$f['issue_id'] ?></td>
                    <td><strong><?= h($f['member_name']) ?></strong> (<?= h($f['member_type']) ?>)</td>
                    <td><?= h($f['book_title']) ?></td>
                    <td><?= h($f['due_date']) ?></td>
                    <td><?= h($f['return_date']) ?></td>
                    <td><?= (int)$f['days_late'] ?> days</td>
                    <td><strong>₹<?= number_format((float)$f['amount'], 2) ?></strong></td>
                    <td>
                        <?php if ($f['paid_status'] === 'Paid'): ?>
                            <span class="badge badge-success">Paid</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Unpaid</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($f['paid_date'] ?? '—') ?></td>
                    <td>
                        <?php if ($f['paid_status'] === 'Unpaid'): ?>
                            <form method="POST" action="fines.php" style="display:inline;">
                                <input type="hidden" name="action" value="pay_fine">
                                <input type="hidden" name="fine_id" value="<?= (int)$f['fine_id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Mark as Paid</button>
                            </form>
                        <?php else: ?>
                            <span style="color:var(--muted); font-size:12px;">Settled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
