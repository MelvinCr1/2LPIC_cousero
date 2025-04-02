<?php
session_start();
//if (!isset($_SESSION['etudiant_id'])) {
//    header("Location: login.php?error=Veuillez vous connecter.");
//    exit();
//}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $etudiant_id = $_SESSION['etudiant_id'];
    $ancien      = trim($_POST['ancien'] ?? '');
    $nouveau     = trim($_POST['nouveau'] ?? '');
    $confirmation = trim($_POST['confirmation'] ?? '');

    if ($ancien === '' || $nouveau === '' || $confirmation === '') {
        $message = "Tous les champs sont obligatoires.";
    } elseif ($nouveau !== $confirmation) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
    } else {
        $conn = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");

        if ($conn->connect_error) {
            die("Erreur de connexion : " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT id FROM etudiants WHERE id = ? AND password = PASSWORD(?)");
        $stmt->bind_param("is", $etudiant_id, $ancien);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->close();
            $update = $conn->prepare("UPDATE etudiants SET password = PASSWORD(?) WHERE id = ?");
            $update->bind_param("si", $nouveau, $etudiant_id);
            if ($update->execute()) {
                $message = "Mot de passe modifié avec succès.";
            } else {
                $message = "Erreur lors de la mise à jour.";
            }
            $update->close();
        } else {
            $message = "Ancien mot de passe incorrect.";
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer le mot de passe</title>
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

        .container {
            background-color: #fff;
            padding: 35px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .msg {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .msg.success {
            color: green;
        }

        .msg.error {
            color: red;
        }

        a {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #4caf50;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🔐 Modifier le mot de passe</h2>

    <?php if ($message): ?>
        <div class="msg <?= str_starts_with($message, '✅') ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="password" name="ancien" placeholder="Ancien mot de passe" required>
        <input type="password" name="nouveau" placeholder="Nouveau mot de passe" required>
        <input type="password" name="confirmation" placeholder="Confirmer le nouveau mot de passe" required>
        <button type="submit">Modifier</button>
    </form>

    <a href="dashboard.php">⬅ Retour au tableau de bord</a>
</div>

</body>
</html>