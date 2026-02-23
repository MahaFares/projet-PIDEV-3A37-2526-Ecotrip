# Reservation entity – brainstorm (one entity, flexible per service)

## Problème actuel

- **Une seule entité** `Reservation` avec `reservationType` + `reservationId` + `totalPrice`.
- Aucun détail **spécifique au type** : pas de dates pour l’hébergement, pas de “from/to” pour le transport, pas de date/heure pour l’activité.
- Donc une résa hébergement = une résa activité = une résa transport au niveau données → **pas logique métier** (on ne sait pas “pour quand”, “combien de personnes”, “d’où vers où”, etc.).

Objectif : **garder une seule entité Reservation**, mais la rendre **flexible** avec des infos différentes selon le type.

---

## Ce qu’on veut par type (idées)

| Type | Infos typiques à stocker |
|------|---------------------------|
| **Hébergement** | Date arrivée (check-in), date départ (check-out), nombre de nuits, nombre de personnes, optionnel : id chambre choisie |
| **Activité** | Date/heure de la résa (créneau), nombre de participants |
| **Transport** | Lieu départ, lieu arrivée, date du trajet, nombre de personnes (ou lien vers un `Trajet` existant si tu réserves un trajet précis) |

Comme ça, une résa = **une résa par service**, avec le **contexte métier** (quand, combien, d’où vers où).

---

## Options de design (une seule table `reservation`)

### Option 1 : Colonnes nullable par type

- Ajouter des colonnes : `checkInAt`, `checkOutAt`, `numberOfGuests` (hébergement), `reservedAt` (activité), `numberOfParticipants`, `departLocation`, `arriveeLocation`, `transportDate`, `numberOfPassengers` (transport).
- Pour une ligne donnée, seules les colonnes du type concerné sont remplies.

**+** Simple, requêtable en SQL (ex: “toutes les résas hébergement sur cette période”).  
**−** Beaucoup de colonnes nullable, un peu “sale” et moins flexible si tu ajoutes d’autres champs plus tard.

---

### Option 2 : Une colonne JSON `details` (recommandée)

- Garder : `user`, `reservationType`, `reservationId`, `totalPrice`, `status`, etc.
- Ajouter **une seule colonne** `details` (type JSON).

Exemples de contenu selon le type :

- **HEBERGEMENT**  
  `{ "checkIn": "2025-03-01", "checkOut": "2025-03-05", "nights": 4, "guests": 3, "chambreId": 12 }`
- **ACTIVITY**  
  `{ "reservedAt": "2025-03-10T14:00:00", "participants": 2 }`
- **TRANSPORT**  
  `{ "depart": "Tunis", "arrivee": "Sousse", "travelDate": "2025-03-08", "passengers": 2 }`  
  ou `{ "trajetId": 5, "passengers": 2 }` si tu réserves un trajet existant.

**+** Une seule entité, très flexible, pas de nouvelles colonnes à chaque nouveau champ.  
**−** Filtrage par détail (ex. “résas avec check-in en mars”) moins direct en SQL (possible en JSON selon SGBD), validation à faire en PHP.

Tu peux exposer des getters/setters “typés” côté entity, ex. `getDetailsForHebergement(): ?array`, `setDetailsForHebergement(array $d): self`, idem pour activity/transport, et valider dans un listener ou un DTO selon le `reservationType`.

---

### Option 3 : Hybride (JSON + quelques colonnes “index”)

- Comme option 2, mais en plus 2–3 colonnes pour les requêtes les plus courantes, ex. `dateFrom`, `dateTo` (remplies pour hébergement et éventuellement transport/activité).
- Le “vrai” détail reste dans `details` (JSON).

**+** Flexibilité + possibilité de faire des requêtes par période sans parser le JSON.  
**−** Un peu de redondance (dateFrom/dateTo dérivables du JSON).

---

## Recommandation

- **Rester sur une seule entité `Reservation`.**
- **Adopter l’option 2 (colonne JSON `details`)** pour tout ce qui est spécifique au type (check-in/out, nuits, personnes, départ/arrivée, date activité, etc.).
- Optionnel : si plus tard tu as besoin de requêtes “par période” très fréquentes, ajouter **option 3** avec `dateFrom` / `dateTo` (ou une seule `reservedAt`) remplis à la création selon le type.

Résumé : **une résa = un type + un `reservationId` (le service) + un blob structuré `details` qui change selon le type**. C’est flexible et ça reste une seule table, un seul modèle.

---

## Impact côté code

1. **Entity**  
   - Ajouter `details` (JSON).  
   - Optionnel : helpers `getDetailsForHebergement()`, `setDetailsForHebergement()`, etc., pour un accès typé.

2. **Cart / Panier**  
   - Enrichir le panier pour stocker ces infos (dates, nuits, personnes, from/to, etc.) quand l’utilisateur choisit “Réserver” (formulaires ou modals par type).

3. **Panier → Reservation**  
   - Dans `finalizePayment()`, créer chaque `Reservation` en remplissant `details` à partir du panier (selon `reservationType`).

4. **Front**  
   - Hébergement : formulaire ou modal (dates, nb personnes) avant “Reserve Now” → envoyer ces champs au add-to-cart.  
   - Activité : choix de date/créneau + nb participants.  
   - Transport : choix départ / arrivée / date (ou sélection d’un trajet) + nb passagers.

Si tu veux, on peut détailler la structure exacte de `details` par type et les changements dans `CartService` + `PanierController` étape par étape.
