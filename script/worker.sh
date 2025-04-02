#!/bin/bash

set -e

# === CONFIGURATION ===
UPLOAD_DIR="/var/www/html/uploads"
REF_DIR="/var/www/html/reference_outputs"
DB_HOST="192.168.146.103"
DB_USER="webuser"
DB_PASS="webpassword"
DB_NAME="corrections"
LOG_FILE="/var/log/worker.log"

# === PRÉVENIR LES EXÉCUTIONS CONCURRENTES ===
LOCK_FILE="/tmp/worker.lock"
if [ -f "$LOCK_FILE" ]; then
    echo "$(date "+%F %T") Worker déjà en cours" >> "$LOG_FILE"
    exit 1
fi
touch "$LOCK_FILE"
trap "rm -f $LOCK_FILE" EXIT

# === CRÉER RÉPERTOIRE TEMPORAIRE DE TRAVAIL ===
TMP_BASE="/tmp/worker"
mkdir -p "$TMP_BASE"

# === EXTRAIRE LES SOUMISSIONS EN ATTENTE ===
soumissions=$(mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" -N -e \
"SELECT id, filename, exercise, language FROM $DB_NAME.submissions WHERE status = 'en_attente'" | tr '\t' '|')

IFS=$'\n'
for ligne in $soumissions; do
    IFS='|' read -r id file exercise language <<< "$ligne"

    echo "$(date "+%F %T") Soumission #$id : $file sur exo $exercise ($language)" >> "$LOG_FILE"

    # === Dossier de travail temporaire ===
    WDIR="$TMP_BASE/task_$id"
    mkdir -p "$WDIR"

    if [ ! -f "$UPLOAD_DIR/$file" ]; then
        echo "$(date "+%F %T") Fichier '$file' introuvable dans $UPLOAD_DIR" >> "$LOG_FILE"
        mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
        "UPDATE submissions SET status='erreur', commentaire='Fichier manquant' WHERE id=$id"
        continue
    fi

    cp "$UPLOAD_DIR/$file" "$WDIR/code"

    # === Testeurs ===
    total_tests=0
    passed_tests=0

    for infile in "$REF_DIR/exo${exercise}_input_"*.txt; do
        test_id=$(basename "$infile" | sed -E "s/exo${exercise}_input_([0-9]+)\.txt/\1/")
        outfile="$REF_DIR/exo${exercise}_output_${test_id}.txt"
        [[ -f "$outfile" ]] || continue

        total_tests=$((total_tests + 1))
        output_file="$WDIR/output_${test_id}.txt"
        error_file="$WDIR/error_${test_id}.txt"

        case "$language" in
            "C")
                mv "$WDIR/code" "$WDIR/prog.c"
                gcc "$WDIR/prog.c" -o "$WDIR/exe" 2>"$error_file"
                if [ $? -eq 0 ]; then
                    timeout 2s bash -c "cd $WDIR && ulimit -t 2; ./exe < $infile > $output_file" 2>>"$error_file"
                fi
                ;;
            "Python")
                mv "$WDIR/code" "$WDIR/code.py"
                timeout 2s bash -c "cd $WDIR && ulimit -t 2; python3 code.py < $infile > $output_file" 2>"$error_file"
                ;;
            *)
                mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
                "UPDATE submissions SET status='erreur', commentaire='Langage non supporté' WHERE id=$id"
                rm -rf "$WDIR"
                continue 2
                ;;
        esac

        diff -q "$output_file" "$outfile" > /dev/null && passed_tests=$((passed_tests + 1))
    done

    # === Résultat final ===
    if [[ $total_tests -eq 0 ]]; then
        note=0
        commentaire="Aucun test trouvé pour l'exercice $exercise."
        status="erreur"
    else
        pourcentage=$(( 100 * passed_tests / total_tests ))
        note=$(( 20 * passed_tests / total_tests ))
        commentaire="$passed_tests / $total_tests tests passés ($pourcentage%)"
        status="corrige"
    fi

    # Échapper les éventuels caractères spéciaux
    commentaire_sql=$(printf "%s" "$commentaire" | sed "s/'/''/g")

    # === Mise à jour SQL ===
    mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" -e \
    "UPDATE submissions SET status='$status', note=$note, commentaire='$commentaire_sql' WHERE id=$id"

    echo "$(date "+%F %T") Correction #$id : $note/20 - $commentaire" >> "$LOG_FILE"

    # === Nettoyage ===
    rm -rf "$WDIR"
    rm -f "$UPLOAD_DIR/$file"
done

echo "$(date "+%F %T") Fin de cycle de correction" >> "$LOG_FILE"