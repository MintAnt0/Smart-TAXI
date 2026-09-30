## Description

<!-- Quelle fonctionnalité ou quelle correction cette pull request apporte-t-elle ?
     Rappelez le ticket ou la section du README concernés. -->

## Type de changement

- [ ] `feat` — nouvelle fonctionnalité
- [ ] `fix` — correction de bug
- [ ] `docs` — documentation
- [ ] `refactor` — réorganisation du code
- [ ] `build` — dépendances ou outillage
- [ ] `chore` — entretien

## Branche

- [ ] La branche suit la convention Git Flow (`feature/…`, `fix/…`, `hotfix/…`)
- [ ] La pull request cible `develop` (ou `main` pour une release et un hotfix)

## Vérifications effectuées

- [ ] `composer check` passe en local
- [ ] `composer.lock` est à jour si des dépendances ont changé
- [ ] Aucun secret n'a été ajouté (`config.php` n'est pas versionné)
- [ ] La CI est verte sur cette pull request

## Sécurité

- [ ] Les requêtes SQL utilisent toujours des requêtes préparées
- [ ] Les sorties utilisateur passent par `htmlspecialchars`
- [ ] Les entrées sont validées côté serveur

<!-- Marquez cette case si les trois conditions ci-dessus sont vraies. -->

- [ ] Aucune donnée sensible ne circule dans les journaux ou les messages d'erreur

## Captures d'écran

<!-- Interface modifiée ? Ajoutez une capture avant / après. -->

## Notes pour la relecture

<!-- Quel point mérite une attention particulière ? -->
