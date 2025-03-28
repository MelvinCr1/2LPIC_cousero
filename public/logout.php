<?php
session_start();

// Supprimer toutes les variables de session
$_SESSION = [];

// Supprimer la session côté serveur
session_destroy();

// Rediriger vers la page de connexion avec message
header("Location: login.php?message=Déconnexion réussie");
exit();