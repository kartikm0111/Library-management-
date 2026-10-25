<?php
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_member') {
        $member_id   = (int)($_POST['member_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $member_type = ($_POST['member_type'] ?? 'Student') === 'Faculty' ? 'Faculty' : 'Student';
        $join_date   = trim($_POST['join_date'] ?? date('Y-m-d'));
        $status      = ($_POST['status'] ?? 'Active') === 'Suspended' ? 'Suspended' : 'Active';

        if ($name === '' || $email === '' || $phone === '') {
            $err = "Name, Email, and Phone are required.";
        } else {
            if ($member_id > 0) {
                $stmt = $conn->prepare("UPDATE member SET name=?, email=?, phone=?, member_type=?, join_date=?, status=? WHERE member_id=?");
                $stmt->bind_param("ssssssi", $name, $email, $phone, $member_type, $join_date, $status, $member_id);
                if ($stmt->execute()) {
                    $msg = "Member #" . $member_id . " updated successfully.";
                } else {
                    $err = "Error updating member: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO member (name, email, phone, member_type, join_date, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $name, $email, $phone, $member_type, $join_date, $status);
                if ($stmt->execute()) {
                    $msg = "Member '" . $name . "' registered successfully.";
                } else {
                    $err = "Error registering member (email must be unique): " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'delete_member') {
        $del_id = (int)($_POST['member_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM member WHERE member_id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $msg = "Member #" . $del_id . " deleted.";
        } else {
            $err = "Cannot delete member with existing issue records.";
        }
        $stmt->close();
    }
}

$edit_member = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM member WHERE member_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_member = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all members with their active book count
$sql = "
    SELECT m.*,
           COUNT(CASE WHEN i.return_date IS NULL THEN 1 END) AS active_books
    FROM member m
    LEFT JOIN issue i ON m.member_id = i.member_id
    GROUP BY m.member_id
    ORDER BY m.member_id ASC
";
$members_res = $conn->query($sql);
?>

<div class="page-header">
    <h1>Library Member Management</h1>
</div>

<?php if ($msg !== ''): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err !== ''): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="grid-2col">
    <div class="card">
        <h2><?= $edit_member ? 'Edit Member #' . (int)$edit_member['member_id'] : 'Register New Member' ?></h2>
        <form method="POST" action="members.php">
            <input type="hidden" name="action" value="save_member">
            <input type="hidden" name="member_id" value="<?= (int)($edit_member['member_id'] ?? 0) ?>">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required maxlength="150" value="<?= h($edit_member['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>College Email (Unique)</label>
                <input type="email" name="email" class="form-control" required maxlength="150" value="<?= h($edit_member['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" required pattern="[0-9]{10,15}" title="10 to 15 digits" value="<?= h($edit_member['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Member Type (Borrow Limit: Student = 3, Faculty = 5)</label>
                <select name="member_type" class="form-control" required>
                    <option value="Student" <?= (($edit_member['member_type'] ?? '') === 'Student') ? 'selected' : '' ?>>Student (Max 3 Books)</option>
                    <option value="Faculty" <?= (($edit_member['member_type'] ?? '') === 'Faculty') ? 'selected' : '' ?>>Faculty (Max 5 Books)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Join Date</label>
                <input type="date" name="join_date" class="form-control" required value="<?= h($edit_member['join_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Account Status</label>
                <select name="status" class="form-control" required>
                    <option value="Active" <?= (($edit_member['status'] ?? '') === 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Suspended" <?= (($edit_member['status'] ?? '') === 'Suspended') ? 'selected' : '' ?>>Suspended (Borrowing Blocked)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;"><?= $edit_member ? 'Update Member' : 'Register Member' ?></button>
        </form>
    </div>

    <div class="card">
        <h2>Registered Members Directory</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name &amp; Contact</th>
                    <th>Type</th>
                    <th>Join Date</th>
                    <th>Active Loans</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $members_res->fetch_assoc()):
                    $max_limit = ($m['member_type'] === 'Faculty') ? 5 : 3;
                ?>
                    <tr>
                        <td><?= (int)$m['member_id'] ?></td>
                        <td>
                            <strong><?= h($m['name']) ?></strong><br>
                            <span style="color:var(--muted); font-size:12px;"><?= h($m['email']) ?> &bull; <?= h($m['phone']) ?></span>
                        </td>
                        <td><span class="badge badge-info"><?= h($m['member_type']) ?></span></td>
                        <td><?= h($m['join_date']) ?></td>
                        <td><strong><?= (int)$m['active_books'] ?></strong> / <?= $max_limit ?></td>
                        <td>
                            <?php if ($m['status'] === 'Active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Suspended</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="members.php?edit_id=<?= (int)$m['member_id'] ?>" class="btn btn-accent btn-sm">Edit</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
