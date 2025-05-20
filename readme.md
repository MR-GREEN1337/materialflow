# Présentation de MaterialFlow

## Introduction

Bonjour à tous,

Aujourd'hui, nous avons le plaisir de vous présenter MaterialFlow, une application web développée par notre équipe pour répondre au besoin de suivi du matériel utilisé dans les projets étudiants.

Je m'appelle [Votre Nom], et je suis accompagné de mes collègues : Amine Abidi, Islam Hachimi, Houssam Eddine Syouti et Anas Draoui. Ensemble, nous avons conçu et développé cette solution complète de gestion de matériel.

## Contexte et Problématique

Chaque année, l'école investit dans du matériel pour les projets étudiants. Si le matériel onéreux est bien géré par les services techniques, le suivi du petit matériel reste problématique :
- Difficulté à connaître l'état des équipements
- Incertitude sur les lieux de stockage
- Manque de visibilité sur l'utilisation dans les différents projets
- Absence d'historique des projets et de leurs ressources

Notre mission était de créer une application web permettant de suivre l'ensemble du cycle de vie du matériel, depuis son acquisition jusqu'à sa fin de vie, tout en conservant une trace des projets réalisés.

## Démonstration de MaterialFlow

### Page d'accueil et Authentification

Comme vous pouvez le voir, notre application propose une interface élégante et intuitive. La page d'accueil présente clairement l'objectif de l'application avec une section présentant notre équipe.

Le système d'authentification par identifiant étudiant permet de sécuriser l'accès tout en maintenant une simplicité d'utilisation pour les étudiants.

### Tableau de bord

Une fois connecté, l'utilisateur accède au tableau de bord qui offre une vue d'ensemble du système :
- Statistiques sur le matériel (total, disponible, en utilisation)
- Nombre de projets
- Activités récentes
- Accès rapide aux fonctionnalités principales

Les activités récentes permettent de suivre en temps réel les mouvements de matériel, les créations de projets, et les opérations utilisateurs.

### Gestion du Matériel

La section Équipement est au cœur de notre application :

1. **Liste des équipements** : Affiche tous les éléments avec leur statut, lieu de stockage et options d'action
2. **Détails d'un équipement** : Présente toutes les informations importantes :
   - Caractéristiques techniques
   - Date d'achat et prix
   - Documentation et liens vers les ressources constructeur
   - Images de l'équipement
   - Historique d'utilisation dans les projets

3. **Ajout de matériel** : Interface complète pour enregistrer un nouvel équipement avec toutes ses spécifications

4. **Système d'emprunt** : Processus simplifié pour associer un équipement à un projet

### Gestion des Projets

Pour les projets, notre application offre :

1. **Liste des projets** : Vue d'ensemble avec filtres par statut
2. **Détails d'un projet** : Informations complètes sur le projet :
   - Dates de début et fin
   - Description et objectifs
   - Liste des étudiants impliqués
   - Équipements utilisés
   - Ressources associées (rapports, présentations, code)

3. **Ressources des projets** : Possibilité de télécharger ou consulter les ressources associées

### Administration et Gestion des Utilisateurs

Pour les administrateurs, des fonctionnalités supplémentaires sont disponibles :
- Gestion des utilisateurs
- Ajout et suppression d'équipements
- Validation des retours de matériel

## Architecture Technique

Notre application repose sur une architecture robuste :
- Base de données relationnelle MySQL
- Backend en PHP structuré par fonctionnalités
- Frontend en HTML/CSS avec JavaScript pour l'interactivité
- Design responsive adapté à tous les appareils

Nous avons mis l'accent sur :
- La sécurité des données
- La performance
- L'extensibilité
- L'éco-conception (optimisation des images, réduction des requêtes)

## Réponse au Cahier des Charges

Notre solution répond point par point aux exigences formulées :
1. **Suivi complet du matériel** : de l'achat à la fin de vie
2. **Mémorisation des projets** : conservation des informations et ressources
3. **Gestion des utilisations successives** du matériel
4. **Stockage des documentations techniques** pour garantir leur pérennité
5. **Interface simple et intuitive** permettant une adoption facile

## Conclusion et Perspectives

MaterialFlow est une application opérationnelle qui répond aux besoins immédiats de l'école. Pour l'avenir, plusieurs évolutions sont envisageables :
- Intégration d'un système de réservation anticipée
- Ajout d'un module de génération de rapports statistiques
- Développement d'une API pour intégration avec d'autres systèmes
- Implémentation de notifications par email

Nous sommes convaincus que cette application apportera une réelle valeur ajoutée dans la gestion du matériel pour les projets étudiants, permettant une meilleure traçabilité et une optimisation des ressources.

Merci de votre attention. Nous sommes maintenant disponibles pour répondre à vos questions.