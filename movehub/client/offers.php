<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: /movehub/auth/login.php');
    exit;
}
$pdo = get_pdo();

$annonceId = isset($_GET['annonce_id']) ? (int)$_GET['annonce_id'] : 0;
$stmt = $pdo->prepare('SELECT id, client_id, title FROM annonces WHERE id=?');
$stmt->execute([$annonceId]);
$ann = $stmt->fetch();
if (!$ann || (int)$ann['client_id'] !== (int)$_SESSION['user']['id']) {
    echo '<div class="alert alert-warning">Accès refusé ou annonce introuvable</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

if (isset($_GET['accept']) && ctype_digit($_GET['accept'])) {
    $offerId = (int)$_GET['accept'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE offers SET status="rejected" WHERE annonce_id=? AND status<>"accepted"')->execute([$annonceId]);
        $pdo->prepare('UPDATE offers SET status="accepted" WHERE id=? AND annonce_id=?')->execute([$offerId, $annonceId]);
        $pdo->commit();
        header('Location: /movehub/client/offers.php?annonce_id=' . $annonceId);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Échec de l\'opération</div>';
    }
}

$offers = $pdo->prepare('SELECT o.*, u.name AS mover_name FROM offers o JOIN users u ON u.id=o.mover_id WHERE o.annonce_id=? ORDER BY o.created_at DESC');
$offers->execute([$annonceId]);
$rows = $offers->fetchAll();
?>
<h3 class="mb-3">Offres pour « <?php echo htmlspecialchars($ann['title']); ?> »</h3>
<div class="list-group">
  <?php foreach ($rows as $o): ?>
    <div class="list-group-item d-flex justify-content-between align-items-start">
      <div>
        <div class="fw-semibold"><?php echo htmlspecialchars($o['mover_name']); ?></div>
        <div class="small text-muted"><?php echo htmlspecialchars($o['message'] ?? ''); ?></div>
      </div>
      <div class="text-end">
        <div class="fw-bold">$<?php echo number_format($o['price_cents']/100, 2); ?></div>
        <div class="mb-2"><span class="badge bg-secondary"><?php echo htmlspecialchars($o['status']); ?></span></div>
        <?php if ($o['status'] !== 'accepted'): ?>
          <a class="btn btn-sm btn-primary" href="/movehub/client/offers.php?annonce_id=<?php echo (int)$annonceId; ?>&accept=<?php echo (int)$o['id']; ?>">Accepter cette offre</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


