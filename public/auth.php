<?php
// Session plus utilisée ici, mais on active les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fonctions JWT (assure-toi que ce fichier existe)
require_once 'jwt_utils.php';

// === Vérification méthode POST ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=Requête invalide.');
    exit();
}

// === Connexion MySQL ===
$mysqli = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");

if ($mysqli->connect_error) {
    error_log("Connexion MySQL échouée : " . $mysqli->connect_error);
    header("Location: login.php?error=Erreur connexion BDD.");
    exit();
}

// === Récupération des données ===
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    header("Location: login.php?error=Champs requis manquants.");
    exit();
}

// === Préparation de l'identifiant ===
$stmt = $mysqli->prepare("SELECT id, password FROM etudiants WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // Email inconnu
    header("Location: login.php?error=Adresse email inconnue.");
    exit();
}

$stmt->bind_result($user_id, $hashed_password);
$stmt->fetch();
$stmt->close();
$mysqli->close();

// === Vérification du mot de passe ===
if (!password_verify($password, $hashed_password)) {
    header("Location: login.php?error=Mot de passe incorrect.");
    exit();
}

// Authentification réussie : générer un JWT
$jwt = generate_jwt(['etudiant_id' => $user_id]);

// Redirection avec le token dans l'URL
header("Location: dashboard.php?token=" . urlencode($jwt));
exit();
?>