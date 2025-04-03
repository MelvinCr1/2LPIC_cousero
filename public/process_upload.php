<?php
// === Afficher les erreurs pour debug local ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'jwt_utils.php';

// === Vérification du token =====
$token = $_POST['token'] ?? null;

if (!$token || !is_jwt_valid($token)) {
    die("Token invalide ou expiré. <a href='login.php'>Retour connexion</a>");
}

$payload = get_payload_from_jwt($token);
$etudiant_id = $payload['etudiant_id'] ?? null;

if (!$etudiant_id) {
    die("Impossible de récupérer l'identifiant depuis le token.");
}

// === Récupération des données du formulaire ===
$course      = $_POST['course']   ?? '';
$exercise    = (int) ($_POST['exercise'] ?? 0);
$language    = $_POST['language'] ?? '';

if (
    empty($course) || empty($exercise) || empty($language) ||
    !isset($_FILES['codefile']) || $_FILES['codefile']['error'] !== UPLOAD_ERR_OK
) {
    header("Location: upload.php?message=Erreur+d'envoi.&token=" . urlencode($token));
    exit();
}

// === Dossier d’upload ===
$upload_dir = __DIR__ . "/uploads";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// === Préparer le nom du fichier ===
$originalName = basename($_FILES['codefile']['name']);
$extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

$allowed_ext = ['c', 'py'];
if (!in_array($extension, $allowed_ext)) {
    header("Location: upload.php?message=Extension+non+autoris%C3%A9e (.c/.py)&token=" . urlencode($token));
    exit();
}

// === Générer un nom de fichier unique ===
$filename = "etudiant{$etudiant_id}_ex{$exercise}_" . time() . ".$extension";
$filepath = $upload_dir . "/" . $filename;

// === Sauvegarde fichier ===
if (!move_uploaded_file($_FILES['codefile']['tmp_name'], $filepath)) {
    header("Location: upload.php?message=Erreur+enregistrement+fichier&token=" . urlencode($token));
    exit();
}

// === Connexion MySQL ===
$host = "192.168.146.103";
$user = "webuser";
$pass = "webpassword";
$db   = "corrections";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Connexion MySQL échouée : " . $conn->connect_error);
    die("Impossible de se connecter à la base.");
}

// === Enregistrement en base ===
$sql = "INSERT INTO submissions 
(etudiant_id, course, exercise, language, filename, filepath, submitted_at, status)
VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_attente')";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("SQL prepare : " . $conn->error);
    die("Erreur SQL (prepare)");
}

$stmt->bind_param("isisss", $etudiant_id, $course, $exercise, $language, $filename, $filepath);

if (!$stmt->execute()) {
    error_log("SQL execute : " . $stmt->error);
    die("Erreur SQL (execute)");
}

$stmt->close();
$conn->close();

// Retour vers upload.php avec message + token
header("Location: upload.php?message=Fichier+envoyé+avec+succès+!&token=" . urlencode($token));
exit();