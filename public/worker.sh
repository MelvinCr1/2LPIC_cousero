#!/bin/bash

# === PARAMÈTRES ===
UPLOAD_DIR="/var/www/html/uploads"
REFERENCE_DIR="/var/www/html/reference_outputs"
TMP_DIR="/tmp/soumission_worker"

PHP_CONFIG="/var/www/html/config.php"

# Utilisateur non privilégié pour exécuter le code étudiant
EXEC_USER="nobody"

# === DÉPENDANCES : mysql + diff ===
command -v mysql >/dev/null 2>&1 || { echo "Erreur : mysql non installé."; exit 1; }

# === EXTRAIRE LOGIN/PASSWORD MYSQL depuis config.php ===
DB_USER=$(grep "db_username" $PHP_CONFIG | cut -d "'" -f4)
DB_PASS=$(grep "db_password" $PHP_CONFIG | cut -d "'" -f4)
DB_NAME=$(grep "db_name" $PHP_CONFIG | cut -d "'" -f4)
DB_HOST=$(grep "db_servername" $PHP_CONFIG | cut -d "'" -f4)

# === PRÉPARER le dossier temporaire ===
mkdir -p "$TMP_DIR"

# === RÉCUPÉRER les soumissions en attente ===
echo "Chargement des soumissions en attente..."
soumissions=$(mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -N -e "SELECT id, filename FROM submissions WHERE status='en_attente'")

IFS=$'\n'
for ligne in $soumissions; do
    id=$(echo $ligne | awk '{print $1}')
    fichier=$(echo $ligne | awk '{print $2}')

    fullpath="$UPLOAD_DIR/$fichier"
    
    echo "Traitement de la soumission #$id : $fichier"

    # Copie temporaire
    cp "$fullpath" "$TMP_DIR/programme.c" || {
        echo "Erreur : Impossible de copier $fichier"
        mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "UPDATE submissions SET status='erreur', commentaire='Copie impossible' WHERE id=$id"
        continue
    }

    # Compilation
    gcc "$TMP_DIR/programme.c" -o "$TMP_DIR/exe" 2> "$TMP_DIR/erreur_compile.txt"
    if [ $? -ne 0 ]; then
        echo "Compilation échouée"
        erreur=$(<"$TMP_DIR/erreur_compile.txt")
        mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "UPDATE submissions SET status='erreur', commentaire='Erreur compilation : $(printf %q "$erreur")' WHERE id=$id"
        continue
    fi

    # Exécution (avec timeout pour éviter les boucles infinies)
    timeout 2s su $EXEC_USER -s /bin/bash -c "$TMP_DIR/exe" > "$TMP_DIR/output.txt" 2> "$TMP_DIR/exec_err.txt"
    if [ $? -ne 0 ]; then
        echo "Erreur pendant l’exécution."
        err=$(<"$TMP_DIR/exec_err.txt")
        mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "UPDATE submissions SET status='erreur', commentaire='Erreur execution : $(printf %q "$err")' WHERE id=$id"
        continue
    fi
    
    # Comparaison avec la sortie attendue
    ref="$REFERENCE_DIR/output_ref.txt"
    if diff -q "$TMP_DIR/output.txt" "$ref" >/dev/null; then
        echo "Résultat correct"
        mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "UPDATE submissions SET status='corrige', note=20, commentaire='Correct' WHERE id=$id"
    else
        echo "Résultat incorrect"
        mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "UPDATE submissions SET status='corrige', note=0, commentaire='Mauvais résultat' WHERE id=$id"
    fi

done