<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';
$pdo = get_pdo();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT a.*, u.name AS client_name FROM annonces a JOIN users u ON u.id=a.client_id WHERE a.id=? LIMIT 1');
$stmt->execute([$id]);
$annonce = $stmt->fetch();
if (!$annonce) {
    echo '<div class="alert alert-warning">找不到該需求</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$imgs = $pdo->prepare('SELECT file_path FROM annonce_images WHERE annonce_id=?');
$imgs->execute([$id]);
$images = $imgs->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-8">
    <h2 class="mb-2"><?php echo htmlspecialchars($annonce['title']); ?></h2>
    <div class="text-muted mb-3">Par <?php echo htmlspecialchars($annonce['client_name']); ?></div>
    <p><?php echo nl2br(htmlspecialchars($annonce['description'] ?? '')); ?></p>

    <div class="row g-2">
      <?php foreach ($images as $img): ?>
        <div class="col-6 col-md-4"><img class="img-fluid rounded" src="<?php echo htmlspecialchars($img['file_path']); ?>" alt="image"></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <div><strong>Villes: </strong><?php echo htmlspecialchars($annonce['city_from'] . ' → ' . $annonce['city_to']); ?></div>
        <div><strong>Début: </strong><?php echo htmlspecialchars($annonce['start_datetime']); ?></div>
        <hr>
        <div><strong>Au départ: </strong><?php echo htmlspecialchars($annonce['from_type']); ?>, étage <?php echo htmlspecialchars((string)($annonce['from_floor'] ?? '-')); ?>, ascenseur <?php echo ((int)$annonce['from_elevator']===1?'oui':'non'); ?></div>
        <div><strong>À l'arrivée: </strong><?php echo htmlspecialchars($annonce['to_type']); ?>, étage <?php echo htmlspecialchars((string)($annonce['to_floor'] ?? '-')); ?>, ascenseur <?php echo ((int)$annonce['to_elevator']===1?'oui':'non'); ?></div>
        <div><strong>Volume (m³): </strong><?php echo htmlspecialchars((string)($annonce['total_volume_m3'] ?? '-')); ?></div>
        <div><strong>Poids (kg): </strong><?php echo htmlspecialchars((string)($annonce['total_weight_kg'] ?? '-')); ?></div>
        <div><strong>Nombre de déménageurs: </strong><?php echo htmlspecialchars((string)$annonce['movers_needed']); ?></div>
      </div>
    </div>
    <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role']==='mover'): ?>
      <a class="btn btn-success w-100 mt-3" href="/movehub/offers/create.php?annonce_id=<?php echo (int)$annonce['id']; ?>">Proposer un prix</a>
    <?php endif; ?>

    <?php
      // list offers for client visibility
      $offers = $pdo->prepare('SELECT o.*, u.name AS mover_name FROM offers o JOIN users u ON u.id=o.mover_id WHERE o.annonce_id=? ORDER BY o.created_at DESC');
      $offers->execute([$annonce['id']]);
      $offersRows = $offers->fetchAll();
    ?>
    <div class="mt-4 d-flex justify-content-between align-items-center">
      <h5 class="mb-2">Offres</h5>
      <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role']==='client' && (int)$_SESSION['user']['id']===(int)$annonce['client_id']): ?>
        <a class="btn btn-sm btn-outline-secondary" href="/movehub/client/offers.php?annonce_id=<?php echo (int)$annonce['id']; ?>">Gérer les offres</a>
      <?php endif; ?>
    </div>
      <?php if (!$offersRows): ?>
        <div class="text-muted">Aucune offre</div>
      <?php else: ?>
        <ul class="list-group">
          <?php foreach ($offersRows as $o): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold"><?php echo htmlspecialchars($o['mover_name']); ?></div>
                <div class="small text-muted"><?php echo htmlspecialchars($o['message'] ?? ''); ?></div>
              </div>
              <div class="text-end">
                <div class="fw-bold">$<?php echo number_format($o['price_cents']/100, 2); ?></div>
                <div class="badge bg-secondary"><?php echo htmlspecialchars($o['status']); ?></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


