<?php
// === Afficher erreurs pour débogage temporaire ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// === Session pour récupérer l'étudiant connecté ===
session_start();
if (!isset($_SESSION['etudiant_id'])) {
    header("Location: login.php?error=Veuillez vous connecter.");
    exit();
}

// === Récupération des données du formulaire ===
$etudiant_id = $_SESSION['etudiant_id'];
$course      = $_POST['course'] ?? '';
$exercise    = (int) ($_POST['exercise'] ?? 0);
$language    = $_POST['language'] ?? '';

if (
    empty($course) || empty($exercise) || empty($language) ||
    !isset($_FILES['codefile']) || $_FILES['codefile']['error'] !== UPLOAD_ERR_OK
) {
    header("Location: upload.php?message=Erreur : formulaire incomplet ou fichier manquant");
    exit();
}

// === Définir le dossier d'upload ===
$upload_dir = __DIR__ . "/uploads";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// === Préparer le fichier à enregistrer ===
$originalName = basename($_FILES['codefile']['name']);
$extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Filtrer extensions autorisées
$allowed_ext = ['c', 'py'];
if (!in_array($extension, $allowed_ext)) {
    header("Location: upload.php?message=Extension non autorisée (.c / .py uniquement)");
    exit();
}

// === Générer un nom unique pour le fichier ===
$filename = "etudiant{$etudiant_id}_ex{$exercise}_" . time() . ".$extension";
$filepath = $upload_dir . "/" . $filename;

// === Sauvegarder le fichier ===
if (!move_uploaded_file($_FILES['codefile']['tmp_name'], $filepath)) {
    header("Location: upload.php?message=Erreur lors de l'enregistrement du fichier");
    exit();
}

// === Connexion à la base ===
$host = "192.168.146.103";
$user = "webuser";
$pass = "webpassword";
$db   = "corrections";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("Connexion MySQL échouée : " . $conn->connect_error);
    die("Erreur de connexion à la base de données.");
}

// === Préparation de la requête SQL ===
$sql = "INSERT INTO submissions 
    (etudiant_id, course, exercise, language, filename, filepath, submitted_at, status)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_attente')";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Erreur SQL (prepare) : " . $conn->error);
    die("Erreur interne (préparation de requête échouée).");
}

// === Lier les paramètres ===
$stmt->bind_param("isisss", $etudiant_id, $course, $exercise, $language, $filename, $filepath);

// === Exécution et vérification ===
if (!$stmt->execute()) {
    error_log("Erreur SQL (execute) : " . $stmt->error);
    die("Erreur lors de l’enregistrement dans la base.");
}

// === Nettoyage et redirection ===
$stmt->close();
$conn->close();

header("Location: upload.php?message=Fichier envoyé avec succès !");
exit();