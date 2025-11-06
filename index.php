<?php
session_start();
$titre = "Accueil";
include('header.inc.php');
include('menu.inc.php');
include('message.inc.php');
?>

<!-- 进入动画层 - 只在第一次访问时显示 -->
<div class="intro-overlay" id="introOverlay" style="display: none;">
  <div class="intro-content">
    <h1 class="intro-title">DéménageFacile</h1>
  </div>
</div>

<div class="index-page-background" id="mainContent">
<div class="container-fluid my-4">

  <div class="row g-4">

    <!-- 🧊 Block 1 -->
    <div class="col-12 col-md-6">
      <div class="p-3 bg-light border rounded h-100">
        <h1 class="mb-3">Plateforme de déménagement</h1>
        <p class="lead mb-4">Trouvez des déménageurs ou proposez vos services.</p>
        
        <div class="media-container d-flex gap-3">
          <div class="video-container">
            <video width="100%" controls>
              <source src="video/20251106_1250_01k9bqf0hxftf9t260qj35kn05.mp4" type="video/mp4">
              Votre navigateur ne supporte pas la vidéo.
            </video>
          </div>
        </div>
      </div>
    </div>

    <!-- 🧊 Block 2 -->
    <div class="col-12 col-md-6">
      <div class="p-3 bg-light border rounded h-100">
        <h2 class="h4 mb-3">Recherche par villes</h2>
        <p class="text-muted">Fonctionnalité de recherche temporairement désactivée.</p>
      </div>
    </div>

    <!-- 🧊 Block 3 (Full width bottom) -->
    <div class="col-12">
      <div class="p-3 bg-light border rounded">

        <!-- 轮播图部分 -->
        <div class="carousel-title-container">
          <h2 class="h4 mb-4">Véhicules de déménagement</h2>
        </div>
        <div class="carousel-container mb-4">
          <button class="carousel-btn carousel-btn-prev" onclick="changeSlide(-1)">
            <span class="carousel-arrow">◀</span>
          </button>
          <div class="carousel-wrapper">
            <div class="carousel-slide active">
              <img src="images/car.png" alt="Voiture" class="carousel-image">
            </div>
            <div class="carousel-slide">
              <img src="images/truck.png" alt="Camion" class="carousel-image">
            </div>
            <div class="carousel-slide">
              <img src="images/E-bike.png" alt="Vélo électrique" class="carousel-image">
            </div>
          </div>
          <button class="carousel-btn carousel-btn-next" onclick="changeSlide(1)">
            <span class="carousel-arrow">▶</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<script>
// 进入动画 - 只在第一次访问时显示
document.addEventListener('DOMContentLoaded', function() {
  const overlay = document.getElementById('introOverlay');
  const hasSeenIntro = localStorage.getItem('hasSeenIntro');
  
  if (!hasSeenIntro && overlay) {
    // 第一次访问，显示动画
    overlay.style.display = 'flex';
    
    // 标记已经看过动画
    localStorage.setItem('hasSeenIntro', 'true');
    
    // 3秒后完全移除overlay元素
    setTimeout(() => {
      overlay.style.display = 'none';
    }, 3000);
  } else {
    // 已经看过动画，直接隐藏并显示主内容
    if (overlay) {
      overlay.style.display = 'none';
    }
    // 立即显示主内容（不等待动画）
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
      mainContent.style.opacity = '1';
    }
  }
});

// 轮播图功能
(function() {
  let currentSlide = 0;
  let slides = [];
  let totalSlides = 0;
  
  function initCarousel() {
    slides = document.querySelectorAll('.carousel-slide');
    totalSlides = slides.length;
    if (totalSlides > 0) {
      showSlide(0);
    }
  }
  
  function showSlide(index) {
    if (slides.length === 0) return;
    
    // 移除所有active类
    slides.forEach(slide => slide.classList.remove('active'));
    
    // 确保索引在有效范围内（循环）
    if (index < 0) {
      currentSlide = totalSlides - 1;
    } else if (index >= totalSlides) {
      currentSlide = 0;
    } else {
      currentSlide = index;
    }
    
    // 添加active类到当前幻灯片
    if (slides[currentSlide]) {
      slides[currentSlide].classList.add('active');
    }
  }
  
  // 将函数暴露到全局作用域
  window.changeSlide = function(direction) {
    showSlide(currentSlide + direction);
  };
  
  // 初始化
  document.addEventListener('DOMContentLoaded', function() {
    initCarousel();
  });
  
  // 如果DOM已经加载完成，立即初始化
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarousel);
  } else {
    initCarousel();
  }
})();
</script>

<?php include('footer.inc.php'); ?>

