#!/usr/bin/env bash
#
# Vérification syntaxique de tous les fichiers PHP du projet.
# Utilisé par « composer test » et par la CI.
#
# Les dossiers de dépendances (vendor, PHPMailer-master) sont ignorés :
# le code des librairies tierces ne nous appartient pas.

set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

PHP_BIN="${PHP_BIN:-php}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "ERREUR  PHP est introuvable (binaire « $PHP_BIN »)." >&2
    exit 1
fi

erreurs=0
fichiers=0

while IFS= read -r -d '' fichier; do
    fichiers=$((fichiers + 1))
    if ! sortie="$("$PHP_BIN" -l "$fichier" 2>&1)"; then
        echo "$sortie"
        erreurs=$((erreurs + 1))
    fi
done < <(find . \
    -type d \( -name vendor -o -name PHPMailer-master -o -name node_modules -o -name .git \) -prune -o \
    -type f -name '*.php' -print0 | sort -z)

if [ "$erreurs" -gt 0 ]; then
    echo ""
    echo "ECHEC  $erreurs fichier(s) PHP en erreur de syntaxe sur $fichiers analysé(s)."
    exit 1
fi

echo "OK  $fichiers fichier(s) PHP — syntaxe valide."
