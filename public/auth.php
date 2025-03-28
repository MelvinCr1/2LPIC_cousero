<?php
session_start();

// Affichage d'erreurs pour debug (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion base de données
$mysqli = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");
if ($mysqli->connect_error) {
    header("Location: login.php?error=Connexion à la base de données échouée.");
    exit();
}

// Récupération des infos du formulaire
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: login.php?error=Champs requis manquants.");
    exit();
}

// Préparation de la requête
$stmt = $mysqli->prepare("SELECT id FROM etudiants WHERE email = ? AND password = PASSWORD(?)");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();
$mysqli->close();

// Connexion réussie ?
if (!empty($user_id)) {
    $_SESSION['etudiant_id'] = $user_id;
    session_regenerate_id(true);
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php?error=Identifiants incorrects.");
    exit();
}
?>