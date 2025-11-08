<?php
// 侧边栏 - 仅对客户端用户显示
if(isset($_SESSION['user']) && (int)$_SESSION['user']['role'] === 1) {
?>
<div class="client-sidebar">
  <div class="sidebar-header">
    <h5>Mes services</h5>
  </div>
  <ul class="sidebar-menu">
    <li class="sidebar-item">
      <a href="annonce_nouvelle.php" class="sidebar-link">
        <span class="sidebar-icon">📝</span>
        <span>Créer une annonce</span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="mes_annonces.php" class="sidebar-link">
        <span class="sidebar-icon">📋</span>
        <span>Mes annonces</span>
      </a>
    </li>
    <li class="sidebar-item">
      <a href="annonces.php" class="sidebar-link">
        <span class="sidebar-icon">🔍</span>
        <span>Les annonces</span>
      </a>
    </li>
  </ul>
</div>
<?php
}
?>

