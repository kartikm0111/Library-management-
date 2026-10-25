<?php
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';
$return_summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id    = (int)($_POST['issue_id'] ?? 0);
    $return_date = trim($_POST['return_date'] ?? date('Y-m-d'));

    $stmt = $conn->prepare("
        SELECT i.issue_id, i.book_id, i.issue_date, i.due_date, i.return_date,
               b.title AS book_title, m.name AS member_name
        FROM issue i
        JOIN book b ON i.book_id = b.book_id
        JOIN member m ON i.member_id = m.member_id
        WHERE i.issue_id = ?
    ");
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $issue = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$issue) {
        $err = "Issue record not found.";
    } elseif ($issue['return_date'] !== null) {
        $err = "This book was already returned on " . $issue['return_date'] . ".";
    } elseif ($return_date < $issue['issue_date']) {
        $err = "Return date cannot be earlier than the issue date (" . $issue['issue_date'] . ").";
    } else {
        // Calculate days late and fine (Rs. 2 per day after due_date)
        $due_ts = strtotime($issue['due_date']);
        $ret_ts = strtotime($return_date);
        $diff_days = (int)round(($ret_ts - $due_ts) / 86400);
        $days_late = max(0, $diff_days);
        $fine_amount = $days_late * 2.00;

        $conn->begin_transaction();
        try {
            // 1. Set return_date on issue
            $stmt_u = $conn->prepare("UPDATE issue SET return_date = ? WHERE issue_id = ?");
            $stmt_u->bind_param("si", $return_date, $issue_id);
            $stmt_u->execute();
            $stmt_u->close();

            // 2. Increment available_copies by 1
            $stmt_b = $conn->prepare("UPDATE book SET available_copies = available_copies + 1 WHERE book_id = ?");
            $stmt_b->bind_param("i", $issue['book_id']);
            $stmt_b->execute();
            $stmt_b->close();

            // 3. If late, create a row in fine table
            if ($days_late > 0) {
                $stmt_f = $conn->prepare("INSERT INTO fine (issue_id, amount, paid_status, paid_date) VALUES (?, ?, 'Unpaid', NULL)");
                $stmt_f->bind_param("id", $issue_id, $fine_amount);
                $stmt_f->execute();
                $stmt_f->close();
            }

            $conn->commit();
            $return_summary = [
                'issue_id'    => $issue_id,
                'book_title'  => $issue['book_title'],
                'member_name' => $issue['member_name'],
                'due_date'    => $issue['due_date'],
                'return_date' => $return_date,
                'days_late'   => $days_late,
                'fine_amount' => $fine_amount
            ];
            if ($days_late > 0) {
                $msg = "Book returned " . $days_late . " day(s) late. Automatic fine of ₹" . number_format($fine_amount, 2) . " recorded in Fine table.";
            } else {
                $msg = "Book returned on time! 0 days late — no fine generated.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Error processing return: " . $e->getMessage();
        }
    }
}

$selected_issue_id = (int)($_GET['issue_id'] ?? 0);

// Fetch all unreturned issues
$active_issues = $conn->query("
    SELECT i.issue_id, b.isbn, b.title, m.name AS member_name, m.member_type,
           i.issue_date, i.due_date,
           DATEDIFF(CURRENT_DATE, i.due_date) AS est_days_late
    FROM issue i
    JOIN book b ON i.book_id = b.book_id
    JOIN member m ON i.member_id = m.member_id
    WHERE i.return_date IS NULL
    ORDER BY i.due_date ASC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <h1>Return Book &amp; Fine Calculation</h1>
</div>

<?php if ($msg !== ''): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err !== ''): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2>Process Book Return</h2>
        <form method="POST" action="return.php">
            <div class="form-group">
                <label>Select Active Issue Record</label>
                <select name="issue_id" class="form-control" required>
                    <option value="">-- Choose Issued Book --</option>
                    <?php foreach ($active_issues as $ai): ?>
                        <option value="<?= (int)$ai['issue_id'] ?>" <?= ($selected_issue_id === (int)$ai['issue_id']) ? 'selected' : '' ?>>
                            #<?= (int)$ai['issue_id'] ?> - <?= h($ai['title']) ?> (<?= h($ai['member_name']) ?> | Due: <?= h($ai['due_date']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Return Date</label>
                <input type="date" name="return_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%;">Complete Return &amp; Calculate Fine</button>
        </form>

        <?php if ($return_summary): ?>
            <div style="margin-top:18px; padding-top:14px; border-top:1px solid var(--border);">
                <h2 style="font-size:14px;">Return Receipt Summary</h2>
                <p><strong>Issue ID:</strong> #<?= (int)$return_summary['issue_id'] ?></p>
                <p><strong>Book:</strong> <?= h($return_summary['book_title']) ?></p>
                <p><strong>Member:</strong> <?= h($return_summary['member_name']) ?></p>
                <p><strong>Due Date:</strong> <?= h($return_summary['due_date']) ?></p>
                <p><strong>Return Date:</strong> <?= h($return_summary['return_date']) ?></p>
                <p><strong>Days Late:</strong> <?= (int)$return_summary['days_late'] ?> day(s)</p>
                <p><strong>Calculated Fine (₹2/day):</strong> ₹<?= number_format((float)$return_summary['fine_amount'], 2) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Currently Issued Books Pending Return</h2>
        <table>
            <thead>
                <tr>
                    <th>Issue ID</th>
                    <th>Book Title</th>
                    <th>Member</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Days Late (Today)</th>
                    <th>Est. Fine</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($active_issues as $ai):
                    $late = max(0, (int)$ai['est_days_late']);
                    $fine = $late * 2;
                ?>
                    <tr>
                        <td>#<?= (int)$ai['issue_id'] ?></td>
                        <td><strong><?= h($ai['title']) ?></strong></td>
                        <td><?= h($ai['member_name']) ?></td>
                        <td><?= h($ai['issue_date']) ?></td>
                        <td><?= h($ai['due_date']) ?></td>
                        <td>
                            <?php if ($late > 0): ?>
                                <span class="badge badge-danger"><?= $late ?> days late</span>
                            <?php else: ?>
                                <span class="badge badge-success">0 days (On time)</span>
                            <?php endif; ?>
                        </td>
                        <td><strong>₹<?= number_format($fine, 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
