<?php
session_start();

// === Connexion à la base de données ===
$mysqli = new mysqli("192.168.146.103", "webuser", "webpassword", "corrections");
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base : " . $mysqli->connect_error);
}

// === Traitement du formulaire ===
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Vérification champs requis
    if ($email === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Vérifie si l'email existe déjà
        $checkStmt = $mysqli->prepare("SELECT id FROM etudiants WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "❗ Cet email est déjà utilisé.";
        } else {
            // Hachage du mot de passe
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertion
            $stmt = $mysqli->prepare("INSERT INTO etudiants (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hash);
            if ($stmt->execute()) {
                $success = "Utilisateur créé avec succès !";
            } else {
                $error = "Erreur lors de la création du compte.";
            }
            $stmt->close();
        }

        $checkStmt->close();
    }

    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte étudiant</title>
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .register-box {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 320px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            background-color: #4caf50;
            color: white;
            padding: 10px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
        }
        .message.success {
            color: green;
        }
        .message.error {
            color: red;
        }
    </style>
</head>
<body>

<div class="register-box">
    <h2>Créer un compte</h2>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="email" name="email" placeholder="Adresse email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Créer le compte</button>
    </form>
</div>

</body>
</html>