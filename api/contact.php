<?php
// ─────────────────────────────────────────────────
//  api/contact.php — Contact form handler
//  Called via fetch() from the frontend JS
// ─────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');           // tighten in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// ─── Rate limiting (simple: 3 submissions per IP per hour) ───
function check_rate_limit(PDO $pdo, string $ip): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM contact_submissions
         WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() < 3;
}

// ─── Sanitize & Validate ─────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);

// Support both JSON body and standard form POST
$name    = trim($body['name']    ?? $_POST['name']    ?? '');
$email   = trim($body['email']   ?? $_POST['email']   ?? '');
$project = trim($body['project'] ?? $_POST['project'] ?? '');
$message = trim($body['message'] ?? $_POST['message'] ?? '');

$errors = [];

if (empty($name) || mb_strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if (empty($message) || mb_strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters.';
}
// Honeypot anti-spam field (add a hidden input named "website" to your form)
if (!empty($body['website'] ?? $_POST['website'] ?? '')) {
    // Silently accept but don't save — it's a bot
    echo json_encode(['success' => true, 'message' => 'Message received!']);
    exit;
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ─── Rate limit check ────────────────────────────────────────
$pdo = get_db();
$ip  = get_client_ip();

if (!check_rate_limit($pdo, $ip)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many submissions. Please try again later.'
    ]);
    exit;
}

// ─── Save to database ────────────────────────────────────────
try {
    $stmt = $pdo->prepare(
        "INSERT INTO contact_submissions
            (name, email, project_type, message, ip_address, user_agent)
         VALUES
            (:name, :email, :project, :message, :ip, :ua)"
    );
    $stmt->execute([
        ':name'    => mb_substr($name,    0, 150),
        ':email'   => mb_substr($email,   0, 255),
        ':project' => mb_substr($project, 0, 200),
        ':message' => $message,
        ':ip'      => $ip,
        ':ua'      => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
    $insertId = $pdo->lastInsertId();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save your message. Please try again.']);
    exit;
}

// ─── Send email notification ─────────────────────────────────
$subject = "New contact from {$name} — VØID Studio";
$mailBody = "New contact form submission\n"
    . "═══════════════════════════\n"
    . "ID:      #{$insertId}\n"
    . "Name:    {$name}\n"
    . "Email:   {$email}\n"
    . "Project: {$project}\n"
    . "Message:\n{$message}\n"
    . "═══════════════════════════\n"
    . "IP:      {$ip}\n"
    . "Time:    " . date('Y-m-d H:i:s') . "\n";

$headers = implode("\r\n", [
    'From: ' . MAIL_FROM,
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
]);

@mail(MAIL_TO, $subject, $mailBody, $headers);

// ─── Success ─────────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => "Thanks {$name}! We've received your message and will be in touch soon.",
]);