<?php
session_start();
//if (!isset($_SESSION['etudiant_id'])) {
//    header("Location: login.php?error=Veuillez vous connecter.");
//    exit();
//}
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
    <p>ID de session : <strong><?php echo $_SESSION['etudiant_id']; ?></strong></p>

    <div class="actions">
        <a href="upload.php">Déposer un fichier</a>
        <a href="soumissions.php">Mes soumissions</a>
        <a href="change_password.php">Changer mon mot de passe</a>
        <a href="logout.php" class="logout">Se déconnecter</a>
    </div>
</div>

</body>
</html>