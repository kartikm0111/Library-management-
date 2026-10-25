<?php
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Handle Add / Update Book
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_book') {
        $book_id          = (int)($_POST['book_id'] ?? 0);
        $isbn             = trim($_POST['isbn'] ?? '');
        $title            = trim($_POST['title'] ?? '');
        $author           = trim($_POST['author'] ?? '');
        $publisher        = trim($_POST['publisher'] ?? '');
        $pub_year         = (int)($_POST['pub_year'] ?? date('Y'));
        $category_id      = (int)($_POST['category_id'] ?? 0);
        $total_copies     = (int)($_POST['total_copies'] ?? 1);
        $available_copies = (int)($_POST['available_copies'] ?? $total_copies);

        if ($isbn === '' || $title === '' || $author === '' || $publisher === '' || $category_id <= 0) {
            $err = "All book fields are required.";
        } elseif ($total_copies < 0 || $available_copies < 0 || $available_copies > $total_copies) {
            $err = "Invalid copy counts: Available copies must be between 0 and Total copies.";
        } else {
            if ($book_id > 0) {
                $stmt = $conn->prepare("UPDATE book SET isbn=?, title=?, author=?, publisher=?, pub_year=?, category_id=?, total_copies=?, available_copies=? WHERE book_id=?");
                $stmt->bind_param("ssssiiiii", $isbn, $title, $author, $publisher, $pub_year, $category_id, $total_copies, $available_copies, $book_id);
                if ($stmt->execute()) {
                    $msg = "Book #" . $book_id . " updated successfully.";
                } else {
                    $err = "Error updating book: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO book (isbn, title, author, publisher, pub_year, category_id, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiiii", $isbn, $title, $author, $publisher, $pub_year, $category_id, $total_copies, $available_copies);
                if ($stmt->execute()) {
                    $msg = "Book '" . $title . "' added to the catalog.";
                } else {
                    $err = "Error adding book (check duplicate ISBN): " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'delete_book') {
        $del_id = (int)($_POST['book_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM book WHERE book_id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $msg = "Book #" . $del_id . " removed from catalog.";
        } else {
            $err = "Cannot delete book because it has existing circulation history.";
        }
        $stmt->close();
    }
}

// Fetch book for editing if requested
$edit_book = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM book WHERE book_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_book = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT category_id, category_name FROM category ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);

// Search parameters (Prepared Statement)
$search_q = trim($_GET['q'] ?? '');
$filter_cat = (int)($_GET['cat'] ?? 0);

$sql = "SELECT b.*, c.category_name FROM book b JOIN category c ON b.category_id = c.category_id WHERE 1=1";
$params = [];
$types = "";

if ($search_q !== '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $like = "%" . $search_q . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}
if ($filter_cat > 0) {
    $sql .= " AND b.category_id = ?";
    $params[] = $filter_cat;
    $types .= "i";
}
$sql .= " ORDER BY b.book_id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books_res = $stmt->get_result();
?>

<div class="page-header">
    <h1>Book Catalog Management</h1>
</div>

<?php if ($msg !== ''): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err !== ''): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<div class="grid-2col">
    <!-- Left Column: Add / Edit Book Form -->
    <div class="card">
        <h2><?= $edit_book ? 'Edit Book #' . (int)$edit_book['book_id'] : 'Add New Book' ?></h2>
        <form method="POST" action="books.php">
            <input type="hidden" name="action" value="save_book">
            <input type="hidden" name="book_id" value="<?= (int)($edit_book['book_id'] ?? 0) ?>">

            <div class="form-group">
                <label>ISBN (Unique)</label>
                <input type="text" name="isbn" class="form-control" required maxlength="20" placeholder="978-0262033848" value="<?= h($edit_book['isbn'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Book Title</label>
                <input type="text" name="title" class="form-control" required maxlength="255" value="<?= h($edit_book['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" class="form-control" required maxlength="150" value="<?= h($edit_book['author'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Publisher</label>
                <input type="text" name="publisher" class="form-control" required maxlength="150" value="<?= h($edit_book['publisher'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Publication Year</label>
                <input type="number" name="pub_year" class="form-control" required min="1901" max="2099" value="<?= h($edit_book['pub_year'] ?? '2024') ?>">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['category_id'] ?>" <?= ((int)($edit_book['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : '' ?>>
                            <?= h($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Total Copies</label>
                <input type="number" name="total_copies" class="form-control" required min="0" value="<?= (int)($edit_book['total_copies'] ?? 3) ?>">
            </div>
            <div class="form-group">
                <label>Available Copies</label>
                <input type="number" name="available_copies" class="form-control" required min="0" value="<?= (int)($edit_book['available_copies'] ?? 3) ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;"><?= $edit_book ? 'Update Book' : 'Add Book to Catalog' ?></button>
            <?php if ($edit_book): ?>
                <a href="books.php" class="btn" style="display:block; text-align:center; margin-top:8px;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Right Column: Search & Book Table -->
    <div class="card">
        <h2>Search &amp; Browse Catalog</h2>
        <form method="GET" action="books.php" class="search-bar">
            <input type="text" name="q" class="form-control" placeholder="Search by title, author, or ISBN..." value="<?= h($search_q) ?>">
            <select name="cat" class="form-control" style="max-width: 220px;">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>" <?= ($filter_cat === (int)$cat['category_id']) ? 'selected' : '' ?>>
                        <?= h($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="books.php" class="btn" style="background:#e2e8f0;">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ISBN</th>
                    <th>Title &amp; Author</th>
                    <th>Category</th>
                    <th>Year</th>
                    <th>Copies (Avail/Total)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($b = $books_res->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$b['book_id'] ?></td>
                        <td><?= h($b['isbn']) ?></td>
                        <td>
                            <strong><?= h($b['title']) ?></strong><br>
                            <span style="color:var(--muted); font-size:12px;"><?= h($b['author']) ?> &bull; <?= h($b['publisher']) ?></span>
                        </td>
                        <td><?= h($b['category_name']) ?></td>
                        <td><?= h($b['pub_year']) ?></td>
                        <td>
                            <?php if ((int)$b['available_copies'] === 0): ?>
                                <span class="badge badge-danger">0 / <?= (int)$b['total_copies'] ?> (Out of Stock)</span>
                            <?php else: ?>
                                <span class="badge badge-success"><?= (int)$b['available_copies'] ?> / <?= (int)$b['total_copies'] ?> Available</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="books.php?edit_id=<?= (int)$b['book_id'] ?>" class="btn btn-accent btn-sm">Edit</a>
                            <form method="POST" action="books.php" style="display:inline;" onsubmit="return confirm('Delete this book?');">
                                <input type="hidden" name="action" value="delete_book">
                                <input type="hidden" name="book_id" value="<?= (int)$b['book_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Del</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/footer.php';
?>
