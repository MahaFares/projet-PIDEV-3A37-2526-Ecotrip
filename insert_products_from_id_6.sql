-- Products to insert into `produits` (starting from id_produit 6)
-- Table: produits (id_produit, nom, prix, stock, image, id_categorie)
-- Categories from your DB: 6,7 = A louer | 8 = A vendre

INSERT INTO `produits` (`id_produit`, `nom`, `prix`, `stock`, `image`, `id_categorie`) VALUES
(6, 'Tente 2 places éco', 45.00, 12, NULL, 6),
(7, 'Sac de couchage été', 35.00, 20, NULL, 8),
(8, 'Vélo de randonnée jour', 25.00, 8, NULL, 6),
(9, 'Lampe frontale rechargeable', 18.50, 30, NULL, 8),
(10, 'Gourde inox isotherme 1L', 22.00, 50, NULL, 8),
(11, 'Kayak 1 place demi-journée', 60.00, 4, NULL, 6),
(12, 'Sac à dos randonnée 40L', 55.00, 15, NULL, 8),
(13, 'Matelas gonflable 2 places', 28.00, 10, NULL, 6),
(14, 'Réchaud camping gaz', 15.00, 18, NULL, 8),
(15, 'Carte randonnée Tunisie Nord', 12.00, 25, NULL, 8),
(16, 'Jumelles compactes', 42.00, 9, NULL, 8),
(17, 'Panneau solaire pliant 10W', 65.00, 6, NULL, 8),
(18, 'Tente 4 places week-end', 95.00, 5, NULL, 6),
(19, 'Crème solaire bio 50ml', 14.00, 40, NULL, 8),
(20, 'Carte prépayée Djerba', 8.00, 100, NULL, 8);
