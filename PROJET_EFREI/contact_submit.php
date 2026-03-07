<?php

declare(strict_types=1);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

function field(string $key, int $maxLen, bool $required = true): string
{
    $value = trim((string)($_POST[$key] ?? ''));
    if ($required && $value == '') {
        throw new InvalidArgumentException('Missing field: ' . $key);
    }
    if ($value !== '' && strlen($value) > $maxLen) {
        $value = substr($value, 0, $maxLen);
    }
    return $value;
}

try {
    $prenom = field('prenom', 100);
    $nom = field('nom', 100);
    $email = field('email', 255);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email');
    }
    $telephone = field('telephone', 50, false);
    $profil = field('profil', 50);
    $sujet = field('sujet', 50);
    $programme = field('programme', 150, false);
    $message = field('message', 4000);

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    if (is_string($ipAddress) && strlen($ipAddress) > 45) {
        $ipAddress = substr($ipAddress, 0, 45);
    }
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    if (is_string($userAgent) && strlen($userAgent) > 255) {
        $userAgent = substr($userAgent, 0, 255);
    }

    $config = require __DIR__ . '/db_config.php';
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['db'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages
            (prenom, nom, email, telephone, profil, sujet, programme, message, ip_address, user_agent)
         VALUES
            (:prenom, :nom, :email, :telephone, :profil, :sujet, :programme, :message, :ip_address, :user_agent)'
    );

    $stmt->execute([
        ':prenom' => $prenom,
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => $telephone !== '' ? $telephone : null,
        ':profil' => $profil,
        ':sujet' => $sujet,
        ':programme' => $programme !== '' ? $programme : null,
        ':message' => $message,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent,
    ]);

    header('Location: contact.html?status=success');
    exit;
} catch (Throwable $e) {
    error_log('[contact_submit] ' . $e->getMessage());
    header('Location: contact.html?status=error');
    exit;
}
