<?php
session_start();
if (!isset($_SESSION['etudiant_id'])) {
    header("Location: login.php");
    exit();
}

$etudiant_id = $_SESSION['etudiant_id'];
$course      = $_POST['course'] ?? '';
$exercise    = $_POST['exercise'] ?? '';
$language    = $_POST['language'] ?? '';

if (
    empty($course) || empty($exercise) || empty($language) ||
    !isset($_FILES['codefile']) || $_FILES['codefile']['error'] !== UPLOAD_ERR_OK
) {
    header("Location: upload.php?message=❌ Erreur dans l’envoi");
    exit();
}

// Dossier de stockage
$upload_dir = __DIR__ . "/uploads";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$originalName = basename($_FILES['codefile']['name']);
$extension = pathinfo($originalName, PATHINFO_EXTENSION);

// Nom du fichier formaté
$filename = "etudiant{$etudiant_id}_ex{$exercise}_" . time() . ".$extension";
$filepath = $upload_dir . "/" . $filename;

// Déplacement du fichier
if (!move_uploaded_file($_FILES['codefile']['tmp_name'], $filepath)) {
    header("Location: upload.php?message=❌ Erreur lors de l’enregistrement du fichier");
    exit();
}

// Enregistrement en base
$conn = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");
if ($conn->connect_error) {
    die("Erreur MySQL : " . $conn->connect_error);
}

$stmt = $conn->prepare("INSERT INTO submissions 
    (etudiant_id, course, exercise, language, filename, filepath)
    VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isisss", $etudiant_id, $course, $exercise, $language, $filename, $filepath);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: upload.php?message=✅ Fichier envoyé avec succès !");
exit();