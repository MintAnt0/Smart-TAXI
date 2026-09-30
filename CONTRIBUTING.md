# Conventions de travail

Ce document décrit comment contribuer au projet Smart Taxi : nommage des
branches, forme des messages de commit, contrôles automatiques et
procédure de mise en production.

## Sommaire

- [Installation de l'outillage](#installation-de-loutillage)
- [Contrôles automatiques](#contrôles-automatiques)
- [Hook pre-commit](#hook-pre-commit)
- [Conventions de branches (Git Flow)](#conventions-de-branches-git-flow)
- [Messages de commit](#messages-de-commit)
- [Intégration continue](#intégration-continue)
- [Mises à jour des dépendances](#mises-à-jour-des-dépendances)
- [Mise en production](#mise-en-production)

## Installation de l'outillage

PHP 7.4 ou plus récent est nécessaire pour les outils de développement
(le code applicatif reste compatible avec PHP 7.0).

```bash
composer install
composer run hooks:install
```

`composer install` récupère les paquets déclarés dans `composer.json` et le
fichier `composer.lock`, qui est versionné : tout le monde travaille donc
exactement sur les mêmes versions.

`composer run hooks:install` active le hook pre-commit du dépôt en
demandant à Git d'utiliser le dossier `.githooks/`. À faire une seule fois
après un clone.

## Contrôles automatiques

| Commande | Rôle |
| --- | --- |
| `composer test` | Vérifie la syntaxe de tous les fichiers PHP. |
| `composer lint` | Analyse le style et les règles de sécurité (PHP_CodeSniffer). |
| `composer lint:fix` | Corrige automatiquement ce qui peut l'être. |
| `composer format` | Met le code en forme (PHP-CS-Fixer). |
| `composer format:check` | Vérifie la mise en forme sans rien modifier. |
| `composer check` | Enchaîne tous les contrôles, comme la CI. |

`composer check` est la commande à lancer avant de pousser : elle produit
exactement le même verdict que l'intégration continue.

## Hook pre-commit

Le hook `.githooks/pre-commit` s'exécute à chaque `git commit` et vérifie
uniquement les fichiers **mis en index**, ce qui le rend rapide. Il contrôle :

1. **Secrets** — `config.php` ne doit jamais être versionné ; les mots de
   passe, clés d'API et jetons écrits en clair sont refusés.
2. **Syntaxe PHP** — `php -l` sur chaque fichier `.php` en index.
3. **Style et sécurité** — PHP_CodeSniffer (PSR-12 et fonctions dangereuses
   interdites : `eval`, `exec`, `unserialize`, `extract`…).
4. **Formatage** — PHP-CS-Fixer en mode `--dry-run`.
5. **Cohérence** — validité de `composer.json`, présence de `composer.lock`.

Si un contrôle échoue, le commit est annulé. Deux échappatoires :

```bash
# contourner un contrôle précis pour ce commit
SKIP=phpcs,format git commit -m "…"

# corriger le formatage automatiquement et remettre en index
CS_FIXER_FIX=1 git commit -m "…"

# tout contourner (à réserver aux situations urgentes)
git commit --no-verify
```

Quand un outil manque (PHP absent, `composer install` non lancé), le hook
le signale et laisse passer le commit au lieu de le bloquer. La variable
`STRICT=1` inverse ce comportement : les contrôles deviennent obligatoires.

## Conventions de branches (Git Flow)

Deux branches de longue vie, des branches de travail courtes :

```
main          # état stable, toujours déployable
develop       # intégration continue du travail en cours
```

| Branche | Rôle | Origine | Destination |
| --- | --- | --- | --- |
| `main` | Version stable, livrable | `develop` | — |
| `develop` | Intégration du travail en cours | `main` | `main`, `hotfix` |
| `feature/<sujet>` | Nouvelle fonctionnalité | `develop` | `develop` |
| `fix/<sujet>` | Correction de bug | `develop` | `develop` |
| `hotfix/<sujet>` | Correction urgente en production | `main` | `main` et `develop` |
| `release/<version>` | Préparation d'une livraison | `develop` | `main` et `develop` |

Le nommage suit `type/mot-cle-en-kebab-case` :

```
feature/calcul-prix-nuit
fix/echec-transaction-reservation
hotfix-injection-sql-dashboard   → hotfix/injection-sql-dashboard
```

### Parcours d'un travail

```bash
git switch develop
git switch -c feature/calcul-prix-nuit
# … développement, avec des commits réguliers …
git push -u origin feature/calcul-prix-nuit
```

Puis ouverture d'une **pull request vers `develop`**. Après relecture et
validation de la CI, fusion :

```bash
git switch develop
git pull
git merge --no-ff feature/calcul-prix-nuit   # --no-ff conserve l'historique de la branche
git push
git branch -d feature/calcul-prix-nuit
```

Le `--no-ff` est important : il garde la trace de la branche dans
l'historique, ce qui rend la relecture et le retour arrière possibles.

### Cycle de vie d'une version

Quand `develop` est stabilisé et que lesCorrections de style sont desirable :

```bash
git switch develop
git switch -c release/1.2.0
# mise à jour de la version dans composer.json et README.md
git commit -am "chore: préparation de la version 1.2.0"
```

La release est ensuite fusionnée dans `main` (avec une étiquette
`v1.2.0`) **puis** dans `develop`, afin que les deux branches restent
alignées. En cas de correction urgente, `hotfix/<sujet>` part de `main`
et est rebasé sur `develop` une fois la production stabilisée.

## Messages de commit

Format [Conventional Commits](https://www.conventionalcommits.org/) :

```
<type>(<portée>) : <description à l'impératif>
```

| Type | Usage |
| --- | --- |
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `docs` | Documentation uniquement |
| `style` | Formatage, sans changement de comportement |
| `refactor` | Réorganisation du code |
| `perf` | Amélioration de performance |
| `test` | Ajout ou correction de tests |
| `build` | Dépendances, outillage, CI |
| `chore` | Tâche d'entretien sans impact fonctionnel |
| `ci` | Modification des workflows |
| `deps` | Mise à jour de dépendance (généré par Dependabot) |

Exemples :

```
feat(reservation) : ajout du calcul du supplément de nuit
fix(login) : évite la fuite du message d'erreur sur e-mail inconnu
docs(README) : précise la procédure d'installation
build(deps) : mise à jour de phpmailer/phpmailer 6.9.0 → 6.10.0
```

Règles : une ligne de résumé, au plus 72 caractères, à l'impératif
(« ajoute », pas « ajouté »), sans point final. Le corps du message
précise le *pourquoi* quand le *quoi* n'est pas évident.

## Intégration continue

Le workflow `.github/workflows/ci.yml` se déclenche à chaque push et à
chaque pull request sur `main` et `develop`. Il exécute cinq vérifications :

| Vérification | Contenu |
| --- | --- |
| Syntaxe PHP | `composer test` sur PHP 7.4, 8.1 et 8.3 — garantit la compatibilité annoncée. |
| Qualité du code | `composer validate`, PHP_CodeSniffer et PHP-CS-Fixer. |
| Configuration | Vérifie que `config.php` n'est pas versionné et que `config.example.php` l'est. |
| Recherche de secrets | Analyse de l'historique complet avec Gitleaks. |
| Hook pre-commit | Analyse statique des scripts Bash avec ShellCheck. |

Le dépôt est-il protégé par des *branch protection rules* sur `main` ?
C'est recommandé : cela impose la validation de la CI avant toute fusion.
Le réglage se fait dans **Settings → Branches → Add rule** sur GitHub.

## Mises à jour des dépendances

Dependabot (`.github/dependabot.yml`) ouvre automatiquement des pull
requests le lundi à 09:00 (Europe/Paris) pour :

- les paquets Composer (PHPMailer et outils de développement) ;
- les actions GitHub utilisées par la CI.

Les mises à jour mineures des outils de développement sont regroupées en
une seule pull request : leurs versions évoluent ensemble, autant les tester
ensemble. Les versions majeures de PHP-CS-Fixer sont ignorées, car elles
changent le nom des règles et demandent une revalidation de
`.php-cs-fixer.php`.

Pour traiter une pull request Dependabot :

```bash
composer update phpmailer/phpmailer
composer check
```

## Mise en production

Le projet n'a pas de déploiement automatisé : la livraison reste manuelle,
ce qui convient à une application hébergée sur un serveur mutualisé ou un
VPS. La section [Pour automatiser plus tard](#pour-automatiser-plus-tard)
propose un modèle de workflow à activer le moment venu.

### Préparation

```bash
git switch main
git pull
composer install --no-dev --optimize-autoloader   # sans les outils de qualité
```

Le fichier `config.php` n'est pas versionné : il doit être créé ou mis à
jour sur le serveur à partir de `config.example.php`.

### Mise en ligne

```bash
# vérifier que la branche à livrer est bien la bonne
git log --oneline -5

# après validation de la CI, fusionner la release dans main
git tag -a v1.2.0 -m "Version 1.2.0"
git push origin main --tags
```

Puis, sur le serveur :

```bash
cd /var/www/smart-taxi
git pull origin main
composer install --no-dev --optimize-autoloader
```

### Retour arrière

```bash
git revert <commit>          # Preferred : preserves l'historique
```

ou, si plusieurs commits sont concernés, créer une branche `hotfix`
depuis l'étiquette précédente :

```bash
git switch -c hotfix/retour-v1.1.0 v1.1.0
git push origin hotfix/retour-v1.1.0
```

### Pour automatiser plus tard

Un workflow de déploiement peut être ajouté sur le modèle suivant :

```yaml
name: Déploiement
on:
  push:
    tags: ['v*']
jobs:
  deploiement:
    runs-on: ubuntu-latest
    environment: production
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Déploiement par SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/smart-taxi
            git pull origin main
            composer install --no-dev --optimize-autoloader
```

Les identifiants sont stockés dans **Settings → Secrets and variables →
Actions** du dépôt, jamais dans un fichier versionné. Cette automatisation
n'a pas été mise en place : elle ne devient pertinente qu'à partir du
moment où l'application est réellement déployée sur un serveur.
