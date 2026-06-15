# controle de clean code


## Partie 1 : Mise en propre des deux controllers (UserController et GameController)

### 1) Failfast et commentaires:

Pour commencer, j'ai supprimé tous les else et les commentaires des deux controlleurs afin de réduire le nombre de lignes.

### 2) Attribution des nom propres et cohérents :

J'ai remarqué que certaines variables possèdent un nom mélangé en anglais et en français (ex: liste_des_users, delete_user_by_identifiant, etc ...). 

Certaines sont entièrement en francais mais il existe les mêmes variables en anglais (ex: id et identifiant).

Les noms des routes et certaines fonctions sont également incohérentes avec un mélange de langue.

### 3) Code inutilisé :

PDO est appelé dans Usercontroller mais jamais utilisé et également un "return null" à été supprimé car il était inutilisé.

Dans GameController, un "return new JsonResponse('coucou');" est écrit mais ne sert à rien.

## Partie 2 :  Factorisation de Usercontroller 

### 1) Extraction des méthodes privées de "updateUser" :

J'ai extrait deux méthodes privées :

- updateUserName(User $user, string $value, EntityManagerInterface $entityManager): void
- updateUserAge(User $user, int $value): void

### 2) Fail-fast dans updateUser :

La méthode originale possédait plusieurs if/else. J'ai inversé ces condition pour retourner immédiatement une erreur 404 si l'utilisateur n'est pas trouvé, ce qui supprime un niveau d'imbrication.

De même, la vérification "$form->isValid()" a été inversée en "!$form->isValid()" pour retourner immédiatement le 400.

### 3) Suppression du code mort dans "updateUser" :

La vérification "$request->getMethod() == 'PATCH'" a été supprimée car Symfony gère déjà la restriction de méthode dans la route
Le "return new JsonResponse('Wrong method', 405)" suite à la méthode, est également supprimé.
 
## Partie 3 :  Factorisation de GameController

### 1) Fail-fast sur toutes les méthodes

J'ai appliqué le même principe de failfast sur les méthodes refactorisées :

- launchGame 
- getGameInfo
- inviteToGame
- gamePlay 
 
### 2) Refactorisation de "inviteToGame"

Avant, toute la logique était enfermée avec des retours 401/404 ambigus en fin de méthode.
Maintenant, chaque cas d'erreur est traité immédiatement. Le flux de succès (ex: ajout du joueur droit + passage en ongoing) est maintenant détaché à la fin de la méthode.

### 3) Suppression de la duplication dans "gamePlay"

La méthode originale contenait deux blocs quasi identiques (environ 100 lignes) :
- un pour le joueur gauche
- un pour le joueur droit

Chaque bloc possédait son propre switch pour calculer le résultat

J'ai unifié le flux :

- enregistrement du coup du joueur actuel ("setPlayLeft" ou "setPlayRight")
- si les deux coups sont présents, calculer le résultat et passer la partie en "finished" 
- un seul "flush()" et un seul "return" est mis en place

### 4) Extraction de "resolveGameMovesResult()"

La logique de calcul du gagnant a été extraite dans une méthode privée à part :

"private function resolveGameMovesResult(string $leftChoice, string $rightChoice): string"


## Commandes de test :

### Réinitialiser la BDD
docker compose exec -u 1000 -w /home/cleancode-course-controle-2 cleancode-course-controle-2 composer reset-db

### Lancer les tests
docker compose exec -u 1000 -w /home/cleancode-course-controle-2 cleancode-course-controle-2 php bin/phpunit