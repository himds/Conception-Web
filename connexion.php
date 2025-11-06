<?php
  $titre = "Connexion";

  include('header.inc.php');
  include('menu.inc.php');
?>

<div class="index-page-background">
<div class="container-fluid my-4">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="p-4 bg-light border rounded">
        <h1 class="mb-4">Connexion à votre compte</h1>
        <form method="POST" action="tt_connexion.php">
          <div class="row my-3">
            <div class="col-12">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="Votre email..." required>
            </div>
          </div>
          <div class="row my-3">
            <div class="col-12">
              <label for="password" class="form-label">Mot de passe</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe..." required>
            </div>
          </div>
          <div class="row my-3">
            <div class="col-12">
              <label for="role" class="form-label">Type de compte</label>
              <select class="form-select" id="role" name="role" required>
                <option value="">Sélectionnez votre type de compte</option>
                <option value="1">Utilisateur (Client)</option>
                <option value="2">Déménageur (Service de déménagement)</option>
                <option value="3">Administrateur</option>
              </select>
            </div>
          </div>
          <div class="row my-3">
            <div class="d-grid d-md-block">
              <button class="btn btn-orange" type="submit">Connexion</button>
            </div>   
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
<?php
  include('footer.inc.php');
?>