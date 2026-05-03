<?php
/**
 * Admin: View All Registered Participants (Users)
 * Real-time quiz system
 * No emojis
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
$pdo = Database::getInstance()->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Fetch users
$sql = "SELECT id, first_name, last_name, email, phone, gender, created_at, is_active 
        FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: #1e293b;
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-size: 22px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #334155;
        }
        .sidebar nav a {
            display: block;
            padding: 12px 16px;
            margin-bottom: 8px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 12px;
        }
        .sidebar nav a:hover {
            background: #334155;
            color: white;
        }
        .sidebar .logout {
            margin-top: 40px;
            border-top: 1px solid #334155;
            padding-top: 20px;
        }
        .main-content {
            margin-left: 280px;
            padding: 30px 40px;
            width: 100%;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #0f172a;
        }
        .search-bar {
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
        }
        .search-bar input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 40px;
            font-size: 14px;
        }
        .search-bar button {
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 40px;
            cursor: pointer;
        }
        .user-table {
            background: white;
            border-radius: 24px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
        }
        .status-active {
            color: #16a34a;
            font-weight: 500;
        }
        .status-inactive {
            color: #dc2626;
        }
        .pagination {
            margin-top: 25px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .pagination a {
            padding: 8px 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            color: #3b82f6;
        }
        .pagination a.active {
            background: #3b82f6;
            color: white;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>Quiz Manager</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="create_quiz.php">Create New Quiz</a>
        <a href="manage_questions.php">Manage Questions</a>
        <a href="participants.php" class="active">View Participants</a>
        <a href="leaderboard.php">Leaderboard</a>
        <div class="logout">
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
</div>
<div class="main-content">
    <div class="header">
        <h1>Registered Participants</h1>
        <p>All users who have registered for quizzes.</p>
    </div>

    <form method="get" class="search-bar">
        <input type="text" name="search" placeholder="Search by name, email, or phone" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
        <?php if (!empty($search)): ?>
            <a href="participants.php" style="line-height: 42px;">Clear</a>
        <?php endif; ?>
    </form>

    <div class="user-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Gender</th>
                    <th>Registered On</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo htmlspecialchars($user['gender']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="<?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px;">No participants found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <footer>
        Real-Time Quiz System | Total participants: <?php echo $totalUsers; ?>
    </footer>
</div>
</body>
</html>