<?php
session_start();
//if (!isset($_SESSION['etudiant_id'])) {
//    header("Location: login.php?error=Veuillez vous connecter.");
//    exit();
//}

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déposer un fichier</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            padding-top: 60px;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            width: 450px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        input[type="file"], select, input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }
        button:hover {
            background-color: #388e3c;
        }
        .msg {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }
        a {
            text-align: center;
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #4caf50;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Dépôt d’un exercice</h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form action="process_upload.php" method="POST" enctype="multipart/form-data">
        <label for="course">Cours :</label>
        <select name="course" required>
            <option value="">-- Choisir --</option>
            <option value="algo">Algorithmique</option>
            <option value="systeme">Système</option>
        </select>

        <label for="exercise">Exercice n° :</label>
        <input type="number" name="exercise" min="1" required>

        <label for="language">Langage :</label>
        <select name="language" required>
            <option value="">-- Choisir --</option>
            <option value="Python">Python</option>
            <option value="C">C</option>
        </select>

        <label for="codefile">Fichier à envoyer :</label>
        <input type="file" name="codefile" accept=".py,.c" required>

        <button type="submit">Envoyer</button>
    </form>

    <a href="dashboard.php">⬅ Retour au tableau de bord</a>
</div>

</body>
</html>