---
layout: default
lang: fr_FR
---

Description
===

Groupir permet de **grouper des équipements** (et des scénarios). Chaque groupe, par exemple « Lumières salon », est un équipement Jeedom qui expose ses propres commandes :

- des **commandes info**, qui indiquent si les équipements du groupe sont dans l'état attendu (ex. `on` vaut `ok` si toutes les lumières sont allumées) ;
- des **commandes action**, qui pilotent tous les équipements d'un coup (ex. `Allumer` allume les lumières, règle leur luminosité et leur couleur).

Ces commandes s'utilisent comme n'importe quelle commande Jeedom : scénarios, widgets, historique, alertes...

![Liste des groupes](../images/Groupes.png)

Configuration du plugin
===

Après l'installation depuis le Market, activez le plugin. **Aucune configuration globale n'est nécessaire** : chaque groupe se configure depuis son équipement, dans l'onglet *Correspondances*.

La fonctionnalité **cron** est active : chaque minute, le plugin recalcule les groupes dont une commande info dépend de l'état d'un scénario. Le niveau de log *Debug* détaille chaque recalcul et chaque action exécutée, ce qui aide au diagnostic.

L'interface est disponible en français, anglais, allemand, espagnol et italien.

Créer un groupe
===

Cliquez sur **Ajouter un groupe**, donnez-lui un nom, puis configurez les deux onglets.

Onglet « Général »
---

Nom, objet parent, catégorie, options *Activer* / *Visible* et description, comme pour tout équipement.

Le bloc **Scénarios liés** liste :

- les scénarios qui **utilisent ce groupe** (une de ses commandes en déclencheur, condition ou action) ;
- les scénarios **pilotés par ce groupe** (lancés, arrêtés, activés ou désactivés par une de ses commandes action).

Un clic ouvre le scénario.

![Onglet Général](../images/Groupe.General.png)

Onglet « Correspondances »
---

Le groupe contient des **commandes**, chacune étant un sous-groupe d'équipements :

- **Commande info** (pastille bleue) : calcule un état à partir des équipements ;
- **Commande action** (pastille orange) : exécute des actions sur les équipements.

Donnez un **nom** à la commande : ce sera le nom de la commande du groupe. Ajoutez ensuite une ligne par équipement avec le bouton **Équipement** (ou par scénario avec le bouton **Scénario**). Chaque ligne se remplit de gauche à droite : **Objet**, **Équipement**, **Commande**. Chaque liste a un champ *Filtre* ; un filtre qui ne laisse qu'un résultat le sélectionne automatiquement, et un équipement déjà présent dans la commande n'est plus proposé.

![Onglet Correspondances](../images/Correspondances.png)

### Commande info

Pour chaque équipement : la commande info, une condition (*égal à*, *différent de*, *supérieur à*, *contient*...) et la **valeur attendue** (la valeur actuelle est affichée en indication).

Les **valeurs renvoyées par la commande** dépendent d'une règle :

- **si tous correspondent** (par défaut) : toutes les lignes doivent correspondre ;
- **si au moins un correspond** : ex. « au moins une lumière allumée » ;
- **si au moins N correspondent** : ex. au moins 2 fenêtres ouvertes sur 4.

La commande prend alors la valeur **alors** (par défaut `ok`), sinon la valeur **sinon** (par défaut `ko`). Si les deux valeurs sont numériques (ex. `1` / `0`), la commande est numérique et peut être historisée en graphique.

Une ligne **Scénario** porte sur l'état d'un scénario : **est actif**, **est inactif**, **est en cours** ou **est arrêté**. Jeedom ne signale pas les changements d'état d'un scénario : ces lignes sont recalculées chaque minute (ou immédiatement avec la commande *Rafraîchir*).

![Commande info](../images/Correspondances.Info.png)

### Commande action

Pour chaque équipement : une ou plusieurs commandes action, exécutées dans l'ordre. Le bouton **+ Commande** enchaîne plusieurs actions sur un même équipement (ex. `On`, puis `Luminosité` à 254, puis `Couleur`).

Le champ **Valeur** s'adapte au type de la commande : nombre (avec la plage autorisée en indication, ex. `153 à 500`, et la saisie limitée à cette plage), couleur, liste ou message. Pour une commande sans valeur (ex. `On`), aucun champ n'est affiché.

Une ligne **Scénario** lance, arrête, active ou désactive un scénario, dans l'ordre avec les équipements (ex. allumer les lumières, puis lancer le scénario « Ambiance soirée »).

La **pause entre deux actions** (0 à 30 secondes) espace chaque action : utile pour les ampoules qui ignorent une couleur envoyée juste après l'allumage.

![Commande action](../images/Correspondances.Action.png)

### Organiser les commandes

- <i class="fas fa-grip-vertical"></i> **Glisser-déposer** : réordonne les commandes (et donc les boutons du widget), les lignes et les actions d'un équipement.
- <i class="fas fa-chevron-right"></i> **Plier / déplier** : les commandes sont pliées à l'ouverture ; les boutons en haut à droite plient ou déplient tout.
- <i class="fas fa-clone"></i> **Cloner une ligne** : ajoute une ligne avec le même objet et les mêmes actions ; une fois l'équipement choisi, ses commandes portant les mêmes noms sont sélectionnées avec les mêmes valeurs.
- <i class="fas fa-clone"></i> **Cloner une commande** : duplique la commande entière, nommée « … (copie) ».
- <i class="fas fa-cog"></i> **Configuration** : ouvre la configuration Jeedom de la commande (historique, type générique, alertes, affichage), une fois le groupe enregistré.

Une ligne dont l'équipement, la commande ou le scénario a été supprimé est signalée **en rouge**. L'enregistrement est refusé tant qu'une ligne est incomplète, avec un message qui indique la commande et la ligne à corriger.

Commandes d'un groupe
===

| Commande | Type | Rôle |
|---|---|---|
| *une par commande info* | info | Valeur « alors » ou « sinon » selon la règle |
| *une par commande action* | action | Exécute les lignes de la commande, dans l'ordre |
| Rafraîchir | action | Recalcule toutes les commandes info du groupe |

Exemple dans un scénario : déclencheur *[Salon][Lumières salon][on] égal à ok*, action *[Salon][Lumières salon][Allumer]*.

Widget
===

Chaque groupe a son propre widget :

![Widgets](../images/Widget.png)

- une ligne par commande info, avec une pastille verte ou rouge, le nombre d'équipements conformes (ex. `2/2`) et la valeur de la commande ;
- la **couleur actuelle** de chaque lumière du groupe (lue sur l'info d'état de sa commande couleur, ou sur une info de type générique *Couleur lumière*) ;
- un bouton par commande action ;
- l'icône <i class="fas fa-sync-alt"></i> recalcule l'état du groupe.

Le widget se met à jour en direct. Une commande masquée (icône <i class="fas fa-cog"></i> > *Afficher*) n'y apparaît pas.
