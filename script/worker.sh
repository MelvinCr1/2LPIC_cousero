#!/bin/bash

set -e

# === CONFIGURATION ===
UPLOAD_DIR="/var/www/html/uploads"
TEMPLATE_DIR="/var/www/html/templates"
DB_HOST="192.168.146.103"
DB_USER="webuser"
DB_PASS="webpassword"
DB_NAME="corrections"
LOG_FILE="/var/log/worker.log"

# === PRÉVENIR EXÉCUTIONS EN DOUBLE ===
LOCK_FILE="/tmp/worker.lock"
if [ -f "$LOCK_FILE" ]; then
    echo "$(date "+%F %T") Worker déjà en cours" >> "$LOG_FILE"
    exit 1
fi
touch "$LOCK_FILE"
trap "rm -f $LOCK_FILE" EXIT

# === RÉPERTOIRE TEMPORAIRE DE TRAVAIL ===
TMP_BASE="/tmp/worker"
mkdir -p "$TMP_BASE"

# === RÉCUPÉRER TOUTES LES SOUMISSIONS EN ATTENTE ===
soumissions=$(mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" -N -e \
"SELECT id, filename, exercise, language FROM $DB_NAME.submissions WHERE status = 'en_attente'" | tr '\t' '|')

IFS=$'\n'
for ligne in $soumissions; do
    IFS='|' read -r id file exercise language <<< "$ligne"

    echo "$(date "+%F %T") Soumission #$id (exo $exercise, $language)" >> "$LOG_FILE"

    # === PRÉPARER RÉPERTOIRE DE TRAVAIL ===
    WDIR="$TMP_BASE/task_$id"
    mkdir -p "$WDIR"

    student_file="$UPLOAD_DIR/$file"
    if [ ! -f "$student_file" ]; then
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', commentaire='Fichier absent' WHERE id=$id"
        continue
    fi

    EXO_DIR="$TEMPLATE_DIR/exo$exercise"
    CONFIG_FILE="$EXO_DIR/config.json"
    if [ ! -f "$CONFIG_FILE" ]; then
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', commentaire='Template absent pour exo $exercise' WHERE id=$id"
        continue
    fi

    # === PARAMÈTRES DU TEMPLATE ===
    timeout_exec=$(jq -r '.timeout' "$CONFIG_FILE")
    languages_supported=$(jq -r '.languages[]' "$CONFIG_FILE")

    if ! echo "$languages_supported" | grep -q "$language"; then
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', commentaire='Langage non supporté' WHERE id=$id"
        continue
    fi

    cp "$student_file" "$WDIR/code"

    # === COMPILATION / PRÉPARATION ===
    case "$language" in
        "C")
            mv "$WDIR/code" "$WDIR/prog.c"
            gcc "$WDIR/prog.c" -o "$WDIR/exe" 2>"$WDIR/compile_error.txt"
            compile_ok=$?
            ;;
        "Python")
            mv "$WDIR/code" "$WDIR/code.py"
            compile_ok=0
            ;;
    esac

    if [ "$compile_ok" -ne 0 ]; then
        commentaire="Erreur à la compilation"
        note=0
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', note=0, commentaire='$commentaire' WHERE id=$id"
        rm -rf "$WDIR" "$student_file"
        continue
    fi

    # === LANCER LES TESTS ===
    total=0
    ok=0
    commentaire=""

    for row in $(jq -c '.tests[]' "$CONFIG_FILE"); do
        total=$((total + 1))
        input_file="$EXO_DIR/$(echo "$row" | jq -r '.input')"
        output_ref="$EXO_DIR/$(echo "$row" | jq -r '.output')"
        output_file="$WDIR/output_$total.txt"
        error_file="$WDIR/error_$total.txt"

        case "$language" in
            "C")
                timeout ${timeout_exec}s bash -c "cd $WDIR && ulimit -t $timeout_exec; ./exe < $input_file > $output_file" 2>"$error_file"
                ;;
            "Python")
                timeout ${timeout_exec}s bash -c "cd $WDIR && ulimit -t $timeout_exec; python3 code.py < $input_file > $output_file" 2>"$error_file"
                ;;
        esac

        if diff -q "$output_file" "$output_ref" > /dev/null; then
            ok=$((ok + 1))
        else
            commentaire+="Test $total échoué.\n"
        fi
    done

    if [ "$total" -eq 0 ]; then
        note=0
        commentaire="Aucun test prévu pour exercice $exercise."
        status="erreur"
    else
        note=$(( 20 * ok / total ))
        status="corrige"
        commentaire="$ok / $total tests réussis. Score : $note%.\n$commentaire"
    fi

    # — Échappement du commentaire pour MySQL —
    commentaire_sql=$(printf "%s" "$commentaire" | sed "s/'/''/g")

    # === MISE À JOUR EN BASE ===
    mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
    "UPDATE submissions SET status='$status', note=$note, commentaire='$commentaire_sql' WHERE id=$id"

    echo "$(date "+%F %T") Soumission #$id notée $note%" >> "$LOG_FILE"

    # — NETTOYAGE —
    rm -rf "$WDIR"
    rm -f "$student_file"

done

echo "$(date "+%F %T") Tous les fichiers traités" >> "$LOG_FILE"