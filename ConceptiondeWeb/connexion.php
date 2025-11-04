<?php
  $titre = "Connexion";

  include('header.inc.php');
  include('menu.inc.php');
?>

  <h1>Connexion à votre compte</h1>
  <form  method="POST" action="tt_connexion.php">
    <div class="row my-3">
      <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control " id="email" name="email" placeholder="Votre email..." required>
      </div>
      <div class="col-md-6">
        <label for="password" class="form-label">Mot de passe</label>
        <input type="password" class="form-control " id="password" name="password" placeholder="Votre mot de passe..." required>
      </div>
    </div>
    <div class="row my-3">
      <div class="d-grid d-md-block">
      <button class="btn btn-orange" type="submit">Connexion</button></div>   
    </div>

  </form>
<?php
  include('footer.inc.php');
?>