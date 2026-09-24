<?php
// Upload handler dla TinyMCE i formularzy
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'Brak uprawnień']);
    exit();
}

// Sprawdź, czy to żądanie POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    echo json_encode(['error' => 'Nieprawidłowa metoda']);
    exit();
}

// Sprawdź, czy przesłano plik
if (!isset($_FILES['file'])) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'Brak pliku']);
    exit();
}

$file = $_FILES['file'];

// Sprawdź, czy plik jest obrazkiem
if (!isImageFile($file['name'])) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'Dozwolone są tylko pliki obrazków (JPG, PNG, GIF, WebP)']);
    exit();
}

// Sprawdź rozmiar pliku
if ($file['size'] > MAX_FILE_SIZE) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'Plik jest za duży. Maksymalny rozmiar: ' . formatFileSize(MAX_FILE_SIZE)]);
    exit();
}

// Przesłanie pliku
$uploadResult = uploadFile($file);

if (!$uploadResult['success']) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => $uploadResult['message']]);
    exit();
}

// Zwróć odpowiedź dla TinyMCE
header('Content-Type: application/json');
echo json_encode([
    'location' => getImageUrl($uploadResult['filename'])
]);
