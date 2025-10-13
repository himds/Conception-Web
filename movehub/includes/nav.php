<?php
declare(strict_types=1);
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container">
    <a class="navbar-brand" href="/movehub/index.php">MoveHub</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/movehub/annonces/index.php">Annonces</a></li>
        <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'client'): ?>
          <li class="nav-item"><a class="nav-link" href="/movehub/annonces/create.php">Publier</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if (empty($_SESSION['user'])): ?>
          <li class="nav-item"><a class="nav-link" href="/movehub/auth/login.php">Se connecter</a></li>
          <li class="nav-item"><a class="nav-link" href="/movehub/auth/register.php">S'inscrire</a></li>
        <?php else: ?>
          <li class="nav-item"><span class="navbar-text me-2">Bonjour, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?></span></li>
          <li class="nav-item"><a class="nav-link" href="/movehub/auth/logout.php">Se déconnecter</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  </nav>

