#!/bin/bash

# === PARAMÈTRES DE CONFIGURATION ===
UPLOAD_DIR="/var/www/html/uploads"
REF_DIR="/var/www/html/reference_outputs"
TMP_DIR="/tmp/worker"
LOG_FILE="/var/log/worker.log"

DB_HOST="192.168.146.103"
DB_USER="webuser"
DB_PASS="webpassword"
DB_NAME="coursero"

# === PRÉREQUIS : packages nécessaires ===
REQUIRED_CMDS=("mysql" "diff" "timeout" "gcc" "python3")
for cmd in "${REQUIRED_CMDS[@]}"; do
    command -v $cmd >/dev/null || { echo "$cmd manquant."; exit 1; }
done

# === NETTOYAGE ET PRÉPARATION ===
mkdir -p "$TMP_DIR"
echo "🕑 $(date) - Lancement du worker" >> "$LOG_FILE"

# === RÉCUPÉRER LES SOUMISSIONS EN ATTENTE ===
queries=$(mysql -N -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e "SELECT id, filename, exercise, language FROM submissions WHERE status = 'en_attente'")

IFS=$'\n'
for ligne in $queries; do
    id=$(echo $ligne | awk '{print $1}')
    filename=$(echo $ligne | awk '{print $2}')
    exercise=$(echo $ligne | awk '{print $3}')
    language=$(echo $ligne | awk '{print $4}')

    filepath="$UPLOAD_DIR/$filename"
    ref_file="$REF_DIR/exo${exercise}.out"

    # Vérification des fichiers
    if [[ ! -f "$filepath" ]]; then
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', commentaire='Fichier introuvable' WHERE id=$id"
        continue
    fi

    if [[ ! -f "$ref_file" ]]; then
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', commentaire='Fichier de référence inexistant (exo${exercise}.out)' WHERE id=$id"
        continue
    fi

    echo "Traitement soumission #$id - $filename ($language)" >> "$LOG_FILE"

    # Préparation du dossier temporaire
    rm -rf "$TMP_DIR/*"
    cp "$filepath" "$TMP_DIR/code"

    run_output="$TMP_DIR/output.txt"
    run_error="$TMP_DIR/error.txt"
    ref_output="$ref_file"

    NOTE=0
    COMMENTAIRE=""

    case "$language" in
        "C")
            mv "$TMP_DIR/code" "$TMP_DIR/code.c"
            gcc "$TMP_DIR/code.c" -o "$TMP_DIR/exe" 2>>"$run_error"
            if [[ $? -ne 0 ]]; then
                COMMENTAIRE="Erreur compilation C"
                STATUS="erreur"
            else
                timeout 2s "$TMP_DIR/exe" > "$run_output" 2>>"$run_error"
                if [[ $? -ne 0 ]]; then
                    COMMENTAIRE="Erreur d'exécution (boucle ou crash)"
                    STATUS="erreur"
                else
                    if diff -q "$run_output" "$ref_output" > /dev/null; then
                        NOTE=20
                        COMMENTAIRE="Programme C correct"
                        STATUS="corrige"
                    else
                        NOTE=10
                        COMMENTAIRE="Résultat incorrect pour exercice $exercise"
                        STATUS="corrige"
                    fi
                fi
            fi
            ;;
        "Python")
            mv "$TMP_DIR/code" "$TMP_DIR/code.py"
            timeout 2s python3 "$TMP_DIR/code.py" > "$run_output" 2>>"$run_error"
            if [[ $? -ne 0 ]]; then
                COMMENTAIRE="❌ Erreur exécution Python"
                STATUS="erreur"
            else
                if diff -q "$run_output" "$ref_output" > /dev/null; then
                    NOTE=20
                    COMMENTAIRE="Script Python correct"
                    STATUS="corrige"
                else
                    NOTE=10
                    COMMENTAIRE="Résultat incorrect pour exercice $exercise"
                    STATUS="corrige"
                fi
            fi
            ;;
        *)
            COMMENTAIRE="Langage non supporté"
            STATUS="erreur"
            ;;
    esac

    # Mettre à jour la base
    # Échapper les caractères spéciaux dans les commentaires
    COMSQL=$(echo "$COMMENTAIRE" | sed "s/'/\\\\'/g")

    mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
    "UPDATE submissions SET status='$STATUS', note=$NOTE, commentaire='$COMSQL' WHERE id=$id"

    echo "📦 Soumission #$id traitée – Status: $STATUS – Note: $NOTE" >> "$LOG_FILE"

done

echo "Traitement terminé – $(date)" >> "$LOG_FILE"