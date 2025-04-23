# Projet Coursero – Correction Automatique d’Exercices (PoC)

## 1. Contexte du projet

Plateforme web permettant aux étudiants de déposer, suivre et évaluer automatiquement des exercices de programmation.

Objectifs :
- Proposer une interface de connexion pour les étudiants.
- Permettre le dépôt de fichiers d’exercices (langages : Python, C).
- Stocker et organiser les soumissions.
- Offrir une interface de suivi des dépôts et des résultats de correction.
- Intégrer une base pour de futurs outils de correction automatique.

## 2. Fonctionnalités

### 2.1 - Gestion des utilisateurs
Connexion via login/mot de passe.
Interface de session pour les étudiants.

### 2.2 - Dépôt de fichiers
Envoi d’exercices au format .py, .c, etc.
Organisation des fichiers par étudiant et par exercice.

### 2.3 - Suivi des soumissions
Vue des fichiers soumis par exercice.
Informations de date/heure, statut de l’évaluation.

### 2.4 - Moteur de correction
Les fichiers exécutables peuvent être testés via des scripts (modulable).
Évaluation simple par compilation/exécution et affichage du résultat.

## 3. Guide d'installation

### 3.1 - Prérequis
Installation des VMs avec le logiciel de virtualisation de votre choix (Vmware Workstation Pro utilisé pour ce projet)
Lien des VMs : https://mega.nz/folder/6d9Akb4D#rMqQIehODLRE7Faj2DKcpw
/!\ Il faudra penser à configurer l'ensemble des VMs au sein d'un réseau et de modifier si besoin les IPs des machines /!\

PHP ≥ 7.4
MySQL / MariaDB

### 3.2 - Installation
1. Cloner le dépôt
2. Importer la base de données via le fichier config.sql disponible.
3. Configurer vos identifiants MySQL dans le fichier config.php (non inclus ici).
4. Lancer le projet

### 3.3 - Tests
Accédez à http://localhost/coursero/ via un navigateur.
Créez un compte étudiant manuellement dans la base de données ou via la page /register.php.

## Technologies utilisées
* Frontend : HTML / CSS / JavaScript
* Backend : PHP
* Base de données : MySQL
* Langages pour les exercices : Python, C

## 5. Authors

* **Melvin Cureau** - [MelvinCureau](https://github.com/MelvinCr1)

## 6. Licence

Ce projet est sous licence MIT. Consultez le fichier LICENSE pour plus de détails.
