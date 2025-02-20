#!/bin/bash

# Demande à l'utilisateur d'entrer le message de commit
read -p "Entrez le message de commit : " commit_message

# Ajoute tous les fichiers et commit
git add --all
git commit -m "$commit_message"

# Pousse les changements vers la branche principale
git push origin main

echo "Les changements ont été poussés avec succès."