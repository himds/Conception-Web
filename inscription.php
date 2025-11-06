<?php
  $titre = "Inscription";

  include('header.inc.php');
  include('menu.inc.php');
?>

<div class="index-page-background">
<div class="container-fluid my-4">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="p-4 bg-light border rounded">
        <h1 class="mb-4">Création d'un compte</h1>
        <form method="POST" action="tt_inscription.php">

          <!-- Nom et prénom -->
          <div class="row my-3">
            <div class="col-md-6">
              <label for="nom" class="form-label">Nom</label>
              <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom..." required>
            </div>
            <div class="col-md-6">
              <label for="prenom" class="form-label">Prénom</label>
              <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Votre prénom..." required>
            </div>
          </div>

          <!-- Email et mot de passe -->
          <div class="row my-3">
            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="Votre email..." required>
            </div>
            <div class="col-md-6">
              <label for="password" class="form-label">Mot de passe</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe..." required>
            </div>
          </div>

          <!-- Type de compte -->
          <div class="row my-3">
            <div class="col-12">
              <label for="role" class="form-label">Type de compte</label>
              <select class="form-select" id="role" name="role" required>
                <option value="">Sélectionnez votre type de compte</option>
                <option value="1">Utilisateur (Client)</option>
                <option value="2">Déménageur (Service de déménagement)</option>
              </select>
              <small class="form-text text-muted">Les comptes administrateur ne peuvent être créés que par un administrateur existant.</small>
            </div>
          </div>

          <div class="row my-3">
            <div class="d-grid d-md-block">
              <button class="btn btn-orange" type="submit">Inscription</button>
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