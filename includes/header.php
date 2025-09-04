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
                    dark: {
                        100: '#f3f4f6',
                        800: '#1f2937',
                        900: '#111827',
                    }
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --background: #f3f4f6;
        --text: #111827;
    }

    .dark {
        --background: #111827;
        --text: #f3f4f6;
    }

    body {
        background-color: var(--background);
        color: var(--text);
        transition: background-color 0.3s, color 0.3s;
    }

    .alert-auto-close {
        opacity: 1;
        transition: opacity 0.5s ease-out;
    }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:bg-gray-900 dark:text-gray-100">

    <header class="bg-white shadow dark:bg-gray-900 dark:text-gray-100 sticky top-0">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <!-- logo -->
            <img src="<?= BASE_URL ?>assets/images/rje.png" alt="Banner" class="w-42 h-12" />

            <!-- Título
           <h1 class="text-xl font-bold text-gray-800">Sistema de Avaliações Físicas</h1>
             -->
            <!-- Botão do menu hambúrguer -->
            <button id="menu-toggle" class="md:hidden text-gray-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <!-- Menu Desktop -->
            <nav class="hidden md:flex space-x-6">
                <a href="<?= BASE_URL ?>index.php" class="text-gray-600 hover:text-blue-600">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="<?= BASE_URL ?>pages/clientes/listar.php" class="text-gray-600 hover:text-blue-600">
                    <i class="fas fa-users mr-1"></i> Clientes
                </a>
                <a href="<?= BASE_URL ?>pages/avaliacoes/listar.php" class="text-gray-600 hover:text-blue-600">
                    <i class="fas fa-clipboard-check mr-1"></i> Avaliações
                </a>
                <a href="<?= BASE_URL ?>pages/agendamentos/listar.php" class="text-gray-600 hover:text-blue-600">
                    <i class="fas fa-calendar-alt mr-1"></i> Agendamentos
                </a>
            </nav>

            <button id="themeToggle" class="p-2 rounded-full focus:outline-none">
                <i class="fas fa-moon dark:hidden text-gray-700"></i>
                <i class="fas fa-sun hidden dark:block text-yellow-300"></i>
            </button>

            <!-- Menu do usuário -->
            <div class="relative">
                <button id="user-menu" class="flex items-center space-x-2 focus:outline-none">
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
                    <img src="<?= BASE_URL ?>/../uploads/<?= htmlspecialchars($fotoUsuario) ?>" alt="Foto do usuário"
                        class="h-8 w-8 rounded-full object-cover">
                    <?php else: ?>
                    <!-- Mostra um ícone padrão se não tiver foto -->
                    <div
                        class="h-8 w-8 bg-gray-200 rounded-full flex items-center justify-center dark:bg-gray-900 dark:text-gray-100">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <?php endif; ?>

                    <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down text-gray-500"></i>
                </button>
                <div id="user-menu-dropdown"
                    class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 dark:bg-gray-900 dark:text-gray-100">
                    <a href="<?= BASE_URL ?>pages/usuarios/cadastrar_personal.php"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-100">
                        <i class="fas fa-cog mr-2"></i> Configurações
                    </a>
                    <a href="<?= BASE_URL ?>logout.php"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:text-gray-100">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>

        <!-- Menu Mobile -->
        <nav id="mobile-menu" class="hidden md:hidden bg-white shadow-md absolute top-16 left-0 w-full">
            <a href=".<?= BASE_URL ?>.<?= BASE_URL ?>index.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-home mr-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>pages/clientes/listar.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-users mr-1"></i> Clientes
            </a>
            <a href="<?= BASE_URL ?>pages/avaliacoes/listar.php"
                class="block py-2 px-4 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-clipboard-check mr-1"></i> Avaliações
            </a>
            <a href="<?= BASE_URL ?>pages/agendamentos/listar.php"
                class="block py-2 px-4 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-calendar-alt mr-1"></i> Agendamentos
            </a>
        </nav>
    </header>

    <script>
    // Toggle do menu hambúrguer
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

    // Toggle dropdown do menu do usuário
    document.getElementById('user-menu').addEventListener('click', function() {
        document.getElementById('user-menu-dropdown').classList.toggle('hidden');
    });

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(event) {
        const userMenu = document.getElementById('user-menu');
        const dropdown = document.getElementById('user-menu-dropdown');
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');

        if (!userMenu.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }

        if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
            mobileMenu.classList.add('hidden');
        }
    });
    </script>