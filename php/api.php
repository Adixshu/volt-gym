<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

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

$type = $data['type'] ?? '';
$name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
$message = is_string($data['message'] ?? null) ? trim($data['message']) : '';

if ($name === '' || strlen($name) > 60) {
    respond(422, ['message' => 'Enter a name no longer than 60 characters.']);
}

try {
    require_once __DIR__ . '/config.php';
    $connection = databaseConnection();

    if ($type === 'inquiry') {
        $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
        $goal = is_string($data['goal'] ?? null) ? trim($data['goal']) : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            respond(422, ['message' => 'Enter a valid email address.']);
        }
        if (strlen($goal) > 100 || strlen($message) > 2000) {
            respond(422, ['message' => 'Some enquiry details are too long.']);
        }

        $statement = $connection->prepare(
            'INSERT INTO inquiries (name, email, goal, message) VALUES (:name, :email, :goal, :message)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'goal' => $goal !== '' && $goal !== 'Choose an option' ? $goal : null,
            'message' => $message !== '' ? $message : null,
        ]);

        respond(201, ['message' => 'Thanks. Your visit request has been sent.']);
    }

    if ($type === 'review') {
        $rating = filter_var($data['rating'] ?? null, FILTER_VALIDATE_INT);
        if ($rating === false || $rating < 1 || $rating > 5) {
            respond(422, ['message' => 'Choose a rating from 1 to 5 stars.']);
        }
        if ($message === '' || strlen($message) > 600) {
            respond(422, ['message' => 'Enter a review no longer than 600 characters.']);
        }

        $statement = $connection->prepare(
            'INSERT INTO reviews (name, rating, message) VALUES (:name, :rating, :message)'
        );
        $statement->execute([
            'name' => $name,
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
