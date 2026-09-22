<?php
/**
 * Python4Physics - Admin Feedback & Inquiries Viewer
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

$page_title = "User Inquiries & Feedback";

$notice = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM `feedback` WHERE `id` = :id");
                $stmt->execute([':id' => $id]);
                $notice = "Feedback message deleted.";
            } catch (PDOException $e) {
                $error = "Failed to delete: " . $e->getMessage();
            }
        }
    }
}

// Fetch feedback
$feedbacks = $conn->query("SELECT * FROM `feedback` ORDER BY `id` DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/layout_top.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">User Inquiries & Community Feedback</h1>
        <p class="admin-page-subtitle">Read messages submitted through the feedback and contact forms.</p>
    </div>
    <div>
        <span class="admin-badge admin-badge-cyan">Total: <?php echo count($feedbacks); ?> messages</span>
    </div>
</div>

<?php if (!empty($notice)): ?>
    <div style="padding: 0.85rem 1.25rem; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.75rem; color: #34d399; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo $notice; ?></span>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th style="width: 180px;">Sender</th>
                    <th style="width: 220px;">Email</th>
                    <th>Message</th>
                    <th style="width: 140px;">Date</th>
                    <th style="width: 80px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 3rem;">
                            No feedback messages received yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td><?php echo (int)$fb['id']; ?></td>
                            <td style="font-weight: 600;">
                                <i class="fa-solid fa-user" style="color: var(--admin-accent); margin-right: 4px;"></i>
                                <?php echo htmlspecialchars($fb['name']); ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($fb['email']); ?>" style="color: #38bdf8; text-decoration: none;">
                                    <?php echo htmlspecialchars($fb['email']); ?>
                                </a>
                            </td>
                            <td style="line-height: 1.5;">
                                <?php echo nl2br(htmlspecialchars($fb['message'])); ?>
                            </td>
                            <td style="color: var(--admin-text-muted); font-size: 0.8rem;">
                                <?php echo htmlspecialchars($fb['created_at'] ?? ''); ?>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this feedback entry?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$fb['id']; ?>">
                                    <button type="submit" class="btn-admin btn-admin-danger btn-admin-sm">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout_bottom.php'; ?>
