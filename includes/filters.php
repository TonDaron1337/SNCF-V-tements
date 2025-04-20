<?php
function getFilterParams() {
    return [
        'status' => isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '',
        'date' => isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '',
        'name' => isset($_GET['name']) ? htmlspecialchars(trim($_GET['name'])) : '',
        'commande' => isset($_GET['commande']) ? (int)$_GET['commande'] : ''
    ];
}

function buildFilterQuery($filters, &$params) {
    $query = "SELECT DISTINCT c.*, u.nom, u.prenom, p.nom as produit_nom, p.categorie, p.taille, cd.quantite
              FROM commandes c
              JOIN utilisateurs u ON c.utilisateur_id = u.id
              JOIN commande_details cd ON c.id = cd.commande_id
              JOIN produits p ON cd.produit_id = p.id
              WHERE c.statut IN ('acceptee', 'refusee')";

    if ($filters['status'] && in_array($filters['status'], ['acceptee', 'refusee'])) {
        $query .= " AND c.statut = :status";
        $params[':status'] = $filters['status'];
    }

    if ($filters['date'] && strtotime($filters['date'])) {
        $query .= " AND DATE(c.date_commande) = :date";
        $params[':date'] = $filters['date'];
    }

    if ($filters['name']) {
        $searchTerms = explode(' ', trim($filters['name']));
        $conditions = [];
        foreach ($searchTerms as $index => $term) {
            $paramName = ":name{$index}";
            $conditions[] = "(LOWER(u.nom) LIKE LOWER({$paramName}) OR LOWER(u.prenom) LIKE LOWER({$paramName}))";
            $params[$paramName] = "%{$term}%";
        }
        if (!empty($conditions)) {
            $query .= " AND (" . implode(' AND ', $conditions) . ")";
        }
    }

    if ($filters['commande'] > 0) {
        $query .= " AND c.id = :commande";
        $params[':commande'] = $filters['commande'];
    }

    $query .= " ORDER BY c.date_commande DESC";
    
    return $query;
}