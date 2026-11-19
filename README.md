# Groupir - plugin Jeedom

Groupir regroupe des équipements (et des scénarios) Jeedom. Chaque groupe, par exemple « Lumières salon », devient un équipement avec ses propres commandes :

- des **commandes info**, qui donnent l'état du groupe : binaire (ex. `on` vaut `1` si toutes les lumières sont allumées), numérique (ex. température moyenne des pièces) ou texte (ex. `on` / `off`, valeurs personnalisables) ;
- des **commandes action**, qui pilotent tous les équipements d'un coup (ex. `Allumer` allume les lumières, règle leur luminosité et leur couleur).

Ces commandes s'utilisent comme n'importe quelle commande Jeedom : scénarios, widgets, historique, alertes...

## Fonctionnalités

- Commandes info de type **Binaire**, **Numérique** ou **Autre** (texte), comme dans Jeedom ; le type est proposé automatiquement d'après les commandes choisies
- Règle au choix : tous, au moins un ou au moins N équipements conformes ; conditions variées : égal, différent, supérieur, inférieur, contient...
- Calculs numériques : moyenne, plus haut, plus bas, somme, médiane ou nombre de correspondances, avec arrondi et unité reprise automatiquement
- **Fraîcheur des données** : un équipement sans mise à jour récente (ex. sonde sans pile) ou en timeout est ignoré et signalé par un avertissement
- Un même équipement peut revenir plusieurs fois dans une commande info (ex. température et humidité d'une même sonde)
- Commandes activables / désactivables sans les supprimer
- Commandes action enchaînant plusieurs actions par équipement (ex. On, Luminosité, Couleur), avec une pause optionnelle entre deux actions
- Scénarios dans les groupes : état (actif, en cours...) pour les infos ; lancer, arrêter, activer, désactiver pour les actions
- Widget dédié : valeur et état de chaque commande info, couleur actuelle des lumières, un bouton par commande action, mis à jour en direct
- Éditeur pratique : valeur actuelle de chaque commande visible même pliée, glisser-déposer, clonage des lignes et des commandes
- Interface en français, anglais, allemand, espagnol et italien

![Widget](docs/images/Widget.png)

## Installation

Depuis le Market Jeedom : *Plugins > Gestion des plugins > Market*, rechercher **Groupir**, installer puis activer le plugin. Aucune configuration globale n'est nécessaire.

Prérequis : Jeedom 4.2 ou supérieur.

## Documentation

- [Français](docs/fr_FR/index.md) · [English](docs/en_US/index.md) · [Deutsch](docs/de_DE/index.md) · [Español](docs/es_ES/index.md) · [Italiano](docs/it_IT/index.md)
- [Changelog](docs/fr_FR/changelog.md)

## Branches

- `stable` : version publiée sur le Market
- `beta` : version de test

## Support

Questions et retours sur le [forum Jeedom Community](https://community.jeedom.com/tag/plugin-groupir).

## Licence

[GPL-2.0](LICENSE)
