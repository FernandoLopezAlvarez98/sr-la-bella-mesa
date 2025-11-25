<?php
session_start();
require_once '../controllers/AuthController.php';
require_once '../models/User.php';
require_once '../models/Restaurant.php';

$authController = new AuthController();

// Verificar autenticación
if (!$authController->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario desde la base de datos
$userModel = new User();
$userId = $_SESSION['user_id'];
$userData = $userModel->getUserById($userId);

$userName = $userData ? $userData['nombre'] : 'Usuario';
$userEmail = $userData ? $userData['correo'] : '';
$userPhone = $userData ? $userData['telefono'] : '';

// Obtener restaurantes desde la base de datos
$restaurantModel = new Restaurant();
$restaurantesDB = $restaurantModel->getAllRestaurants();

// Transformar los datos de la BD al formato esperado por la vista
$restaurantes = [];
foreach ($restaurantesDB as $rest) {
    $restaurantes[] = [
        'id' => $rest['id_restaurante'],
        'nombre' => $rest['nombre'],
        'tipo_cocina' => $rest['tipo_cocina'],
        'direccion' => $rest['direccion'],
        'calificacion' => floatval($rest['calificacion']),
        'tiempo_entrega' => $rest['tiempo_espera'],
        'precio_rango' => $rest['precio_rango'],
        'imagen' => $rest['foto_portada'],
        'promocion' => $rest['promocion'],
        'abierto' => (bool)$rest['abierto'],
        'horario_apertura' => $rest['horario_apertura'],
        'horario_cierre' => $rest['horario_cierre'],
        'capacidad_total' => $rest['capacidad_total'],
        'telefono' => $rest['telefono']
    ];
}

// Categorías
$categorias = [
    ['icono' => 'fa-utensils', 'nombre' => 'Todos', 'activo' => true],
    ['icono' => 'fa-pepper-hot', 'nombre' => 'Mexicana', 'activo' => false],
    ['icono' => 'fa-pizza-slice', 'nombre' => 'Italiana', 'activo' => false],
    ['icono' => 'fa-fish', 'nombre' => 'Japonesa', 'activo' => false],
    ['icono' => 'fa-drumstick-bite', 'nombre' => 'Argentina', 'activo' => false],
    ['icono' => 'fa-ice-cream', 'nombre' => 'Postres', 'activo' => false],
    ['icono' => 'fa-coffee', 'nombre' => 'Café', 'activo' => false],
    ['icono' => 'fa-globe', 'nombre' => 'Internacional', 'activo' => false],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Bella Mesa | Encuentra tu restaurante</title>
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.css">
    
    <style>
        :root {
            --primary-color: #e23744;
            --primary-dark: #c41e3a;
            --secondary-color: #ff914d;
            --bg-dark: #f5f5f5;
            --bg-card: #ffffff;
            --bg-light: #f8f9fa;
            --text-light: #333333;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --gold: #ffd700;
            --success: #28a745;
            --warning: #ffc107;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* ========== HEADER/NAVBAR ========== */
        .main-header {
            background: #ffffff;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-top {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-text i {
            color: var(--primary-color);
        }

        .logo-text:hover {
            color: var(--primary-color);
        }

        /* Barra de ubicación */
        .location-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: rgba(0,0,0,0.05);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .location-bar:hover {
            background: rgba(0,0,0,0.08);
        }

        .location-bar i {
            color: var(--primary-color);
        }

        .location-text {
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .location-text small {
            color: var(--text-muted);
            display: block;
            font-size: 0.75rem;
        }

        /* Barra de búsqueda */
        .search-bar {
            position: relative;
            flex: 1;
            max-width: 500px;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 25px;
            background: #f8f9fa;
            color: var(--text-dark);
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-bar input::placeholder {
            color: var(--text-muted);
        }

        .search-bar input:focus {
            outline: none;
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(226, 55, 68, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Usuario header */
        .user-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #ffffff;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .header-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0,0,0,0.05);
            border: none;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .header-icon-btn:hover {
            background: rgba(0,0,0,0.1);
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
        }

        /* ========== CATEGORÍAS SLIDER ========== */
        .categories-section {
            padding: 20px 0;
            background: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        .categories-slider {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: none;
        }

        .categories-slider::-webkit-scrollbar {
            display: none;
        }

        .category-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 90px;
            border: 2px solid transparent;
        }

        .category-item:hover {
            background: #e9ecef;
            transform: translateY(-5px);
        }

        .category-item.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-color: var(--primary-color);
        }

        .category-item i {
            font-size: 1.5rem;
            color: var(--secondary-color);
        }

        .category-item.active i {
            color: #ffffff;
        }

        .category-item span {
            font-size: 0.8rem;
            white-space: nowrap;
            color: var(--text-dark);
        }
        
        .category-item.active span {
            color: #ffffff;
        }

        /* ========== PROMOCIONES BANNER ========== */
        .promo-banner {
            padding: 20px 0;
        }

        .promo-card {
            background: linear-gradient(135deg, var(--primary-color), #ff6b6b);
            border-radius: 20px;
            padding: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .promo-card:hover {
            transform: scale(1.02);
        }

        .promo-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        }

        .promo-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            position: relative;
        }

        .promo-card p {
            opacity: 0.9;
            position: relative;
        }

        .promo-card .promo-code {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-weight: bold;
            position: relative;
        }

        /* ========== SECCIÓN DE RESTAURANTES ========== */
        .restaurants-section {
            padding: 30px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .view-all-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .view-all-btn:hover {
            color: var(--secondary-color);
            gap: 10px;
        }

        /* ========== TARJETAS DE RESTAURANTES ========== */
        .restaurant-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.08);
            height: 100%;
        }

        .restaurant-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }

        .restaurant-img-container {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .restaurant-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .restaurant-card:hover .restaurant-img {
            transform: scale(1.1);
        }

        .restaurant-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .restaurant-favorite {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            background: rgba(0,0,0,0.5);
            border: none;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .restaurant-favorite:hover,
        .restaurant-favorite.active {
            background: var(--primary-color);
            color: white;
        }

        .restaurant-favorite.active i {
            font-weight: 900;
        }

        .restaurant-status {
            position: absolute;
            bottom: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .restaurant-status.open {
            background: var(--success);
            color: white;
        }

        .restaurant-status.closed {
            background: rgba(0,0,0,0.7);
            color: #ff6b6b;
        }

        .restaurant-info {
            padding: 20px;
        }

        .restaurant-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .restaurant-cuisine {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .restaurant-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .meta-item i {
            color: var(--secondary-color);
        }

        .rating {
            background: rgba(255,215,0,0.2);
            padding: 3px 8px;
            border-radius: 5px;
        }

        .rating i {
            color: var(--gold);
        }

        .restaurant-price {
            color: var(--success);
            font-weight: 600;
        }

        /* ========== RESTAURANTES DESTACADOS HORIZONTAL ========== */
        .featured-restaurants {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 10px 0 20px;
            scrollbar-width: none;
        }

        .featured-restaurants::-webkit-scrollbar {
            display: none;
        }

        .featured-card {
            min-width: 300px;
            max-width: 300px;
        }

        /* ========== FILTROS ========== */
        .filters-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid rgba(0,0,0,0.15);
            border-radius: 20px;
            background: #ffffff;
            color: var(--text-dark);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #ffffff;
        }

        .filter-btn i {
            font-size: 0.8rem;
        }

        /* ========== FOOTER ========== */
        .user-footer {
            background: #ffffff;
            padding: 40px 0 20px;
            margin-top: 50px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .footer-links h5 {
            color: var(--text-dark);
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links ul li {
            margin-bottom: 10px;
        }

        .footer-links ul li a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links ul li a:hover {
            color: var(--primary-color);
        }

        .social-icons a {
            display: inline-flex;
            width: 40px;
            height: 40px;
            background: rgba(0,0,0,0.05);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-icons a:hover {
            background: var(--primary-color);
            color: #ffffff;
            transform: translateY(-3px);
        }

        /* ========== BARRA NAVEGACIÓN INFERIOR MÓVIL ========== */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            padding: 10px 0;
            z-index: 1000;
            border-top: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .mobile-nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.75rem;
            transition: color 0.3s;
        }

        .mobile-nav-item i {
            font-size: 1.2rem;
        }

        .mobile-nav-item.active,
        .mobile-nav-item:hover {
            color: var(--primary-color);
        }

        /* ========== DROPDOWN USUARIO ========== */
        .user-dropdown {
            position: relative;
        }

        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: #ffffff;
            border-radius: 15px;
            padding: 15px;
            min-width: 250px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            z-index: 1001;
            border: 1px solid rgba(0,0,0,0.08);
        }

        .user-dropdown-menu.show {
            display: block;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-dropdown-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            margin-bottom: 15px;
        }

        .user-dropdown-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffffff;
        }

        .user-dropdown-info h6 {
            margin: 0;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .user-dropdown-info small {
            color: var(--text-muted);
        }

        .user-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
            transition: background 0.3s;
        }

        .user-dropdown-menu a:hover {
            background: rgba(0,0,0,0.05);
        }

        .user-dropdown-menu a i {
            width: 20px;
            text-align: center;
            color: var(--primary-color);
        }

        .user-dropdown-menu .logout-btn {
            color: #ff6b6b;
            margin-top: 10px;
            border-top: 1px solid rgba(0,0,0,0.08);
            padding-top: 15px;
        }

        .user-dropdown-menu .logout-btn i {
            color: #ff6b6b;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .search-bar {
                max-width: 100%;
                order: 3;
                margin-top: 15px;
            }

            .header-top .container {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {
            .mobile-nav {
                display: block;
            }

            body {
                padding-bottom: 70px;
            }

            .user-header {
                display: none;
            }

            .location-bar {
                flex: 1;
            }

            .restaurant-card {
                margin-bottom: 15px;
            }

            .promo-card {
                padding: 20px;
            }

            .promo-card h3 {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .category-item {
                min-width: 70px;
                padding: 10px 15px;
            }

            .category-item i {
                font-size: 1.2rem;
            }

            .category-item span {
                font-size: 0.7rem;
            }
        }

        /* ========== ANIMACIONES ========== */
        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Skeleton loading */
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ========== MODAL DE RESERVA RÁPIDA ========== */
        .quick-reserve-modal .modal-content {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 20px;
        }

        .quick-reserve-modal .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .quick-reserve-modal .modal-header .modal-title {
            color: var(--text-dark);
        }

        .quick-reserve-modal .btn-close {
            filter: none;
        }

        .quick-reserve-modal .form-label {
            color: var(--text-dark);
        }

        .quick-reserve-modal .form-control,
        .quick-reserve-modal .form-select {
            background: #f8f9fa;
            border: 1px solid rgba(0,0,0,0.15);
            color: var(--text-dark);
            border-radius: 10px;
        }

        .quick-reserve-modal .form-control:focus,
        .quick-reserve-modal .form-select:focus {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(226, 55, 68, 0.15);
        }

        .quick-reserve-modal .form-control::placeholder {
            color: var(--text-muted);
        }

        .quick-reserve-modal option {
            background: #ffffff;
            color: var(--text-dark);
        }
    </style>
</head>
<body>

    <!-- ========== HEADER ========== -->
    <header class="main-header">
        <div class="header-top">
            <div class="container">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <!-- Logo -->
                    <a href="../index.php" class="logo-text">
                        <i class="fas fa-utensils"></i>
                        <span>La Bella Mesa</span>
                    </a>

                    <!-- Ubicación -->
                    <div class="location-bar" onclick="openLocationModal()">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="location-text">
                            <small>Mi ubicación</small>
                            <strong>Ciudad de México, Centro</strong>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>

                    <!-- Barra de búsqueda -->
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar restaurantes, cocinas..." id="searchInput">
                    </div>

                    <!-- Usuario -->
                    <div class="user-header">
                        <button class="header-icon-btn" title="Favoritos">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="header-icon-btn" title="Notificaciones">
                            <i class="far fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <div class="user-dropdown">
                            <div class="user-avatar" onclick="toggleUserDropdown()">
                                <?php echo strtoupper(substr($userName, 0, 1)); ?>
                            </div>
                            <div class="user-dropdown-menu" id="userDropdownMenu">
                                <div class="user-dropdown-header">
                                    <div class="user-dropdown-avatar">
                                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                    </div>
                                    <div class="user-dropdown-info">
                                        <h6><?php echo htmlspecialchars($userName); ?></h6>
                                        <small><?php echo htmlspecialchars($userEmail); ?></small>
                                    </div>
                                </div>
                                <a href="userProfile.php"><i class="fas fa-user"></i> Mi Perfil</a>
                                <a href="userReservation.php"><i class="fas fa-calendar-check"></i> Mis Reservaciones</a>
                                <a href="#"><i class="fas fa-heart"></i> Favoritos</a>
                                <a href="userReservation.php"><i class="fas fa-history"></i> Historial</a>
                                <a href="#"><i class="fas fa-cog"></i> Configuración</a>
                                <a href="login.php?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ========== CATEGORÍAS ========== -->
    <section class="categories-section">
        <div class="container">
            <div class="categories-slider">
                <?php foreach ($categorias as $cat): ?>
                <div class="category-item <?php echo $cat['activo'] ? 'active' : ''; ?>" onclick="filterByCategory('<?php echo $cat['nombre']; ?>')">
                    <i class="fas <?php echo $cat['icono']; ?>"></i>
                    <span><?php echo $cat['nombre']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========== BANNER PROMOCIONAL ========== -->
    <section class="promo-banner">
        <div class="container">
            <div class="promo-card">
                <h3><i class="fas fa-gift me-2"></i>¡Bienvenido a La Bella Mesa!</h3>
                <p>Reserva tu mesa favorita y disfruta de los mejores restaurantes de la ciudad</p>
                <div class="promo-code">
                    <i class="fas fa-tag me-2"></i>Usa código: BELLA20 para 20% OFF
                </div>
            </div>
        </div>
    </section>

    <!-- ========== RESTAURANTES DESTACADOS ========== -->
    <section class="restaurants-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-fire"></i>
                    Restaurantes Destacados
                </h2>
                <a href="#" class="view-all-btn">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="featured-restaurants">
                <?php foreach ($restaurantes as $index => $rest): ?>
                <?php if ($index < 4): ?>
                <div class="featured-card fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                    <div class="restaurant-card" onclick="openRestaurantDetail('<?php echo $rest['id']; ?>')">
                        <div class="restaurant-img-container">
                            <img src="<?php echo $rest['imagen']; ?>" alt="<?php echo $rest['nombre']; ?>" class="restaurant-img">
                            <?php if ($rest['promocion']): ?>
                            <span class="restaurant-badge">
                                <i class="fas fa-percent me-1"></i><?php echo $rest['promocion']; ?>
                            </span>
                            <?php endif; ?>
                            <button class="restaurant-favorite" onclick="event.stopPropagation(); toggleFavorite(this, '<?php echo $rest['id']; ?>')">
                                <i class="far fa-heart"></i>
                            </button>
                            <span class="restaurant-status <?php echo $rest['abierto'] ? 'open' : 'closed'; ?>">
                                <?php echo $rest['abierto'] ? 'Abierto' : 'Cerrado'; ?>
                            </span>
                        </div>
                        <div class="restaurant-info">
                            <h5 class="restaurant-name"><?php echo $rest['nombre']; ?></h5>
                            <p class="restaurant-cuisine"><?php echo $rest['tipo_cocina']; ?></p>
                            <div class="restaurant-meta">
                                <span class="meta-item rating">
                                    <i class="fas fa-star"></i> <?php echo $rest['calificacion']; ?>
                                </span>
                                <span class="meta-item">
                                    <i class="far fa-clock"></i> <?php echo $rest['tiempo_entrega']; ?> min
                                </span>
                                <span class="meta-item restaurant-price">
                                    <?php echo $rest['precio_rango']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========== TODOS LOS RESTAURANTES ========== -->
    <section class="restaurants-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-store"></i>
                    Todos los Restaurantes
                </h2>
            </div>

            <!-- Filtros -->
            <div class="filters-bar">
                <button class="filter-btn active">
                    <i class="fas fa-sort"></i> Relevancia
                </button>
                <button class="filter-btn">
                    <i class="fas fa-star"></i> Mejor calificados
                </button>
                <button class="filter-btn">
                    <i class="fas fa-clock"></i> Más rápido
                </button>
                <button class="filter-btn">
                    <i class="fas fa-dollar-sign"></i> Precio
                </button>
                <button class="filter-btn">
                    <i class="fas fa-percent"></i> Promociones
                </button>
            </div>

            <!-- Grid de restaurantes -->
            <div class="row" id="restaurantsGrid">
                <?php foreach ($restaurantes as $index => $rest): ?>
                <div class="col-lg-4 col-md-6 mb-4 fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                    <div class="restaurant-card" onclick="openRestaurantDetail('<?php echo $rest['id']; ?>')">
                        <div class="restaurant-img-container">
                            <img src="<?php echo $rest['imagen']; ?>" alt="<?php echo $rest['nombre']; ?>" class="restaurant-img">
                            <?php if ($rest['promocion']): ?>
                            <span class="restaurant-badge">
                                <i class="fas fa-percent me-1"></i>Promo
                            </span>
                            <?php endif; ?>
                            <button class="restaurant-favorite" onclick="event.stopPropagation(); toggleFavorite(this, '<?php echo $rest['id']; ?>')">
                                <i class="far fa-heart"></i>
                            </button>
                            <span class="restaurant-status <?php echo $rest['abierto'] ? 'open' : 'closed'; ?>">
                                <?php echo $rest['abierto'] ? 'Abierto' : 'Cerrado'; ?>
                            </span>
                        </div>
                        <div class="restaurant-info">
                            <h5 class="restaurant-name"><?php echo $rest['nombre']; ?></h5>
                            <p class="restaurant-cuisine">
                                <i class="fas fa-map-marker-alt me-1"></i><?php echo $rest['direccion']; ?>
                            </p>
                            <div class="restaurant-meta">
                                <span class="meta-item rating">
                                    <i class="fas fa-star"></i> <?php echo $rest['calificacion']; ?>
                                </span>
                                <span class="meta-item">
                                    <i class="far fa-clock"></i> <?php echo $rest['tiempo_entrega']; ?> min
                                </span>
                                <span class="meta-item restaurant-price">
                                    <?php echo $rest['precio_rango']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Botón cargar más -->
            <div class="text-center mt-4">
                <button class="btn btn-outline-light btn-lg" onclick="loadMoreRestaurants()">
                    <i class="fas fa-plus me-2"></i>Cargar más restaurantes
                </button>
            </div>
        </div>
    </section>

    <!-- ========== FOOTER ========== -->
    <footer class="user-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-utensils me-2"></i>La Bella Mesa</h5>
                    <p class="text-muted">
                        Tu plataforma de reservaciones para los mejores restaurantes de la ciudad.
                    </p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 footer-links">
                    <h5>Descubre</h5>
                    <ul>
                        <li><a href="#">Restaurantes</a></li>
                        <li><a href="#">Promociones</a></li>
                        <li><a href="#">Categorías</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 footer-links">
                    <h5>Soporte</h5>
                    <ul>
                        <li><a href="#">Ayuda</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Contacto</a></li>
                        <li><a href="#">Reportar</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 footer-links">
                    <h5>Legal</h5>
                    <ul>
                        <li><a href="#">Términos</a></li>
                        <li><a href="#">Privacidad</a></li>
                        <li><a href="#">Cookies</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 footer-links">
                    <h5>Descarga la App</h5>
                    <a href="#" class="d-block mb-2">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" style="height: 40px;">
                    </a>
                    <a href="#">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" style="height: 40px;">
                    </a>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center text-muted">
                <p>&copy; 2025 La Bella Mesa. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- ========== NAVEGACIÓN MÓVIL ========== -->
    <nav class="mobile-nav">
        <div class="mobile-nav-items">
            <a href="dashboardUser.php" class="mobile-nav-item active">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="#" class="mobile-nav-item">
                <i class="fas fa-search"></i>
                <span>Buscar</span>
            </a>
            <a href="userReservation.php" class="mobile-nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Reservas</span>
            </a>
            <a href="#" class="mobile-nav-item">
                <i class="fas fa-heart"></i>
                <span>Favoritos</span>
            </a>
            <a href="userProfile.php" class="mobile-nav-item">
                <i class="fas fa-user"></i>
                <span>Perfil</span>
            </a>
        </div>
    </nav>

    <!-- ========== MODAL DETALLE RESTAURANTE ========== -->
    <div class="modal fade quick-reserve-modal" id="restaurantDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restaurantModalTitle">
                        <i class="fas fa-utensils me-2"></i>Restaurante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img src="" id="restaurantModalImg" class="img-fluid rounded mb-3" alt="Restaurante">
                            <div class="d-flex gap-2 mb-3">
                                <span class="badge bg-success" id="restaurantModalStatus">Abierto</span>
                                <span class="badge bg-warning text-dark" id="restaurantModalRating">
                                    <i class="fas fa-star"></i> 4.8
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 id="restaurantModalName">Nombre del Restaurante</h4>
                            <p class="text-muted" id="restaurantModalCuisine">Tipo de cocina</p>
                            <p><i class="fas fa-map-marker-alt me-2 text-danger"></i><span id="restaurantModalAddress">Dirección</span></p>
                            <p><i class="fas fa-clock me-2 text-warning"></i>Tiempo estimado: <span id="restaurantModalTime">25-35</span> min</p>
                            <hr>
                            <h5>Hacer Reservación</h5>
                            <form id="quickReserveForm">
                                <input type="hidden" id="reserveRestaurantId" value="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha</label>
                                        <input type="date" class="form-control" id="reserveDate" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Hora</label>
                                        <select class="form-select" id="reserveTime" required>
                                            <option value="">Seleccionar</option>
                                            <option value="12:00">12:00 PM</option>
                                            <option value="13:00">1:00 PM</option>
                                            <option value="14:00">2:00 PM</option>
                                            <option value="15:00">3:00 PM</option>
                                            <option value="18:00">6:00 PM</option>
                                            <option value="19:00">7:00 PM</option>
                                            <option value="20:00">8:00 PM</option>
                                            <option value="21:00">9:00 PM</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Número de personas</label>
                                        <select class="form-select" id="reservePeople" required>
                                            <option value="">Seleccionar</option>
                                            <option value="1">1 persona</option>
                                            <option value="2">2 personas</option>
                                            <option value="3">3 personas</option>
                                            <option value="4">4 personas</option>
                                            <option value="5">5 personas</option>
                                            <option value="6">6 personas</option>
                                            <option value="7">7+ personas</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-danger" onclick="makeReservation()">
                        <i class="fas fa-calendar-check me-2"></i>Reservar Mesa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.12.0/toastify.min.js"></script>
    
    <script>
        // Datos de restaurantes para JavaScript
        const restaurantesData = <?php echo json_encode($restaurantes); ?>;

        // Toggle dropdown usuario
        function toggleUserDropdown() {
            const menu = document.getElementById('userDropdownMenu');
            menu.classList.toggle('show');
        }

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.user-dropdown');
            const menu = document.getElementById('userDropdownMenu');
            if (!dropdown.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // Filtrar por categoría
        function filterByCategory(category) {
            // Remover active de todas las categorías
            document.querySelectorAll('.category-item').forEach(item => {
                item.classList.remove('active');
            });
            // Agregar active a la categoría seleccionada
            event.currentTarget.classList.add('active');
            
            showNotification(`Filtrando por: ${category}`, 'info');
            // Aquí iría la lógica de filtrado real
        }

        // Toggle favorito
        function toggleFavorite(btn, restaurantId) {
            btn.classList.toggle('active');
            const icon = btn.querySelector('i');
            
            if (btn.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                showNotification('Agregado a favoritos', 'success');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                showNotification('Eliminado de favoritos', 'info');
            }
        }

        // Abrir detalle de restaurante
        function openRestaurantDetail(restaurantId) {
            const restaurant = restaurantesData.find(r => r.id === restaurantId);
            if (!restaurant) return;

            // Guardar el ID del restaurante en el formulario
            document.getElementById('reserveRestaurantId').value = restaurantId;
            
            document.getElementById('restaurantModalTitle').innerHTML = `<i class="fas fa-utensils me-2"></i>${restaurant.nombre}`;
            document.getElementById('restaurantModalImg').src = restaurant.imagen;
            document.getElementById('restaurantModalName').textContent = restaurant.nombre;
            document.getElementById('restaurantModalCuisine').textContent = restaurant.tipo_cocina;
            document.getElementById('restaurantModalAddress').textContent = restaurant.direccion;
            document.getElementById('restaurantModalTime').textContent = restaurant.tiempo_entrega;
            document.getElementById('restaurantModalRating').innerHTML = `<i class="fas fa-star"></i> ${restaurant.calificacion}`;
            
            const statusBadge = document.getElementById('restaurantModalStatus');
            if (restaurant.abierto) {
                statusBadge.className = 'badge bg-success';
                statusBadge.textContent = 'Abierto';
            } else {
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'Cerrado';
            }

            // Configurar fecha mínima (hoy)
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('reserveDate').min = today;
            document.getElementById('reserveDate').value = today;
            
            // Resetear el formulario
            document.getElementById('reserveTime').value = '';
            document.getElementById('reservePeople').value = '';

            const modal = new bootstrap.Modal(document.getElementById('restaurantDetailModal'));
            modal.show();
        }

        // Hacer reservación
        async function makeReservation() {
            const restaurantId = document.getElementById('reserveRestaurantId').value;
            const date = document.getElementById('reserveDate').value;
            const time = document.getElementById('reserveTime').value;
            const people = document.getElementById('reservePeople').value;

            if (!date || !time || !people) {
                showNotification('Por favor completa todos los campos', 'error');
                return;
            }
            
            if (!restaurantId) {
                showNotification('Error: No se ha seleccionado un restaurante', 'error');
                return;
            }

            // Deshabilitar el botón mientras se procesa
            const submitBtn = document.querySelector('#restaurantDetailModal .btn-danger');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';

            try {
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('restaurant_id', restaurantId);
                formData.append('date', date);
                formData.append('time', time);
                formData.append('people', people);

                const response = await fetch('../controllers/ReservationController.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');
                    
                    // Mostrar detalles de la reservación
                    setTimeout(() => {
                        showNotification(`Mesa ${result.data.table_number} reservada para ${result.data.people} persona(s)`, 'info');
                    }, 1500);
                    
                    // Cerrar el modal
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('restaurantDetailModal')).hide();
                    }, 2000);
                } else {
                    showNotification(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión. Por favor intenta nuevamente.', 'error');
            } finally {
                // Restaurar el botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        // Cargar más restaurantes
        function loadMoreRestaurants() {
            showNotification('Cargando más restaurantes...', 'info');
            // Aquí iría la lógica para cargar más restaurantes vía AJAX
        }

        // Abrir modal de ubicación
        function openLocationModal() {
            showNotification('Función de ubicación próximamente', 'info');
        }

        // Mostrar notificación
        function showNotification(message, type = 'info') {
            const colors = {
                'success': 'linear-gradient(to right, #00b09b, #96c93d)',
                'error': 'linear-gradient(to right, #ff5f6d, #ffc371)',
                'warning': 'linear-gradient(to right, #f7971e, #ffd200)',
                'info': 'linear-gradient(to right, #2193b0, #6dd5ed)'
            };

            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: colors[type] || colors['info']
                },
                stopOnFocus: true
            }).showToast();
        }

        // Búsqueda en tiempo real
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            if (query.length > 2) {
                // Aquí iría la lógica de búsqueda
                console.log('Buscando:', query);
            }
        });

        // Filtros
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                showNotification('Filtro aplicado', 'info');
            });
        });

        // Navegación móvil
        document.querySelectorAll('.mobile-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.mobile-nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Animación de entrada para las tarjetas
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        console.log('🍽️ Dashboard de usuario cargado correctamente');
    </script>

</body>
</html>
