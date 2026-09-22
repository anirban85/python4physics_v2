<?php
/**
 * Python4Physics - Feedback Portal
 */
session_start();
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/db.php';

$page_title = "Feedback & Academic Suggestions";
$page_description = "Share your feedback, report errors in physics codes, or suggest new algorithms to the Python4Physics team.";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$feedback_status = null;
$feedback_msg = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $feedback_status = 'error';
        $feedback_msg = 'Security token validation failed. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $rating = intval($_POST['rating'] ?? 5);
        $user_msg = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($user_msg)) {
            $feedback_status = 'error';
            $feedback_msg = 'Please fill out all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $feedback_status = 'error';
            $feedback_msg = 'Please provide a valid email address.';
        } else {
            $full_message = "[Category: {$category} | Rating: {$rating}/5]\n\n" . $user_msg;

            try {
                if (isset($conn) && $conn !== null) {
                    $stmt = $conn->prepare("INSERT INTO feedback (name, email, message) VALUES (:name, :email, :message)");
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'message' => $full_message
                    ]);
                    $feedback_status = 'success';
                    $feedback_msg = 'Thank you for your valuable feedback! The authors have received your submission.';
                    // Regenerate token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $feedback_status = 'error';
                    $feedback_msg = 'Database connection error. Please try again later.';
                }
            } catch (Exception $e) {
                $feedback_status = 'error';
                $feedback_msg = 'Error saving feedback: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/include/header.php';
require_once __DIR__ . '/include/navbar.php';
?>

<main class="container" style="padding-top: 3.5rem; padding-bottom: 5rem; max-width: 760px;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <span class="badge badge-cyan"><i class="fa-regular fa-comment-dots"></i> Community Feedback</span>
        <h1 style="font-size: 2.6rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">Your Feedback <span class="gradient-text">Matters</span></h1>
        <p style="font-size: 1.1rem; line-height: 1.6;">
            Help us improve computational physics education globally. Report bugs in scripts, request new algorithms, or suggest curriculum enhancements.
        </p>
    </div>

    <div class="glass-card" style="padding: 2.5rem 2rem;">
        <?php if ($feedback_status === 'success'): ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--text); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fa-solid fa-circle-check fa-2x" style="color: var(--success);"></i>
                <div>
                    <strong>Success!</strong><br>
                    <?php echo htmlspecialchars($feedback_msg); ?>
                </div>
            </div>
        <?php elseif ($feedback_status === 'error'): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: var(--text); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fa-solid fa-circle-exclamation fa-2x" style="color: var(--danger);"></i>
                <div>
                    <strong>Attention:</strong><br>
                    <?php echo htmlspecialchars($feedback_msg); ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="feedback.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Your Name *</label>
                    <input type="text" name="name" required class="search-input" style="width: 100%; border: 1px solid var(--card-border); background: var(--bg-secondary); padding: 0.75rem 1rem; border-radius: var(--radius-sm);" placeholder="e.g. Dr. John Doe">
                </div>
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Email Address *</label>
                    <input type="email" name="email" required class="search-input" style="width: 100%; border: 1px solid var(--card-border); background: var(--bg-secondary); padding: 0.75rem 1rem; border-radius: var(--radius-sm);" placeholder="e.g. name@university.edu">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Category</label>
                    <select name="category" class="btn-modern btn-secondary" style="width: 100%; padding: 0.75rem 1rem;">
                        <option value="General Feedback">General Feedback</option>
                        <option value="Code Request">Request New Physics Algorithm</option>
                        <option value="Bug Report">Report Script / Math Bug</option>
                        <option value="Curriculum Suggestion">Curriculum & Syllabus Suggestion</option>
                        <option value="Academic Adoption">University / College Adoption</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Overall Rating</label>
                    <select name="rating" class="btn-modern btn-secondary" style="width: 100%; padding: 0.75rem 1rem;">
                        <option value="5">★★★★★ (5/5) Excellent Platform</option>
                        <option value="4">★★★★☆ (4/5) Very Good</option>
                        <option value="3">★★★☆☆ (3/5) Average / Needs Improvements</option>
                        <option value="2">★★☆☆☆ (2/5) Disliked Aspects</option>
                        <option value="1">★☆☆☆☆ (1/5) Needs Complete Overhaul</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Message & Details *</label>
                <textarea name="message" rows="6" required class="search-input" style="width: 100%; border: 1px solid var(--card-border); background: var(--bg-secondary); padding: 0.85rem 1rem; border-radius: var(--radius-sm); font-family: inherit; line-height: 1.6;" placeholder="Describe your experience, suggest algorithms, or paste any code snippets..."></textarea>
            </div>

            <button type="submit" class="btn-modern btn-primary btn-lg" style="width: 100%;">
                <i class="fa-solid fa-paper-plane"></i> Submit Feedback
            </button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
