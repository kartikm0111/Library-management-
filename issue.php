<?php
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id  = (int)($_POST['member_id'] ?? 0);
    $book_id    = (int)($_POST['book_id'] ?? 0);
    $issue_date = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $staff_id   = (int)$_SESSION['staff_id'];

    if ($member_id <= 0 || $book_id <= 0) {
        $err = "Please select both a valid Member and a Book.";
    } else {
        // 1. Fetch Member details (Prepared Statement)
        $stmt_m = $conn->prepare("SELECT name, member_type, status FROM member WHERE member_id = ?");
        $stmt_m->bind_param("i", $member_id);
        $stmt_m->execute();
        $member = $stmt_m->get_result()->fetch_assoc();
        $stmt_m->close();

        // 2. Fetch active loan count for this member (Prepared Statement)
        $stmt_c = $conn->prepare("SELECT COUNT(*) AS active_cnt FROM issue WHERE member_id = ? AND return_date IS NULL");
        $stmt_c->bind_param("i", $member_id);
        $stmt_c->execute();
        $active_cnt = (int)$stmt_c->get_result()->fetch_assoc()['active_cnt'];
        $stmt_c->close();

        // 3. Fetch Book availability (Prepared Statement)
        $stmt_b = $conn->prepare("SELECT title, available_copies FROM book WHERE book_id = ?");
        $stmt_b->bind_param("i", $book_id);
        $stmt_b->execute();
        $book = $stmt_b->get_result()->fetch_assoc();
        $stmt_b->close();

        if (!$member || !$book) {
            $err = "Selected member or book record does not exist.";
        } elseif ($member['status'] === 'Suspended') {
            // Business Rule: A Suspended member cannot borrow
            $err = "Issue Rejected: Member '" . $member['name'] . "' is currently SUSPENDED and cannot borrow books.";
        } else {
            // Business Rule: Max 3 books for Student, Max 5 for Faculty
            $max_allowed = ($member['member_type'] === 'Faculty') ? 5 : 3;
            if ($active_cnt >= $max_allowed) {
                $err = "Issue Rejected: " . $member['member_type'] . " '" . $member['name'] . "' already holds the maximum allowed " . $max_allowed . " books (" . $active_cnt . "/" . $max_allowed . " active).";
            } elseif ((int)$book['available_copies'] <= 0) {
                // Business Rule: A book with available_copies = 0 cannot be issued
                $err = "Issue Rejected: Book '" . $book['title'] . "' has 0 available copies in stock.";
            } else {
                // Business Rule: Loan period is 14 days for everyone
                $due_date = date('Y-m-d', strtotime($issue_date . ' + 14 days'));

                $conn->begin_transaction();
                try {
                    $stmt_ins = $conn->prepare("INSERT INTO issue (book_id, member_id, staff_id, issue_date, due_date, return_date) VALUES (?, ?, ?, ?, ?, NULL)");
                    $stmt_ins->bind_param("iiiss", $book_id, $member_id, $staff_id, $issue_date, $due_date);
                    if (!$stmt_ins->execute()) {
                        throw new Exception($stmt_ins->error);
                    }
                    $new_issue_id = $stmt_ins->insert_id;
                    $stmt_ins->close();

                    // Business Rule: Issuing a book reduces available_copies by 1
                    $stmt_upd = $conn->prepare("UPDATE book SET available_copies = available_copies - 1 WHERE book_id = ?");
                    $stmt_upd->bind_param("i", $book_id);
                    if (!$stmt_upd->execute()) {
                        throw new Exception($stmt_upd->error);
                    }
                    $stmt_upd->close();

                    $conn->commit();
                    $msg = "Success! Book '" . $book['title'] . "' issued to " . $member['name'] . " (Issue #" . $new_issue_id . "). Due Date (14 days): " . $due_date;
                } catch (Exception $e) {
                    $conn->rollback();
                    $err = "Transaction failed: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch members and books for selection dropdowns
$members = $conn->query("
    SELECT m.member_id, m.name, m.member_type, m.status,
           COUNT(CASE WHEN i.return_date IS NULL THEN 1 END) AS active_cnt
    FROM member m
    LEFT JOIN issue i ON m.member_id = i.member_id
    GROUP BY m.member_id
    ORDER BY m.name ASC
")->fetch_all(MYSQLI_ASSOC);

$books = $conn->query("SELECT book_id, isbn, title, author, available_copies, total_copies FROM book ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <h1>Issue Book to Member</h1>
</div>

<?php if ($msg !== ''): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err !== ''): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2>New Book Issue Transaction</h2>
        <form method="POST" action="issue.php">
            <div class="form-group">
                <label>Select Borrower (Member)</label>
                <select name="member_id" class="form-control" required>
                    <option value="">-- Choose Member --</option>
                    <?php foreach ($members as $m):
                        $limit = ($m['member_type'] === 'Faculty') ? 5 : 3;
                    ?>
                        <option value="<?= (int)$m['member_id'] ?>">
                            #<?= (int)$m['member_id'] ?> - <?= h($m['name']) ?> (<?= h($m['member_type']) ?> | Active: <?= (int)$m['active_cnt'] ?>/<?= $limit ?> | <?= h($m['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Select Book</label>
                <select name="book_id" class="form-control" required>
                    <option value="">-- Choose Book --</option>
                    <?php foreach ($books as $b): ?>
                        <option value="<?= (int)$b['book_id'] ?>">
                            <?= h($b['title']) ?> [<?= h($b['isbn']) ?>] — Avail: <?= (int)$b['available_copies'] ?>/<?= (int)$b['total_copies'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Issue Date (Due date is auto-calculated as Issue Date + 14 days)</label>
                <input type="date" name="issue_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Validate Rules &amp; Issue Book</button>
        </form>
    </div>

    <div class="card">
        <h2>Active Circulation Rules Enforced</h2>
        <ul style="padding-left: 18px; line-height: 1.8;">
            <li><strong>Student Quota:</strong> Maximum <strong>3 books</strong> issued at a time.</li>
            <li><strong>Faculty Quota:</strong> Maximum <strong>5 books</strong> issued at a time.</li>
            <li><strong>Loan Period:</strong> Automatically set to <strong>14 calendar days</strong> from Issue Date.</li>
            <li><strong>Stock Verification:</strong> Books with <code>available_copies = 0</code> are strictly rejected.</li>
            <li><strong>Member Standing:</strong> Members with <code>status = 'Suspended'</code> are blocked from borrowing.</li>
            <li><strong>Inventory Sync:</strong> Issuing atomically decrements <code>available_copies</code> by 1.</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
