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
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #eee;
        }
        .status-en_attente {
            color: orange;
        }
        .status-corrige {
            color: green;
        }
        .status-erreur {
            color: red;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center">Mes soumissions</h2>
    <p style="text-align:center"><a href="dashboard.php?token=<?= urlencode($token) ?>">← Retour au tableau de bord</a></p>

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
                    <td class="status-<?= htmlspecialchars($row['status']) ?>">
                        <?= ucwords(str_replace("_", " ", $row['status'])) ?>
                    </td>
                    <td><?= is_null($row['note']) ? "-" : $row['note'] . "/20" ?></td>
                    <td><?= htmlspecialchars($row['commentaire'] ?? "") ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">Aucune soumission à afficher.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>