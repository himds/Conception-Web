<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'mover') {
    header('Location: /movehub/auth/login.php');
    exit;
}
$pdo = get_pdo();

$annonceId = isset($_GET['annonce_id']) ? (int)$_GET['annonce_id'] : 0;
$stmt = $pdo->prepare('SELECT id, title FROM annonces WHERE id=?');
$stmt->execute([$annonceId]);
$ann = $stmt->fetch();
if (!$ann) {
    echo '<div class="alert alert-warning">Annonce introuvable</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = (int)($_POST['price_cents'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if ($price <= 0) $errors[] = 'Veuillez saisir un prix valide (centimes)';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO offers (annonce_id, mover_id, price_cents, message) VALUES (?,?,?,?)');
            $stmt->execute([$annonceId, $_SESSION['user']['id'], $price, $message]);
            header('Location: /movehub/annonces/show.php?id=' . $annonceId);
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Échec de l\'envoi (offre peut-être déjà soumise)';
        }
    }
}
?>
<h3 class="mb-3">Proposer un prix pour « <?php echo htmlspecialchars($ann['title']); ?> »</h3>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Prix (centimes, 100 = 1 €)</label>
    <input type="number" name="price_cents" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Message</label>
    <textarea name="message" class="form-control" rows="3"></textarea>
  </div>
  <button class="btn btn-primary" type="submit">Envoyer l'offre</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>


