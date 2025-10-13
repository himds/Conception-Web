<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'client';
    if (!in_array($role, ['client','mover'], true)) { $role = 'client'; }

    if ($name === '') { $errors[] = 'Veuillez saisir votre nom'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide'; }
    if (strlen($password) < 6) { $errors[] = 'Mot de passe d\'au moins 6 caractères'; }

    if (!$errors) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email est déjà utilisé';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
            $stmt->execute([$name, $email, $hash, $role]);
            header('Location: /movehub/auth/login.php?registered=1');
            exit;
        }
    }
}
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h2 class="mb-3">Inscription</h2>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post" novalidate>
      <div class="mb-3">
        <label class="form-label">Nom</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Rôle</label>
        <select name="role" class="form-select">
          <option value="client" <?php echo (($_POST['role'] ?? '')==='client')?'selected':''; ?>>Client</option>
          <option value="mover" <?php echo (($_POST['role'] ?? '')==='mover')?'selected':''; ?>>Déménageur</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Créer le compte</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


