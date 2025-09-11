<!DOCTYPE html>
<html lang="pt-BR" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Avaliações</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: {
                        50: '#eff6ff',
                        100: '#dbeafe',
                        200: '#bfdbfe',
                        300: '#93c5fd',
                        400: '#60a5fa',
                        500: '#3b82f6',
                        600: '#2563eb',
                        700: '#1d4ed8',
                        800: '#1e40af',
                        900: '#1e3a8a',
                    },
                    dark: {
                        100: '#f3f4f6',
                        200: '#e5e7eb',
                        300: '#d1d5db',
                        400: '#9ca3af',
                        500: '#6b7280',
                        600: '#4b5563',
                        700: '#374151',
                        800: '#1f2937',
                        900: '#111827',
                    }
                },
                fontFamily: {
                    'sans': ['Inter', 'ui-sans-serif', 'system-ui']
                }
            }
        }
    }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --primary: #3b82f6;
        --primary-dark: #2563eb;
        --secondary: #6366f1;
        --success: #10b981;
        --info: #06b6d4;
        --warning: #f59e0b;
        --danger: #ef4444;
        --light: #f8fafc;
        --dark: #0f172a;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f1f5f9;
        color: #334155;
        transition: background-color 0.3s, color 0.3s;
    }

    .dark body {
        background-color: #0f172a;
        color: #e2e8f0;
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
        background-color: white;
        overflow: hidden;
    }

    .dark .card {
        background-color: #1e293b;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2), 0 2px 4px -1px rgba(0, 0, 0, 0.1);
    }

    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .dark .card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
    }

    .stat-card {
        position: relative;
        overflow: hidden;
        padding-top: 1.5rem;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--info));
    }

    .table-container {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    th {
        background-color: #f8fafc;
        padding: 1rem;
        font-weight: 600;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    .dark th {
        background-color: #1e293b;
        color: #94a3b8;
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .dark td {
        border-bottom: 1px solid #334155;
    }

    tr:hover td {
        background-color: #f8fafc;
    }

    .dark tr:hover td {
        background-color: #1e293b;
    }

    tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background-color: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-1px);
    }

    .gradient-text {
        background: linear-gradient(90deg, var(--primary), var(--info));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .section-title {
        position: relative;
        padding-bottom: 0.75rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.25rem;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--info));
        border-radius: 3px;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Mobile menu animation */
    #mobile-menu {
        transition: transform 0.3s ease, opacity 0.3s ease;
        transform: translateY(-10px);
        opacity: 0;
        pointer-events: none;
    }

    #mobile-menu.show {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* User dropdown animation */
    #user-menu-dropdown {
        transition: transform 0.3s ease, opacity 0.3s ease;
        transform: translateY(-10px);
        opacity: 0;
        pointer-events: none;
    }

    #user-menu-dropdown.show {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* Loading animation for cards */
    .card-loading {
        position: relative;
        overflow: hidden;
    }

    .card-loading::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg,
                rgba(255, 255, 255, 0) 0,
                rgba(255, 255, 255, 0.2) 20%,
                rgba(255, 255, 255, 0.5) 60%,
                rgba(255, 255, 255, 0));
        animation: shimmer 2s infinite;
    }

    .dark .card-loading::after {
        background: linear-gradient(90deg,
                rgba(30, 41, 59, 0) 0,
                rgba(30, 41, 59, 0.2) 20%,
                rgba(30, 41, 59, 0.5) 60%,
                rgba(30, 41, 59, 0));
    }

    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }

    /* Focus states for accessibility */
    button:focus,
    a:focus {
        outline: 2px solid var(--primary);
        outline-offset: 2px;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .dark ::-webkit-scrollbar-track {
        background: #1e293b;
    }

    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .dark ::-webkit-scrollbar-thumb {
        background: #475569;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    </style>
</head>

<body class="bg-gray-100 dark:bg-dark-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">

    <header class="bg-white dark:bg-dark-800 shadow-md sticky top-0 z-30">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="<?= BASE_URL ?>assets/images/rje.png" alt="Logo" class="w-42 h-10" />
                <h1 class="ml-3 text-lg font-semibold hidden md:block">Sistema de Avaliações</h1>
            </div>

            <!-- Botão do menu hambúrguer -->
            <button id="menu-toggle"
                class="md:hidden text-gray-700 dark:text-gray-300 focus:outline-none p-1 rounded-md hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors"
                aria-label="Abrir menu">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Menu Desktop -->
            <nav class="hidden md:flex space-x-1">
                <a href="<?= BASE_URL ?>index.php"
                    class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 rounded-md transition-colors flex items-center gap-2 font-medium">
                    <i class="fas fa-home w-5 text-center"></i> Dashboard
                </a>
                <a href="<?= BASE_URL ?>pages/clientes/listar.php"
                    class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 rounded-md transition-colors flex items-center gap-2 font-medium">
                    <i class="fas fa-users w-5 text-center"></i> Clientes
                </a>
                <a href="<?= BASE_URL ?>pages/avaliacoes/listar.php"
                    class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 rounded-md transition-colors flex items-center gap-2 font-medium">
                    <i class="fas fa-clipboard-check w-5 text-center"></i> Avaliações
                </a>
                <a href="<?= BASE_URL ?>pages/agendamentos/listar.php"
                    class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 rounded-md transition-colors flex items-center gap-2 font-medium">
                    <i class="fas fa-calendar-alt w-5 text-center"></i> Agendamentos
                </a>
            </nav>

            <div class="flex items-center gap-4">
                <button id="themeToggle"
                    class="p-2 rounded-full focus:outline-none hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors"
                    aria-label="Alternar tema">
                    <i class="fas fa-moon dark:hidden text-gray-700"></i>
                    <i class="fas fa-sun hidden dark:block text-yellow-400"></i>
                </button>

                <!-- Menu do usuário -->
                <div class="relative">
                    <button id="user-menu"
                        class="flex items-center space-x-2 focus:outline-none p-1 rounded-md hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors"
                        aria-label="Menu do usuário">
                        <?php
                        // Verifica se o usuário tem uma foto no banco de dados
                        $fotoUsuario = '';
                        if (isset($_SESSION['user_id'])) {
                            $stmt = $db->prepare("SELECT foto FROM usuarios WHERE id = :id");
                            $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            $usuario = $result->fetchArray(SQLITE3_ASSOC);
                            $fotoUsuario = $usuario['foto'] ?? '';
                        }
                        ?>

                        <?php if (!empty($fotoUsuario)): ?>
                        <!-- Mostra a foto do usuário em miniatura circular -->
                        <img src="<?= BASE_URL ?>/../uploads/<?= htmlspecialchars($fotoUsuario) ?>"
                            alt="Foto do usuário"
                            class="h-8 w-8 rounded-full object-cover border-2 border-gray-200 dark:border-dark-600">
                        <?php else: ?>
                        <!-- Mostra um ícone padrão se não tiver foto -->
                        <div
                            class="h-8 w-8 bg-gray-100 dark:bg-dark-700 rounded-full flex items-center justify-center border-2 border-gray-200 dark:border-dark-600">
                            <i class="fas fa-user text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <?php endif; ?>

                        <span
                            class="text-gray-700 dark:text-gray-300 font-medium hidden lg:block"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <i class="fas fa-chevron-down text-gray-500 dark:text-gray-400 text-xs"></i>
                    </button>
                    <div id="user-menu-dropdown"
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-dark-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-dark-700 transition-all duration-300 transform origin-top-right">
                        <a href="<?= BASE_URL ?>pages/usuarios/cadastrar_personal.php"
                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-cog w-4 text-center"></i> Configurações
                        </a>
                        <a href="<?= BASE_URL ?>logout.php"
                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors flex items-center gap-2">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i> Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Mobile -->
        <nav id="mobile-menu"
            class="absolute top-full left-0 w-full bg-white dark:bg-dark-800 shadow-md border-t border-gray-200 dark:border-dark-700 py-2 transition-all duration-300">
            <a href="<?= BASE_URL ?>index.php"
                class="block py-3 px-6 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors flex items-center gap-3">
                <i class="fas fa-home w-5 text-center"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>pages/clientes/listar.php"
                class="block py-3 px-6 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors flex items-center gap-3">
                <i class="fas fa-users w-5 text-center"></i> Clientes
            </a>
            <a href="<?= BASE_URL ?>pages/avaliacoes/listar.php"
                class="block py-3 px-6 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors flex items-center gap-3">
                <i class="fas fa-clipboard-check w-5 text-center"></i> Avaliações
            </a>
            <a href="<?= BASE_URL ?>pages/agendamentos/listar.php"
                class="block py-3 px-6 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 transition-colors flex items-center gap-3">
                <i class="fas fa-calendar-alt w-5 text-center"></i> Agendamentos
            </a>
        </nav>
    </header>

    <script>
    // Toggle do menu hambúrguer com animação
    document.getElementById('menu-toggle').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('show');

        // Alterar ícone do menu
        const icon = this.querySelector('i');
        if (mobileMenu.classList.contains('show')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });

    // Toggle dropdown do menu do usuário com animação
    document.getElementById('user-menu').addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('user-menu-dropdown');
        dropdown.classList.toggle('show');
    });

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('user-menu');
        const dropdown = document.getElementById('user-menu-dropdown');
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const icon = menuToggle.querySelector('i');

        if (!userMenu.contains(event.target)) {
            dropdown.classList.remove('show');
        }

        if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
            mobileMenu.classList.remove('show');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
    </script>