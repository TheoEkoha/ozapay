#!/bin/bash

# Fichier contenant le numéro de commit
COMMIT_NUMBER_FILE="commit_number.txt"

# Vérifie si le fichier existe, sinon le crée avec le numéro 0
if [ ! -f "$COMMIT_NUMBER_FILE" ]; then
    echo "0" > "$COMMIT_NUMBER_FILE"
fi

# Lis le numéro de commit actuel
COMMIT_NUMBER=$(cat "$COMMIT_NUMBER_FILE")

# Incrémente le numéro de commit
NEW_COMMIT_NUMBER=$((COMMIT_NUMBER + 1))

# Met à jour le fichier avec le nouveau numéro de commit
echo "$NEW_COMMIT_NUMBER" > "$COMMIT_NUMBER_FILE"

# Effectue le commit avec le nouveau numéro
git add .
git commit -m "fix: INSCRIPTION $NEW_COMMIT_NUMBER"

# Ajoute les autres commandes nécessaires ici (comme le push)
git push origin main