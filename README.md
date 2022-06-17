# MacaronDB

Bienvenue dans le projet MacaronDB.

Ce projet est publié sous double licence MIT et New BSD.

MacaronDB est une petite librarie écrite en PHP fournissant un jeu de classes destinées à faciliter le travail avec les bases de données DB2 pour IBMi, DB2 Express C, et MySQL 5.

J’ai utilisé cette librarie au sein de projets personnels et professionnels.
Vous pouvez trouver un exemple d'implémentation de MacaronDB dans le projet suivant :

https://github.com/gregja/DBTulbox2

MacaronDB est un projet un peu ancien, par rapport aux normes en vigueur actuellement. 
Parmi les points qui pourraient être améliorés :
- intégrer la notion de namespaces, 
- réaliser un composant compatible avec Composer.
- passer l'ensemble du code en UTF-8 (projet développé initialement en ISO-8859
    pour compatibilité avec les environnements IBMi pour lesquels le projet
    avait été développé inialement).

La documentation de ce projet se trouve dans le dépôt suivant :

https://github.com/gregja/phpLibrary4i

Pour compléter votre information sur l'écosystème IBM i, et en particulier sur la base de données DB2 for i (et ses nouveautés), je vous invite à vous reporter à mon autre dépôt Github, dédié à ce sujet :

https://github.com/gregja/SQLMasters
