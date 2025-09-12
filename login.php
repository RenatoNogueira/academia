<?php
require_once './includes/config.php';
require_once './includes/auth.php';
require_once './includes/db.php';

// Inicializa a variável de erro
$error = '';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (login($username, $password, $db)) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Usuário ou senha inválidos';
    }
}

// Verificar e definir o tema (claro/escuro)
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'] === 'dark' ? 'dark' : 'light';
}

$currentTheme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

?>

<!DOCTYPE html>
<html lang="pt-BR" class="<?= $currentTheme === 'dark' ? 'dark' : 'light' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Avaliações</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
        }

        .login-image {
            flex: 1;
            background: linear-gradient(rgba(30, 64, 175, 0.7), rgba(30, 64, 175, 0.7)), url('https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: white;
            position: relative;
        }

        .dark .login-image {
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
            background-size: cover;
            background-position: center;
        }

        .login-form {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background-color: #f8fafc;
        }

        .dark .login-form {
            background-color: #0f172a;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            background-color: white;
            transition: all 0.3s ease;
        }

        .dark .form-container {
            background-color: #1e293b;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }

        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .dark .form-container:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        .logo {
            width: 180px;
            margin-bottom: 2rem;
            filter: brightness(0) invert(1);
        }

        .form-input {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            background-color: white;
            color: #1a1a1a;
        }

        .dark .form-input {
            border-color: #334155;
            background-color: #1e293b;
            color: #e2e8f0;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
        }

        .dark .password-toggle {
            color: #94a3b8;
        }

        .btn-login {
            transition: all 0.3s ease;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4), 0 2px 4px -1px rgba(59, 130, 246, 0.2);
        }

        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: background-color 0.3s;
        }

        .theme-toggle:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-image {
                min-height: 40vh;
                padding: 1.5rem;
            }

            .login-form {
                min-height: 60vh;
                padding: 1.5rem;
            }

            .form-container {
                padding: 2rem;
                margin-top: -60px;
            }
        }
    </style>
</head>

<body class="bg-gray-100 dark:bg-dark-900 text-gray-800 dark:text-gray-200 transition-colors duration-200">
    <div class="login-container">
        <!-- Imagem full à esquerda -->
        <div class="login-image">
            <button class="theme-toggle" id="themeToggle">
                <i class="fas <?= $currentTheme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
            </button>

            <img src="<?= BASE_URL ?>assets/images/rje.png" alt="Logo" class="logo">

            <div class="text-center max-w-md">
                <h2 class="text-3xl font-bold mb-4">Sistema de Avaliações Físicas</h2>
                <p class="text-xl opacity-90">Acompanhamento completo para profissionais de educação física e seus
                    clientes</p>
            </div>

            <div class="absolute bottom-6 left-6 text-sm opacity-80">
                <p>© <?= date('Y') ?> RJE Academia. Todos os direitos reservados.</p>
            </div>
        </div>

        <!-- Formulário de login à direita -->
        <div class="login-form">
            <div class="form-container">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">Bem-vindo de volta</h1>
                    <p class="text-gray-600 dark:text-gray-400">Entre com suas credenciais para acessar o sistema</p>
                </div>

                <?php if ($error): ?>
                    <div
                        class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-200 p-4 rounded mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="username"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Usuário</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="text" id="username" name="username" required
                                class="form-input pl-10 pr-4 py-3 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Digite seu usuário">
                        </div>
                    </div>

                    <div>
                        <label for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Senha</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="form-input pl-10 pr-10 py-3 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Digite sua senha">
                            <div class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye-slash" id="eyeIcon"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn-login w-full py-3 px-4 rounded-lg text-white font-medium">
                            Entrar no Sistema
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Sistema de Avaliações Físicas - Versão 1.8.1
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        // Mostra/oculta o ícone baseado no conteúdo do input
        passwordInput.addEventListener('input', function() {
            togglePassword.style.display = this.value.length > 0 ? 'block' : 'none';
        });

        // Alterna entre mostrar e ocultar a senha
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Alterna o ícone do olho
            eyeIcon.classList.toggle('fa-eye-slash');
            eyeIcon.classList.toggle('fa-eye');
        });

        // Toggle de tema
        document.getElementById('themeToggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            // Atualiza o tema via URL para que o PHP possa capturar
            window.location.href = `?theme=${newTheme}`;
        });

        // Inicializa a visibilidade do ícone de mostrar senha
        document.addEventListener('DOMContentLoaded', function() {
            togglePassword.style.display = passwordInput.value.length > 0 ? 'block' : 'none';
        });
    </script>
</body>

</html>