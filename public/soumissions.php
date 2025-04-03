<?php
require_once("config.php");
require_once("jwt_utils.php");

// 1. Récupérer le JWT depuis l'URL
$token = $_GET['token'] ?? null;

if (!$token || !is_jwt_valid($token)) {
    die("Token JWT invalide ou expiré.<br><a href='login.php'>Se reconnecter.</a>");
}

// 2. Extraire l'id de l'étudiant depuis le token
$payload = get_payload_from_jwt($token);
$id_etudiant = $payload['etudiant_id'] ?? null;

if (!$id_etudiant) {
    die("Impossible d'extraire l'étudiant depuis le token.");
}

// 3. Connexion à la BDD
$conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// 4. Requête SQL
$sql = "SELECT id, filename, status, note, commentaire, submitted_at FROM submissions WHERE etudiant_id = ? ORDER BY submitted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_etudiant);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes soumissions</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            margin-top: 60px;
            width: 90%;
            max-width: 1000px;
            background: #fff;
            padding: 40px 50px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 14px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
        }

        .status-en_attente {
            color: orange;
            font-weight: bold;
        }

        .status-corrige {
            color: green;
            font-weight: bold;
        }

        .status-erreur {
            color: red;
            font-weight: bold;
        }

        .back-link {
            display: block;
            margin-top: 30px;
            text-align: center;
        }

        .back-link a {
            color: #4caf50;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>📄 Mes soumissions</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom du fichier</th>
                <th>Date de soumission</th>
                <th>Statut</th>
                <th>Note</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><a href="uploads/<?= htmlspecialchars(basename($row['filename'])) ?>" target="_blank"><?= htmlspecialchars($row['filename']) ?></a></td>
                    <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                    <td class="status-<?= htmlspecialchars($row['status']) ?>"><?= ucwords(str_replace("_", " ", $row['status'])) ?></td>
                    <td><?= is_null($row['note']) ? "-" : $row['note'] . "/20" ?></td>
                    <td><?= htmlspecialchars($row['commentaire'] ?? "") ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">Aucune soumission à afficher.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="back-link">
        <a href="dashboard.php?token=<?= urlencode($token) ?>">← Retour au tableau de bord</a>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>