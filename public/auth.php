<?php
session_start();

// Segfault préventif : vérifier que la requête est bien envoyée par POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// Récupération sécurisée des données du formulaire
$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';

// 1. Vérifier que les deux champs sont fournis
if (empty($email) || empty($password)) {
    header("Location: login.php?error=Champs manquants.");
    exit();
}

// 2. Connexion à la base de données
$conn = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");

if ($conn->connect_error) {
    error_log("Connexion échouée : " . $conn->connect_error);
    header("Location: login.php?error=Erreur de connexion.");
    exit();
}

// 3. Préparer la requête sécurisée (anti-injection SQL) 🛡️
$stmt = $conn->prepare("SELECT id, password FROM etudiants WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

// 4. Vérifier si un compte existe avec cet email
if ($stmt->num_rows === 0) {
    header("Location: login.php?error=Adresse email inconnue.");
    exit();
}

// 5. Récupérer l'id et le mot de passe hashé
$stmt->bind_result($etudiant_id, $hashed_password);
$stmt->fetch();

// 6. Vérification du mot de passe
if (!password_verify($password, $hashed_password)) {
    header("Location: login.php?error=Mot de passe incorrect.");
    exit();
}

$stmt->close();
$conn->close();

// 7. Authentification réussie → Lancer la session de l’étudiant
$_SESSION['etudiant_id'] = $etudiant_id;

// 8. Redirection vers le tableau de bord
header("Location: dashboard.php");
exit();