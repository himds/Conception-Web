<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';
$pdo = get_pdo();

$stmt = $pdo->query("SELECT a.id, a.title, a.city_from, a.city_to, a.start_datetime, u.name AS client_name FROM annonces a JOIN users u ON u.id=a.client_id ORDER BY a.created_at DESC LIMIT 50");
$rows = $stmt->fetchAll();
?>
<h2 class="mb-3">Annonces de déménagement</h2>
<div class="row row-cols-1 row-cols-md-2 g-3">
<?php foreach ($rows as $r): ?>
  <div class="col">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-1"><?php echo htmlspecialchars($r['title']); ?></h5>
        <div class="small text-muted mb-2">Par <?php echo htmlspecialchars($r['client_name']); ?></div>
        <div class="mb-1">Villes: <?php echo htmlspecialchars($r['city_from'] . ' → ' . $r['city_to']); ?></div>
        <div class="mb-2">Début: <?php echo htmlspecialchars($r['start_datetime']); ?></div>
        <a class="btn btn-sm btn-outline-primary" href="/movehub/annonces/show.php?id=<?php echo (int)$r['id']; ?>">Voir le détail</a>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


