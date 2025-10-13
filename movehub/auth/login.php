<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide'; }
    if ($password === '') { $errors[] = 'Veuillez saisir votre mot de passe'; }

    if (!$errors) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Identifiants incorrects';
        } elseif ((int)$user['is_active'] !== 1) {
            $errors[] = 'Compte désactivé';
        } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            header('Location: /movehub/index.php');
            exit;
        }
    }
}
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h2 class="mb-3">Connexion</h2>
    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Inscription réussie, veuillez vous connecter.</div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post" novalidate>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-primary" type="submit">Se connecter</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>


