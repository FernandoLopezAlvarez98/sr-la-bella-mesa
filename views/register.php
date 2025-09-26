<?php
require_once '../controllers/AuthController.php';

$authController = new AuthController();
$error = '';
$success = '';

if ($_POST) {
    $result = $authController->register(
        $_POST['nombre'], 
        $_POST['correo'], 
        $_POST['telefono'], 
        $_POST['password'], 
        $_POST['confirm_password']
    );
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>La Bella Mesa | Restaurante y Reservaciones Online</title>
  <!-- CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="style_animation.css">
  
</head>
<body>
  <!-- Indicador de carga -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
  </div>

  <!-- Hero Section -->
  <div class="hero">
    <video autoplay muted loop>
      <source src="videos/fondo.mp4" type="video/mp4">
      Tu navegador no soporta videos HTML5.
    </video>
    <div class="overlay"></div>
    
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container-fluid">
        <a href="index.html" class="navbar-brand logo">
          <i class="fas fa-utensils"></i> La Bella Mesa
        </a>

        <!-- Botón hamburguesa -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Links + Buscador dentro del collapse -->
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link active" href="pantalla principal.html"><i class="fas fa-home"></i> Inicio</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="REGISTRAR/index.html"><i class="fas fa-calendar-alt"></i> Reservaciones</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="Galery.html"><i class="fas fa-images"></i> Galería</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#about"><i class="fas fa-info-circle"></i> Acerca de La Bella Mesa</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="inicio sesion.html"><i class="fas fa-sign-in-alt"></i> Acceso</a>
            </li>
          </ul>

          <!-- Buscador a la derecha -->
          <form class="d-flex ms-lg-3 mt-2 mt-lg-0" role="search" style="position: relative;">
            <label for="buscador" class="visually-hidden">Buscar restaurante</label>
            <input id="buscador"
                   class="form-control me-2 flex-grow-1"
                   type="search"
                   placeholder="Buscar por ubicación (ej. ciudad, barrio)"
                   aria-label="Buscar"
                   style="max-width: 300px;"
                   autocomplete="off">
            <div class="search-suggestions" id="searchSuggestions"></div>
            <button class="btn btn-primary" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </form>
        </div>
      </div>
    </nav>
    
    <!-- Main Content -->
    <div class="content text-center text-white">
     <!-- Contenedor Principal -->
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="bg-dark text-white p-5 rounded shadow-lg" style="max-width: 450px; width: 100%; ;">
    <div class="card-body text-center text-white">
      <h2 class="card-title mb-3">Crear Cuenta</h2>
      <p class="card-text mb-4">Regístrate para acceder al sistema de reservas.</p>

      <!-- Panel de registro -->
      <?php if ($error): ?>
          <div class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
          </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
          <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
              <br><br>
              <a href="login.php" class="btn btn-success">
                  <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión Ahora
              </a>
          </div>
      <?php else: ?>
|
      <form method="POST" action="" id="registerForm">
          <div class="row">
              <div class="col-md-6">
                  <div class="mb-3">
                      <label for="nombre" class="form-label">
                          <i class="fas fa-user me-1"></i>Nombre Completo
                      </label>
                      <input type="text" class="form-control" id="nombre" name="nombre" 
                             placeholder="Ingresa tu nombre completo" required
                             value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                  </div>
              </div>
              
              <div class="col-md-6">
                  <div class="mb-3">
                      <label for="telefono" class="form-label">
                          <i class="fas fa-phone me-1"></i>Teléfono
                      </label>
                      <input type="tel" class="form-control" id="telefono" name="telefono" 
                             placeholder="Ej: 1234567890" required
                             value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                  </div>
              </div>
          </div>
          
          <div class="mb-3">
              <label for="correo" class="form-label">
                  <i class="fas fa-envelope me-1"></i>Correo Electrónico
              </label>
              <input type="email" class="form-control" id="correo" name="correo" 
                     placeholder="tu@ejemplo.com" required
                     value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
          </div>
          
          <div class="row">
              <div class="col-md-6">
                  <div class="mb-3">
                      <label for="password" class="form-label">
                          <i class="fas fa-lock me-1"></i>Contraseña
                      </label>
                      <input type="password" class="form-control" id="password" name="password" 
                             placeholder="Mínimo 6 caracteres" required>
                      <div class="form-text">
                          <small>La contraseña debe tener al menos 6 caracteres</small>
                      </div>
                  </div>
              </div>
              
              <div class="col-md-6">
                  <div class="mb-3">
                      <label for="confirm_password" class="form-label">
                          <i class="fas fa-lock me-1"></i>Confirmar Contraseña
                      </label>
                      <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                             placeholder="Repite la contraseña" required>
                  </div>
              </div>
          </div>
          
          <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="terms" required>
              <label class="form-check-label" for="terms">
                  Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">términos y condiciones</a>
              </label>
          </div>
          
          <button type="submit" class="btn btn-primary w-100 btn-lg">
              <i class="fas fa-user-plus me-2"></i>Crear Cuenta
          </button>
      </form>

      <?php endif; ?>

      <div class="mt-3 text-center">
        <a href="inicio sesion.html" class="text-white">¿Ya tienes cuenta? Iniciar sesión</a>
      </div>
    </div>
  </div>
</div>



    </div>
    
    <!-- Scroll Down Indicator -->
    <a href="#carousel-section" class="scroll-down text-white">
      <span>Desliza para más</span>
      <i class="fas fa-chevron-down"></i>
    </a>
  </div>

  <!-- Carousel Section -->
  <section id="carousel-section" class="py-5 fade-in">
    <div class="carousel-container">
      <div id="foodCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <img src="1.jpg" class="d-block w-100" alt="Plato gourmet con carne y vegetales">
            <div class="carousel-caption d-none d-md-block">
              <h5>Exquisitos platos gourmet</h5>
              <p>Preparados por chefs de renombre internacional</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="2.jpg" class="d-block w-100" alt="Vista del restaurante con ambiente acogedor">
            <div class="carousel-caption d-none d-md-block">
              <h5>Ambiente acogedor</h5>
              <p>Un espacio diseñado para disfrutar al máximo</p>
            </div>
          </div>
          <div class="carousel-item">
            <img src="4.gif" class="d-block w-100" alt="Postres elegantes y deliciosos">
            <div class="carousel-caption d-none d-md-block">
              <h5>Postres de ensueño</h5>
              <p>El broche perfecto para una experiencia culinaria inolvidable</p>
            </div>
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#foodCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#foodCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Siguiente</span>
        </button>
      </div>
    </div>
  </section>

  <!-- NUEVA SECCIÓN: Estadísticas -->
  <section class="stats-section py-5 fade-in">
    <div class="container">
      <div class="row">
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number" data-target="15">0</div>
            <div class="stat-label">Años de Experiencia</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number" data-target="50000">0</div>
            <div class="stat-label">Clientes Satisfechos</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number" data-target="25">0</div>
            <div class="stat-label">Chefs Expertos</div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="stat-item">
            <div class="stat-number" data-target="200">0</div>
            <div class="stat-label">Platos Únicos</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="py-5 text-center fade-in" style="background-color: #f2e6d9;">
    <div class="container">
      <h2 style="color:#333;">Acerca de La Bella Mesa</h2>
      <p style="color:#444;">
        La Bella Mesa nació con la misión de ofrecer experiencias culinarias únicas que combinan tradición y modernidad.
        Nuestro equipo de chefs trabaja con ingredientes frescos y de la más alta calidad para crear platillos que deleitan los sentidos.
      </p>
    </div>
  </section>

  <!-- NUEVA SECCIÓN: Menú Interactivo -->
  <section class="menu-section py-5 fade-in">
    <div class="container">
      <h2 class="text-center mb-5">Nuestro Menú Destacado</h2>
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="menu-category">
            <h4><i class="fas fa-drumstick-bite"></i> Platos Principales</h4>
            <div class="menu-item">
              <div class="d-flex justify-content-between">
                <div>
                  <h6>Salmón a la Parrilla</h6>
                  <small>Con vegetales mediterráneos y salsa de limón</small>
                </div>
                <span class="price">$450</span>
              </div>
            </div>
            <div class="menu-item">
              <div class="d-flex justify-content-between">
                <div>
                  <h6>Filete Wellington</h6>
                  <small>Envuelto en hojaldre con champiñones</small>
                </div>
                <span class="price">$580</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-4">
          <div class="menu-category">
            <h4><i class="fas fa-birthday-cake"></i> Postres</h4>
            <div class="menu-item">
              <div class="d-flex justify-content-between">
                <div>
                  <h6>Tiramisú de la Casa</h6>
                  <small>Receta tradicional italiana</small>
                </div>
                <span class="price">$180</span>
              </div>
            </div>
            <div class="menu-item">
              <div class="d-flex justify-content-between">
                <div>
                  <h6>Chocolate Soufflé</h6>
                  <small>Con helado de vainilla</small>
                </div>
                <span class="price">$220</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <!-- Información Restaurantes -->
  <section id="restaurants" class="py-5 fade-in">
    <div class="container">
      <h2 class="text-center mb-4">Nuestros Restaurantes</h2>
      <div class="row">
        <div class="col-md-4 mb-3">
          <div class="card">
            <img src="1.2.jpg" class="card-img-top" alt="Restaurante 1">
            <div class="card-body">
              <h5 class="card-title">Restaurante Gourmet</h5>
              <p class="card-text">Especializado en cocina internacional con ingredientes premium.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card">
            <img src="2.2.jpg" class="card-img-top" alt="Restaurante 2">
            <div class="card-body">
              <h5 class="card-title">La Cocina Mexicana</h5>
              <p class="card-text">Auténticos sabores mexicanos con un toque moderno.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card">
            <img src="3.2.jpg" class="card-img-top" alt="Restaurante 3">
            <div class="card-body">
              <h5 class="card-title">Postres Deliciosos</h5>
              <p class="card-text">Variedad de postres caseros y exclusivos para endulzar tu día.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <!-- NUEVA SECCIÓN: Testimonios -->
  <section class="testimonials py-5 fade-in">
    <div class="container">
      <h2 class="text-center mb-5">Lo Que Dicen Nuestros Clientes</h2>
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="testimonial-card">
            <div class="stars">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <p>"Una experiencia culinaria excepcional. El servicio es impecable y la comida simplemente deliciosa."</p>
            <div class="mt-3">
              <strong>- Carmen Rodríguez</strong>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="testimonial-card">
            <div class="stars">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <p>"El ambiente es perfecto para una cena romántica. Los postres son una obra de arte."</p>
            <div class="mt-3">
              <strong>- Miguel Hernández</strong>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="testimonial-card">
            <div class="stars">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <p>"Definitivamente el mejor restaurante de la ciudad. Cada plato es una experiencia única."</p>
            <div class="mt-3">
              <strong>- Laura Martínez</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
<!-- NUEVA SECCIÓN: Newsletter -->
  <section class="newsletter py-5 fade-in">
    <div class="container text-center">
      <h3 class="mb-4">Mantente Informado</h3>
      <p class="mb-4">Suscríbete a nuestro boletín y recibe ofertas exclusivas y noticias sobre nuevos platos.</p>
      <div class="newsletter-form">
        <form id="newsletterForm">
          <div class="input-group">
            <input type="email" 
                   class="form-control" 
                   placeholder="Tu correo electrónico" 
                   required
                   id="newsletterEmail">
            <button class="btn btn-warning" type="submit">
              <i class="fas fa-paper-plane"></i> Suscribirse
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
  
  <!-- NUEVA SECCIÓN: Información de Contacto y Horarios -->
  <section class="contact-info py-5 fade-in">
    <div class="container">
      <div class="row">
        <div class="col-md-6 mb-4">
          <h3 class="mb-4"><i class="fas fa-clock"></i> Horarios de Atención</h3>
          <div class="contact-item">
            <i class="fas fa-calendar-day contact-icon"></i>
            <strong>Lunes - Viernes:</strong> 12:00 PM - 11:00 PM
          </div>
          <div class="contact-item">
            <i class="fas fa-calendar-week contact-icon"></i>
            <strong>Sábados:</strong> 1:00 PM - 12:00 AM
          </div>
          <div class="contact-item">
            <i class="fas fa-calendar contact-icon"></i>
            <strong>Domingos:</strong> 1:00 PM - 10:00 PM
          </div>
        </div>
        <div class="col-md-6 mb-4">
          <h3 class="mb-4"><i class="fas fa-map-marker-alt"></i> Ubicación y Contacto</h3>
          <div class="contact-item">
            <i class="fas fa-map-marker-alt contact-icon"></i>
            <strong>Dirección:</strong> Av. Principal 123, Centro Histórico
          </div>
          <div class="contact-item">
            <i class="fas fa-phone contact-icon"></i>
            <strong>Teléfono:</strong> (55) 1234-5678
          </div>
          <div class="contact-item">
            <i class="fas fa-envelope contact-icon"></i>
            <strong>Email:</strong> info@labellamesa.com
          </div>
        </div>
      </div>
    </div>
  </section>

  
  <!-- Footer -->
  <footer class="bg-dark text-center text-white py-4">
    <div class="container">
      <div class="social-icons mb-3">
        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
      </div>
      <p>&copy; 2025 La Bella Mesa. Todos los derechos reservados.</p>
      <p class="mt-2">
        <a href="#" class="text-white me-3">Términos de servicio</a>
        <a href="#" class="text-white me-3">Política de privacidad</a>
        <a href="#" class="text-white">Contáctanos</a>
      </p>
    </div>
  </footer>

  <!-- NUEVOS ELEMENTOS FLOTANTES -->
  <!-- Botón de WhatsApp -->
  <a href="https://wa.me/5551234567" class="whatsapp-float" target="_blank" aria-label="Contactar por WhatsApp">
    <i class="fab fa-whatsapp"></i>
  </a>

  <!-- Botón volver arriba -->
  <button class="back-to-top" id="backToTop" aria-label="Volver arriba">
    <i class="fas fa-arrow-up"></i>
  </button>

  <!-- Notificación -->
  <div class="notification" id="notification">
    <i class="fas fa-check-circle me-2"></i>
    <span id="notificationText">¡Mensaje enviado correctamente!</span>
  </div>
  
  <!-- JavaScript Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Script original para scroll suave
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute("href")).scrollIntoView({
          behavior: "smooth"
        });
      });
    });

    // NUEVAS FUNCIONALIDADES AGREGADAS

    // 1. Indicador de carga al cargar la página
    window.addEventListener('load', function() {
      setTimeout(() => {
        document.getElementById('loadingOverlay').classList.add('hidden');
      }, 1000);
    });

    // 2. Animaciones al hacer scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, observerOptions);

    document.querySelectorAll('.fade-in').forEach(el => {
      observer.observe(el);
    });

    // 3. Contador animado para estadísticas
    function animateCounters() {
      const counters = document.querySelectorAll('.stat-number');
      counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / 200;
        let current = 0;
        
        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            counter.textContent = target.toLocaleString();
            clearInterval(timer);
          } else {
            counter.textContent = Math.floor(current).toLocaleString();
          }
        }, 10);
      });
    }

    // Ejecutar contador cuando la sección de estadísticas sea visible
    const statsObserver = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounters();
          statsObserver.unobserve(entry.target);
        }
      });
    });

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
      statsObserver.observe(statsSection);
    }

    // 4. Botón volver arriba
    const backToTop = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        backToTop.style.display = 'block';
      } else {
        backToTop.style.display = 'none';
      }
    });

    backToTop.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // 5. Sugerencias de búsqueda
    const searchInput = document.getElementById('buscador');
    const suggestions = document.getElementById('searchSuggestions');
    
    const searchData = [
      'Centro Histórico',
      'Polanco',
      'Roma Norte',
      'Condesa',
      'Santa Fe',
      'Coyoacán',
      'San Ángel',
      'Zona Rosa',
      'Del Valle',
      'Narvarte'
    ];

    searchInput.addEventListener('input', function() {
      const value = this.value.toLowerCase();
      suggestions.innerHTML = '';
      
      if (value.length > 0) {
        const filtered = searchData.filter(item => 
          item.toLowerCase().includes(value)
        );
        
        if (filtered.length > 0) {
          suggestions.style.display = 'block';
          filtered.forEach(item => {
            const div = document.createElement('div');
            div.className = 'search-suggestion';
            div.textContent = item;
            div.addEventListener('click', function() {
              searchInput.value = item;
              suggestions.style.display = 'none';
            });
            suggestions.appendChild(div);
          });
        } else {
          suggestions.style.display = 'none';
        }
      } else {
        suggestions.style.display = 'none';
      }
    });

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.d-flex')) {
        suggestions.style.display = 'none';
      }
    });

    // 6. Funcionalidad del newsletter
    const newsletterForm = document.getElementById('newsletterForm');
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notificationText');

    function showNotification(message, type = 'success') {
      notificationText.textContent = message;
      notification.className = 'notification show';
      
      if (type === 'error') {
        notification.style.background = '#e74c3c';
      } else {
        notification.style.background = '#2ecc71';
      }
      
      setTimeout(() => {
        notification.classList.remove('show');
      }, 4000);
    }

    newsletterForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const email = document.getElementById('newsletterEmail').value;
      
      // Validación simple de email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showNotification('Por favor ingresa un email válido', 'error');
        return;
      }
      
      // Simular envío
      showNotification('¡Te has suscrito exitosamente a nuestro newsletter!');
      document.getElementById('newsletterEmail').value = '';
    });

    // 7. Efectos hover mejorados para las tarjetas
    document.querySelectorAll('.card, .chef-card, .testimonial-card').forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px)';
        this.style.transition = 'transform 0.3s ease';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
    });

    // 8. Funcionalidad de reservación rápida
    document.getElementById('reserve-btn').addEventListener('click', function(e) {
      e.preventDefault();
      
      // Crear modal dinámico para reservación rápida
      const modalHTML = `
        <div class="modal fade" id="quickReservationModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fas fa-calendar-alt"></i> Reservación Rápida
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="quickReservationForm">
                  <div class="mb-3">
                    <label for="quickName" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="quickName" required>
                  </div>
                  <div class="mb-3">
                    <label for="quickPhone" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="quickPhone" required>
                  </div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label for="quickDate" class="form-label">Fecha</label>
                      <input type="date" class="form-control" id="quickDate" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="quickTime" class="form-label">Hora</label>
                      <select class="form-select" id="quickTime" required>
                        <option value="">Seleccionar hora</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="19:00">7:00 PM</option>
                        <option value="20:00">8:00 PM</option>
                        <option value="21:00">9:00 PM</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label for="quickGuests" class="form-label">Número de personas</label>
                    <select class="form-select" id="quickGuests" required>
                      <option value="">Seleccionar</option>
                      <option value="1">1 persona</option>
                      <option value="2">2 personas</option>
                      <option value="3">3 personas</option>
                      <option value="4">4 personas</option>
                      <option value="5">5 personas</option>
                      <option value="6">6+ personas</option>
                    </select>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmQuickReservation">
                  <i class="fas fa-check"></i> Confirmar Reservación
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
   
document.getElementById("loginForm").addEventListener("submit", function(e) {
  e.preventDefault();
  const email = document.getElementById("loginEmail").value;
  const pass = document.getElementById("loginPassword").value;

  if(email && pass){
    alert("✅ Inicio de sesión exitoso (ejemplo)");
    const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
    modal.hide();
  } else {
    alert("❌ Por favor completa todos los campos");
  }
});


      // Agregar modal al DOM si no existe
      if (!document.getElementById('quickReservationModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Configurar fecha mínima (hoy)
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('quickDate').setAttribute('min', today);
        
        // Event listener para confirmar reservación
        document.getElementById('confirmQuickReservation').addEventListener('click', function() {
          const form = document.getElementById('quickReservationForm');
          const formData = new FormData(form);
          
          // Validar que todos los campos estén llenos
          let isValid = true;
          form.querySelectorAll('[required]').forEach(field => {
            if (!field.value) {
              field.classList.add('is-invalid');
              isValid = false;
            } else {
              field.classList.remove('is-invalid');
            }
          });
          
          if (isValid) {
            // Simular envío de reservación
            const modal = bootstrap.Modal.getInstance(document.getElementById('quickReservationModal'));
            modal.hide();
            
            setTimeout(() => {
              showNotification('¡Reservación confirmada! Te contactaremos pronto para confirmar los detalles.');
              form.reset();
            }, 500);
          } else {
            showNotification('Por favor completa todos los campos requeridos', 'error');
          }
        });
      }
      
      // Mostrar modal
      const modal = new bootstrap.Modal(document.getElementById('quickReservationModal'));
      modal.show();
    });

    // 9. Efectos de paralaje suave
    window.addEventListener('scroll', function() {
      const scrolled = window.pageYOffset;
      const parallaxElements = document.querySelectorAll('.hero');
      
      parallaxElements.forEach(element => {
        const speed = 0.5;
        element.style.transform = `translateY(${scrolled * speed}px)`;
      });
    });

    // 10. Sistema de favoritos local
    function initFavorites() {
      const favoriteButtons = document.querySelectorAll('.favorite-btn');
      const favorites = JSON.parse(localStorage.getItem('restaurantFavorites') || '[]');
      
      favoriteButtons.forEach(btn => {
        const restaurantId = btn.getAttribute('data-restaurant-id');
        if (favorites.includes(restaurantId)) {
          btn.classList.add('favorited');
          btn.innerHTML = '<i class="fas fa-heart"></i>';
        }
        
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-restaurant-id');
          let currentFavorites = JSON.parse(localStorage.getItem('restaurantFavorites') || '[]');
          
          if (currentFavorites.includes(id)) {
            currentFavorites = currentFavorites.filter(fav => fav !== id);
            this.classList.remove('favorited');
            this.innerHTML = '<i class="far fa-heart"></i>';
            showNotification('Restaurante removido de favoritos');
          } else {
            currentFavorites.push(id);
            this.classList.add('favorited');
            this.innerHTML = '<i class="fas fa-heart"></i>';
            showNotification('Restaurante agregado a favoritos');
          }
          
          localStorage.setItem('restaurantFavorites', JSON.stringify(currentFavorites));
        });
      });
    }

    // Agregar botones de favoritos a las tarjetas de restaurantes
    document.querySelectorAll('#restaurants .card').forEach((card, index) => {
      const favoriteBtn = document.createElement('button');
      favoriteBtn.className = 'btn btn-outline-danger favorite-btn position-absolute top-0 end-0 m-2';
      favoriteBtn.setAttribute('data-restaurant-id', `restaurant-${index}`);
      favoriteBtn.innerHTML = '<i class="far fa-heart"></i>';
      favoriteBtn.style.zIndex = '10';
      
      card.style.position = 'relative';
      card.appendChild(favoriteBtn);
    });

    // Inicializar sistema de favoritos
    initFavorites();

    // 11. Modo oscuro toggle
    function initDarkMode() {
      const darkModeBtn = document.createElement('button');
      darkModeBtn.className = 'btn btn-outline-secondary position-fixed';
      darkModeBtn.style.cssText = 'top: 50%; right: 20px; z-index: 1000; transform: translateY(-50%); border-radius: 50%; width: 50px; height: 50px;';
      darkModeBtn.innerHTML = '<i class="fas fa-moon"></i>';
      darkModeBtn.setAttribute('aria-label', 'Alternar modo oscuro');
      
      document.body.appendChild(darkModeBtn);
      
      // Verificar si hay preferencia guardada
      const isDarkMode = localStorage.getItem('darkMode') === 'true';
      if (isDarkMode) {
        document.body.classList.add('dark-mode');
        darkModeBtn.innerHTML = '<i class="fas fa-sun"></i>';
      }
      
      darkModeBtn.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        const isNowDark = document.body.classList.contains('dark-mode');
        
        this.innerHTML = isNowDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        localStorage.setItem('darkMode', isNowDark.toString());
        
        showNotification(`Modo ${isNowDark ? 'oscuro' : 'claro'} activado`);
      });
    }

    // CSS para modo oscuro
    const darkModeStyles = document.createElement('style');
    darkModeStyles.textContent = `
      .dark-mode {
        filter: invert(1) hue-rotate(180deg);
      }
      
      .dark-mode img,
      .dark-mode video,
      .dark-mode .carousel-item img {
        filter: invert(1) hue-rotate(180deg);
      }
    `;
    document.head.appendChild(darkModeStyles);
    
    initDarkMode();

    // 12. Preloader de imágenes
    function preloadImages() {
      const images = ['1.jpg', '2.jpg', '4.jpg', 'restaurante1.jpg', 'restaurante2.jpg', 'restaurante3.jpg'];
      let loadedImages = 0;
      
      images.forEach(imageSrc => {
        const img = new Image();
        img.onload = function() {
          loadedImages++;
          if (loadedImages === images.length) {
            console.log('Todas las imágenes han sido cargadas');
          }
        };
        img.src = imageSrc;
      });
    }

    // Ejecutar preloader
    preloadImages();

    // 13. Efecto typewriter para el tagline
    function typewriterEffect() {
      const tagline = document.querySelector('.tagline');
      const text = tagline.textContent;
      tagline.textContent = '';
      
      let i = 0;
      const timer = setInterval(() => {
        if (i < text.length) {
          tagline.textContent += text.charAt(i);
          i++;
        } else {
          clearInterval(timer);
        }
      }, 50);
    }

    // Ejecutar efecto typewriter después de que cargue la página
    window.addEventListener('load', function() {
      setTimeout(typewriterEffect, 1500);
    });

    // 14. Funcionalidad adicional para mejorar UX
    
    // Auto-hide navbar on scroll
    let lastScrollTop = 0;
    const navbar = document.querySelector('.navbar');
    
    window.addEventListener('scroll', function() {
      let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      
      if (scrollTop > lastScrollTop && scrollTop > 100) {
        // Scrolling down
        navbar.style.transform = 'translateY(-100%)';
      } else {
        // Scrolling up
        navbar.style.transform = 'translateY(0)';
      }
      lastScrollTop = scrollTop;
    });

    // Lazy loading para imágenes
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove('lazy');
          imageObserver.unobserve(img);
        }
      });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
      imageObserver.observe(img);
    });

    console.log('🎉 La Bella Mesa - Versión mejorada cargada exitosamente!');
  </script>
</body>
</html>