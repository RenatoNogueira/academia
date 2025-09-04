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

?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Avaliações</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Sistema de Avaliações</h1>
                <p class="text-gray-600">Acesse sua conta</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Usuário</label>
                    <input type="text" id="username" name="username" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="relative">
                    <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 pr-10">
                        <div id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer hidden">
                            <i class="fas fa-eye-slash text-gray-400 hover:text-gray-500" id="eyeIcon"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Entrar
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                <p>Versão 1.0.0</p>
            </div>
        </div>
    </div>

    <script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    passwordInput.addEventListener('input', function() {
        // Mostra/oculta o ícone baseado no conteúdo do input
        togglePassword.style.display = this.value.length > 0 ? 'flex' : 'none';
    });

    togglePassword.addEventListener('click', function() {
        // Alterna entre mostrar e ocultar a senha
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Alterna o ícone do olho
        eyeIcon.classList.toggle('fa-eye-slash');
        eyeIcon.classList.toggle('fa-eye');
    });
    </script>
</body>

</html>