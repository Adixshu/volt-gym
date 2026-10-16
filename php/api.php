<?php
declare(strict_types=1);

// JSON endpoint for public enquiry and member-review submissions.
header('Content-Type: application/json; charset=utf-8');

// Send a consistent JSON response and stop processing the request.
function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['message' => 'Only POST requests are accepted.']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    respond(400, ['message' => 'The submitted data could not be read.']);
}

// Normalize shared contact fields before validating the selected form type.
$type = $data['type'] ?? '';
$name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
$email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
$phone = is_string($data['phone'] ?? null) ? trim($data['phone']) : '';
$message = is_string($data['message'] ?? null) ? trim($data['message']) : '';

if ($name === '' || strlen($name) > 60) {
    respond(422, ['message' => 'Enter a name no longer than 60 characters.']);
}
if ($email === '' && $phone === '') {
    respond(422, ['message' => 'Enter an email address or phone number so we can contact you.']);
}
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254)) {
    respond(422, ['message' => 'Enter a valid email address.']);
}
if ($phone !== '') {
    $phoneDigits = preg_replace('/\D/', '', $phone);
    if (
        strlen($phone) > 32
        || !preg_match('/^\+?[0-9\s().-]+$/', $phone)
        || $phoneDigits === null
        || strlen($phoneDigits) < 7
        || strlen($phoneDigits) > 15
    ) {
        respond(422, ['message' => 'Enter a valid phone number.']);
    }
}

try {
    require_once __DIR__ . '/config.php';
    $connection = databaseConnection();

    // Save a prospective member's visit request.
    if ($type === 'inquiry') {
        $goal = is_string($data['goal'] ?? null) ? trim($data['goal']) : '';

        if (strlen($goal) > 100 || strlen($message) > 2000) {
            respond(422, ['message' => 'Some enquiry details are too long.']);
        }

        $statement = $connection->prepare(
            'INSERT INTO inquiries (name, email, phone, goal, message)
             VALUES (:name, :email, :phone, :goal, :message)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'goal' => $goal !== '' && $goal !== 'Choose an option' ? $goal : null,
            'message' => $message !== '' ? $message : null,
        ]);

        respond(201, ['message' => 'Thanks. Your visit request has been sent.']);
    }

    // Save a review after validating its rating and message.
    if ($type === 'review') {
        $rating = filter_var($data['rating'] ?? null, FILTER_VALIDATE_INT);
        if ($rating === false || $rating < 1 || $rating > 5) {
            respond(422, ['message' => 'Choose a rating from 1 to 5 stars.']);
        }
        if ($message === '' || strlen($message) > 600) {
            respond(422, ['message' => 'Enter a review no longer than 600 characters.']);
        }

        $statement = $connection->prepare(
            'INSERT INTO reviews (name, email, phone, rating, message)
             VALUES (:name, :email, :phone, :rating, :message)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'rating' => $rating,
            'message' => $message,
        ]);

        respond(201, ['message' => 'Thanks. Your review has been sent.']);
    }

    respond(422, ['message' => 'The requested form type is not supported.']);
} catch (Throwable $error) {
    error_log($error->getMessage());
    respond(500, ['message' => 'The request could not be saved. Check that MySQL is running and the database is set up.']);
}
