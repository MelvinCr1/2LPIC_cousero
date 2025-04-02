<?php
session_start();

// === Sécurité : vérifier que la requête vient bien d'un formulaire POST ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=Requête invalide.');
    exit();
}

// === Connexion à la BDD ===
$mysqli = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");

if ($mysqli->connect_error) {
    error_log("Connexion MySQL échouée : " . $mysqli->connect_error);
    header("Location: login.php?error=Erreur de connexion à la base.");
    exit();
}

// === Récupération et nettoyage des données ===
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    header("Location: login.php?error=Champs manquants.");
    exit();
}

// === Préparation de la requête ===
$stmt = $mysqli->prepare("SELECT id, password FROM etudiants WHERE email = ?");

if (!$stmt) {
    error_log("Erreur requête SQL (prepare) : " . $mysqli->error);
    header("Location: login.php?error=Erreur interne (requête).");
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // Aucun utilisateur trouvé
    header("Location: login.php?error=Adresse email inconnue.");
    exit();
}

$stmt->bind_result($user_id, $hashed_password);
$stmt->fetch();
$stmt->close();
$mysqli->close();

// === Vérification du mot de passe (recommandé avec password_hash) ===
if (!password_verify($password, $hashed_password)) {
    header("Location: login.php?error=Mot de passe invalide.");
    exit();
}

// === Connexion réussie ===
$_SESSION['etudiant_id'] = $user_id;
session_regenerate_id(true);  // Sécurité contre session fixation

header("Location: dashboard.php");
exit();
?>