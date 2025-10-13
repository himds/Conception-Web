<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: /movehub/auth/login.php');
    exit;
}

$pdo = get_pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_datetime = trim($_POST['start_datetime'] ?? '');
    $city_from = trim($_POST['city_from'] ?? '');
    $city_to = trim($_POST['city_to'] ?? '');
    $from_type = $_POST['from_type'] ?? 'apartment';
    $from_floor = $_POST['from_floor'] !== '' ? (int)$_POST['from_floor'] : null;
    $from_elevator = isset($_POST['from_elevator']) ? 1 : 0;
    $to_type = $_POST['to_type'] ?? 'apartment';
    $to_floor = $_POST['to_floor'] !== '' ? (int)$_POST['to_floor'] : null;
    $to_elevator = isset($_POST['to_elevator']) ? 1 : 0;
    $total_volume_m3 = $_POST['total_volume_m3'] !== '' ? (float)$_POST['total_volume_m3'] : null;
    $total_weight_kg = $_POST['total_weight_kg'] !== '' ? (int)$_POST['total_weight_kg'] : null;
    $movers_needed = (int)($_POST['movers_needed'] ?? 1);

    if ($title === '') $errors[] = 'Veuillez saisir un titre';
    if ($start_datetime === '') $errors[] = 'Veuillez choisir la date et l\'heure';
    if ($city_from === '' || $city_to === '') $errors[] = 'Veuillez renseigner les villes de départ et d\'arrivée';
    if (!in_array($from_type, ['house','apartment'], true)) $from_type = 'apartment';
    if (!in_array($to_type, ['house','apartment'], true)) $to_type = 'apartment';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO annonces (client_id, title, description, start_datetime, city_from, city_to, from_type, from_floor, from_elevator, to_type, to_floor, to_elevator, total_volume_m3, total_weight_kg, movers_needed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $_SESSION['user']['id'], $title, $description, $start_datetime, $city_from, $city_to,
                $from_type, $from_floor, $from_elevator, $to_type, $to_floor, $to_elevator,
                $total_volume_m3, $total_weight_kg, $movers_needed
            ]);
            $annonceId = (int)$pdo->lastInsertId();

            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                foreach ($_FILES['images']['name'] as $idx => $name) {
                    if ($_FILES['images']['error'][$idx] === UPLOAD_ERR_OK) {
                        $tmp = $_FILES['images']['tmp_name'][$idx];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $safeName = 'ann_' . $annonceId . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                        $dest = $uploadDir . '/' . $safeName;
                        if (move_uploaded_file($tmp, $dest)) {
                            $relPath = '/movehub/uploads/' . $safeName;
                            $pdo->prepare('INSERT INTO annonce_images (annonce_id, file_path) VALUES (?,?)')->execute([$annonceId, $relPath]);
                        }
                    }
                }
            }

            $pdo->commit();
            header('Location: /movehub/annonces/show.php?id=' . $annonceId);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Échec de l\'enregistrement, veuillez réessayer plus tard';
        }
    }
}
?>
<h2 class="mb-3">Publier une annonce de déménagement</h2>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Titre</label>
      <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Date et heure de début</label>
      <input type="datetime-local" name="start_datetime" class="form-control" required value="<?php echo htmlspecialchars($_POST['start_datetime'] ?? ''); ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Ville de départ</label>
      <input type="text" name="city_from" class="form-control" required value="<?php echo htmlspecialchars($_POST['city_from'] ?? ''); ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Ville d'arrivée</label>
      <input type="text" name="city_to" class="form-control" required value="<?php echo htmlspecialchars($_POST['city_to'] ?? ''); ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Type au départ</label>
      <select name="from_type" class="form-select">
        <option value="apartment" <?php echo (($_POST['from_type'] ?? '')==='apartment')?'selected':''; ?>>Appartement</option>
        <option value="house" <?php echo (($_POST['from_type'] ?? '')==='house')?'selected':''; ?>>Maison</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Étage (départ)</label>
      <input type="number" name="from_floor" class="form-control" value="<?php echo htmlspecialchars($_POST['from_floor'] ?? ''); ?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="from_elevator" id="fromElevator" <?php echo isset($_POST['from_elevator'])?'checked':''; ?>>
        <label class="form-check-label" for="fromElevator">Ascenseur</label>
      </div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Type à l'arrivée</label>
      <select name="to_type" class="form-select">
        <option value="apartment" <?php echo (($_POST['to_type'] ?? '')==='apartment')?'selected':''; ?>>Appartement</option>
        <option value="house" <?php echo (($_POST['to_type'] ?? '')==='house')?'selected':''; ?>>Maison</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Étage (arrivée)</label>
      <input type="number" name="to_floor" class="form-control" value="<?php echo htmlspecialchars($_POST['to_floor'] ?? ''); ?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="to_elevator" id="toElevator" <?php echo isset($_POST['to_elevator'])?'checked':''; ?>>
        <label class="form-check-label" for="toElevator">Ascenseur</label>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Volume total (m³)</label>
      <input type="number" step="0.01" name="total_volume_m3" class="form-control" value="<?php echo htmlspecialchars($_POST['total_volume_m3'] ?? ''); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Poids total (kg)</label>
      <input type="number" name="total_weight_kg" class="form-control" value="<?php echo htmlspecialchars($_POST['total_weight_kg'] ?? ''); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Nombre de déménageurs</label>
      <input type="number" min="1" name="movers_needed" class="form-control" value="<?php echo htmlspecialchars($_POST['movers_needed'] ?? '1'); ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Images (multiple)</label>
      <input type="file" name="images[]" class="form-control" multiple accept="image/*">
    </div>
    <div class="col-12">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary" type="submit">Publier</button>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>


