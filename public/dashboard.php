<?php
require_once 'jwt_utils.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Récupérer le token JWT depuis l'URL (GET)
$token = $_GET['token'] ?? null;

// 2. Vérification : existe et est valide ?
if (!$token || !is_jwt_valid($token)) {
    die("Accès refusé. Token invalide ou expiré.<br><a href='login.php'>Retour à la connexion</a>");
}

// 3. Extraire l'identité de l'étudiant depuis le token
$payload = get_payload_from_jwt($token);
$etudiant_id = $payload['etudiant_id'] ?? null;

if (!$etudiant_id) {
    die("Impossible d’extraire l'identifiant depuis le token.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord Étudiant</title>
    <style>
        body {
            background-color: #f2f2f2;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 60px;
            margin: 0;
        }

        .dashboard-container {
            background-color: #fff;
            padding: 40px 50px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 400px;
            text-align: center;
        }

        h1 {
            margin-bottom: 20px;
            color: #333;
        }

        .actions a {
            display: block;
            margin: 15px 0;
            padding: 12px;
            background-color: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .actions a:hover {
            background-color: #388e3c;
        }

        .logout {
            background-color: #d9534f;
        }

        .logout:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>Bienvenue étudiant 👋</h1>
    <p>Identifiant : <strong><?= htmlspecialchars($etudiant_id); ?></strong></p>

    <div class="actions">
        <a href="upload.php?token=<?= urlencode($token) ?>">Déposer un fichier</a>
        <a href="soumissions.php?token=<?= urlencode($token) ?>">Mes soumissions</a>
        <a href="change_password.php?token=<?= urlencode($token) ?>">Changer mon mot de passe</a>
        <a href="logout.php">Se déconnecter</a>
    </div>
</div>

</body>
</html>