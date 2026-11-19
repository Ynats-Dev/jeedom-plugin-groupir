---
layout: default
lang: fr_FR
---

# Changelog Groupir

# 19/11/2026 - 1.0.0

- Première version publiée sur le Market Jeedom
- Retrait de la partie « Développement » de la configuration du plugin
- Nettoyage du code
- Interface traduite en anglais, allemand, espagnol et italien
- Documentation : section « Configuration du plugin »

# 19/11/2026 - 0.5

**Commandes du groupe**
- Commande info : règle au choix, « tous », « au moins un » ou « au moins N » équipements qui correspondent
- Commande info : l'état de scénarios peut compter (actif, inactif, en cours, arrêté), recalculé chaque minute
- Commande action : plusieurs actions enchaînées sur un même équipement (ex. On, Luminosité, Couleur)
- Commande action : lancer, arrêter, activer ou désactiver des scénarios
- Commande action : pause optionnelle entre deux actions (0 à 30 secondes)
- Valeur d'un curseur : plage de la commande en indication (ex. 153 à 500) et saisie limitée à cette plage

**Onglet Correspondances**
- Commandes, lignes et actions réordonnables par glisser-déposer
- Commandes pliables, pliées par défaut
- Boutons pour cloner une ligne (commandes retrouvées par leur nom sur le nouvel équipement, valeurs reprises) ou une commande entière
- Icône de configuration de chaque commande : historique, type générique, alertes, affichage
- Lignes dont l'équipement, la commande ou le scénario a été supprimé signalées en rouge
- Enregistrement refusé avec un message précis (commande et ligne) si une ligne est incomplète

**Widget et page**
- Widget dédié : état de chaque commande info (pastille, nombre d'équipements conformes), couleur actuelle des lumières, un bouton par commande action
- Onglet Général : bloc « Scénarios liés » (scénarios qui utilisent le groupe et scénarios pilotés par le groupe)
- Interface relookée dans le style de Scan.Ip et nouveau logo

**Performances**
- Seules les commandes concernées par un changement sont recalculées
- État du groupe mis en cache, widget mis à jour en direct sans être régénéré

**Corrections**
- Les commandes du groupe pouvaient être effacées à l'enregistrement (version de développement)

# 18/11/2026 - 0.4

- Le plugin devient **Groupir** (id `groupir`) et se limite au regroupement d'équipements (anciennement Simple.Scenario)
- Groupes composés de commandes info (valeur « ok / ko », personnalisable) et de commandes action, chacune étant un sous-groupe d'équipements
- Sélection en ligne Objet > Équipement > Commande avec filtres ; un équipement déjà choisi dans une commande n'est plus proposé
- Développement : bouton « Tirer la branche » dans la configuration du plugin
