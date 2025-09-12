<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
$error = '';
$success = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

$db = $database->getConnection();

// Excluir usuário
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Impede excluir o próprio usuário logado
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Você não pode excluir o próprio usuário logado.';
        header('Location: cadastrar_personal.php');
        exit();
    } else {
        // Primeiro obtém a foto para deletar do servidor
        $stmt = $db->prepare("SELECT foto FROM usuarios WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $usuario = $result->fetchArray(SQLITE3_ASSOC);

        if ($usuario && !empty($usuario['foto'])) {
            $fotoPath = '../../uploads/' . $usuario['foto'];
            if (file_exists($fotoPath)) {
                unlink($fotoPath);
            }
        }

        // Depois deleta o usuário
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $_SESSION['success'] = 'Usuário excluído com sucesso.';
        header('Location: cadastrar_personal.php');
        exit();
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = trim($_POST['username']);
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $cargo = trim($_POST['cargo']);
    $telefone = trim($_POST['telefone']);
    $especialidade = trim($_POST['especialidade']);
    $fotoAtual = trim($_POST['foto_atual'] ?? '');

    // Validações básicas
    if (empty($nome) || empty($email) || empty($username) || ($id === 0 && empty($senha))) {
        $error = 'Username, nome, email e senha são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } else {
        // Verifica se email ou username já existem
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE (email = :email OR username = :username) AND id != :id");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if ($result->fetchArray(SQLITE3_ASSOC)) {
            $error = 'Email ou Username já está em uso.';
        } else {
            // Processar upload da foto
            $foto = $fotoAtual; // Mantém a foto atual por padrão

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Gera nome único para o arquivo
                $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nomeArquivo = uniqid() . '.' . $extensao;
                $uploadFile = $uploadDir . $nomeArquivo;

                // Tipos de arquivo permitidos
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

                if (in_array($_FILES['foto']['type'], $allowedTypes)) {
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadFile)) {
                        // Remove a foto antiga se existir
                        if (!empty($fotoAtual)) {
                            $oldFile = $uploadDir . $fotoAtual;
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                        $foto = $nomeArquivo;
                    }
                } else {
                    $error = 'Tipo de arquivo não permitido. Use apenas JPEG, PNG ou GIF.';
                }
            }

            if (empty($error)) {
                $senhaHash = !empty($senha) ? password_hash($senha, PASSWORD_DEFAULT) : null;

                if ($id > 0) {
                    // Atualizar usuário existente
                    if ($senhaHash) {
                        $sql = "UPDATE usuarios SET
                                username = :username,
                                nome = :nome,
                                email = :email,
                                password = :senha,
                                telefone = :telefone,
                                especialidade = :especialidade,
                                foto = :foto
                                WHERE id = :id";
                    } else {
                        $sql = "UPDATE usuarios SET
                                username = :username,
                                nome = :nome,
                                email = :email,
                                telefone = :telefone,
                                especialidade = :especialidade,
                                foto = :foto
                                WHERE id = :id";
                    }

                    $stmt = $db->prepare($sql);
                    if ($senhaHash) {
                        $stmt->bindValue(':senha', $senhaHash, SQLITE3_TEXT);
                    }
                } else {
                    // Inserir novo usuário
                    $stmt = $db->prepare("INSERT INTO usuarios
                                        (username, password, nome, email, telefone, especialidade, foto)
                                        VALUES
                                        (:username, :senha, :nome, :email, :telefone, :especialidade, :foto)");
                    $stmt->bindValue(':senha', $senhaHash, SQLITE3_TEXT);
                }

                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $stmt->bindValue(':telefone', $telefone, SQLITE3_TEXT);
                $stmt->bindValue(':especialidade', $especialidade, SQLITE3_TEXT);
                $stmt->bindValue(':foto', $foto, SQLITE3_TEXT);

                if ($id > 0) {
                    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                }

                $stmt->execute();

                $_SESSION['success'] = $id > 0 ? 'Usuário atualizado com sucesso.' : 'Usuário cadastrado com sucesso.';
                header('Location: cadastrar_personal.php');
                exit();
            }
        }
    }
}

// Buscar usuários
$usuarios = [];
$stmt = $db->query("SELECT id, username, nome, email, telefone, especialidade, foto, 'profissional' as cargo, created_at as criado_em FROM usuarios ORDER BY id DESC");
while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
    $usuarios[] = $row;
}

// Editar usuário
$usuarioEdicao = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT id, username, nome, email, telefone, especialidade, foto FROM usuarios WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $usuarioEdicao = $result->fetchArray(SQLITE3_ASSOC);
}
?>

<?php include '../../includes/header.php'; ?>

<style>
    .alert-auto-close {
        transition: opacity 0.5s ease-out;
    }

    .fade-out {
        opacity: 0;
    }

    .preview-container {
        margin-top: 10px;
        display: none;
    }

    .preview-image {
        max-width: 150px;
        max-height: 150px;
        border-radius: 5px;
        border: 1px solid #e2e8f0;
    }

    .dark .preview-image {
        border-color: #334155;
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

    .form-input {
        transition: border-color 0.2s ease;
        background-color: white;
        color: #1a1a1a;
        border: 1px solid #e2e8f0;
    }

    .dark .form-input {
        background-color: #1e293b;
        color: #e2e8f0;
        border-color: #334155;
    }

    .form-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:hover {
        transform: scale(1.1);
    }

    /* Estilos específicos para a página de usuários */
    .user-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .user-table th {
        background-color: #f8fafc;
        padding: 1rem;
        font-weight: 600;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    .dark .user-table th {
        background-color: #1e293b;
        color: #94a3b8;
    }

    .user-table td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .dark .user-table td {
        border-bottom: 1px solid #334155;
    }

    .user-table tr:hover td {
        background-color: #f8fafc;
    }

    .dark .user-table tr:hover td {
        background-color: #1e293b;
    }
</style>

<!-- Script para mostrar alertas -->
<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alert = document.getElementById('successAlert');
                if (alert) {
                    alert.classList.add('fade-out');
                    setTimeout(() => alert.remove(), 500);
                }
            }, 3000);
        });
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alert = document.getElementById('errorAlert');
                if (alert) {
                    alert.classList.add('fade-out');
                    setTimeout(() => alert.remove(), 500);
                }
            }, 5000);
        });
    </script>
<?php endif; ?>

<div class="max-w-6xl mx-auto mt-5 px-4">
    <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-gray-200">Gerenciar Usuários</h1>

    <?php if ($error): ?>
        <div id="errorAlert"
            class="alert-auto-close bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-200 p-4 rounded mb-6 relative">
            <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            <button class="absolute top-0 right-0 p-2" onclick="this.parentElement.remove()">
                <i class="fas fa-times text-red-700 dark:text-red-200"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div id="successAlert"
            class="alert-auto-close bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-200 p-4 rounded mb-6 relative">
            <span class="block sm:inline"><?= htmlspecialchars($success) ?></span>
            <button class="absolute top-0 right-0 p-2" onclick="this.parentElement.remove()">
                <i class="fas fa-times text-green-700 dark:text-green-200"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card p-6 mb-8">
        <h2
            class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 border-b pb-2 border-gray-200 dark:border-gray-700">
            <i class="fas <?= $usuarioEdicao ? 'fa-user-edit' : 'fa-user-plus' ?> mr-2"></i>
            <?= $usuarioEdicao ? 'Editar Usuário' : 'Novo Usuário' ?>
        </h2>
        <form method="POST" enctype="multipart/form-data" id="userForm">
            <?php if ($usuarioEdicao): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($usuarioEdicao['id']) ?>">
                <input type="hidden" name="foto_atual" id="foto_atual"
                    value="<?= htmlspecialchars($usuarioEdicao['foto'] ?? '') ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="mb-4">
                    <label for="username"
                        class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Username *</label>
                    <input type="text" name="username" id="username"
                        class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required value="<?= htmlspecialchars($usuarioEdicao['username'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Nome
                        Completo *</label>
                    <input type="text" name="nome" id="nome"
                        class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required value="<?= htmlspecialchars($usuarioEdicao['nome'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Email
                        *</label>
                    <input type="email" name="email" id="email"
                        class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required value="<?= htmlspecialchars($usuarioEdicao['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="telefone"
                        class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Telefone</label>
                    <input type="text" name="telefone" id="telefone"
                        class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?= htmlspecialchars($usuarioEdicao['telefone'] ?? '') ?>" placeholder="(11) 99999-9999">
                </div>

                <div class="mb-4 relative">
                    <label for="password" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        Senha
                        <?= isset($usuarioEdicao) && $usuarioEdicao ? '<span class="text-xs text-gray-500 dark:text-gray-400">(Deixe em branco para manter a atual)</span>' : '*' ?>
                    </label>
                    <div class="relative">
                        <input type="password" name="senha" id="password"
                            class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                            <?= isset($usuarioEdicao) && $usuarioEdicao ? '' : 'required' ?>
                            autocomplete="new-password">
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye-slash" id="eyeIcon"></i>
                        </span>
                    </div>
                    <div class="text-xs mt-1 text-gray-500 dark:text-gray-400">Mínimo de 6 caracteres</div>
                </div>

                <div class="mb-4">
                    <label for="especialidade"
                        class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Especialidade</label>
                    <input type="text" name="especialidade" id="especialidade"
                        class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?= htmlspecialchars($usuarioEdicao['especialidade'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-6">
                <label for="foto" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Foto</label>
                <div class="flex items-center space-x-4">
                    <label for="foto"
                        class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-upload mr-2"></i>Selecionar Imagem
                    </label>
                    <input type="file" name="foto" id="foto" class="hidden" accept="image/jpeg,image/png,image/gif">
                    <span id="file-name" class="text-sm text-gray-500 dark:text-gray-400">Nenhum arquivo
                        selecionado</span>
                </div>

                <div class="preview-container mt-3" id="imagePreview">
                    <img src="" alt="Preview" class="preview-image">
                    <button type="button"
                        class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        onclick="clearImage()">
                        <i class="fas fa-times-circle"></i> Remover
                    </button>
                </div>

                <?php if ($usuarioEdicao && !empty($usuarioEdicao['foto'])): ?>
                    <div class="mt-3 flex items-center space-x-3">
                        <img src="../../uploads/<?= htmlspecialchars($usuarioEdicao['foto']) ?>" alt="Foto atual"
                            class="h-20 w-20 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Foto atual</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="cargo" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Cargo</label>
                <select name="cargo" id="cargo"
                    class="w-full px-4 py-2 rounded-lg form-input focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="profissional" selected>Profissional</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3">
                <?php if ($usuarioEdicao): ?>
                    <a href="cadastrar_personal.php"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-5 py-2.5 rounded-lg transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                <?php endif; ?>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg transition-colors flex items-center">
                    <i class="fas <?= $usuarioEdicao ? 'fa-save' : 'fa-plus' ?> mr-2"></i>
                    <?= $usuarioEdicao ? 'Atualizar' : 'Cadastrar' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de usuários -->
    <div class="card p-6">
        <h2
            class="text-xl font-semibold mb-6 text-gray-700 dark:text-gray-300 border-b pb-2 border-gray-200 dark:border-gray-700">
            <i class="fas fa-users mr-2"></i>Usuários Cadastrados
        </h2>
        <?php if (!empty($usuarios)): ?>
            <div class="overflow-x-auto rounded-lg shadow">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Especialidade</th>
                            <th>Cargo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td class="whitespace-nowrap">
                                    <?php if (!empty($usuario['foto'])): ?>
                                        <img src="../../uploads/<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto"
                                            class="h-10 w-10 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                                    <?php else: ?>
                                        <div
                                            class="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center border border-gray-200 dark:border-gray-600">
                                            <i class="fas fa-user text-gray-400 dark:text-gray-500"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                                    <?= htmlspecialchars($usuario['nome']) ?></td>
                                <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars($usuario['email']) ?></td>
                                <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars($usuario['telefone']) ?></td>
                                <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars($usuario['especialidade']) ?></td>
                                <td class="whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full
                                        <?= $usuario['cargo'] === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' ?>">
                                        <?= htmlspecialchars($usuario['cargo']) ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap text-sm font-medium">
                                    <a href="?action=edit&id=<?= $usuario['id'] ?>"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-3 action-btn"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $usuario['id'] ?>"
                                        onclick="return confirm('Tem certeza que deseja excluir o usuário <?= addslashes($usuario['nome']) ?>?')"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 action-btn"
                                        title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-user-slash text-4xl mb-3"></i>
                <p>Nenhum usuário cadastrado.</p>
            </div>
        <?php endif; ?>
    </div>

</div>
<div class="max-w-6xl mx-auto mt-8 mb-8 px-4">
    <a href="<?= BASE_URL ?>index.php"
        class="bg-gray-600 hover:bg-gray-700 text-white px-5 py-2.5 rounded-lg transition-colors inline-flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>Voltar
    </a>
</div>

<script>
    // Máscara de telefone
    document.addEventListener('DOMContentLoaded', function() {
        const telefoneInput = document.getElementById('telefone');

        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.length > 11) {
                value = value.slice(0, 11);
            }

            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else if (value.length > 0) {
                value = value.replace(/^(\d*)/, '($1');
            }

            e.target.value = value;
        });


        // Preview de imagem
        const fotoInput = document.getElementById('foto');
        const previewContainer = document.getElementById('imagePreview');
        const previewImage = previewContainer.querySelector('.preview-image');
        const fileName = document.getElementById('file-name');
        const fotoAtual = document.getElementById('foto_atual');

        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                fileName.textContent = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'flex';
                }
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Nenhum arquivo selecionado';
                previewContainer.style.display = 'none';
            }
        });

        // Se estiver editando e houver uma foto atual, mostra o nome do arquivo
        if (fotoAtual && fotoAtual.value) {
            fileName.textContent = 'Imagem atual mantida';
        }
    });

    function clearImage() {
        document.getElementById('foto').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('file-name').textContent = 'Nenhum arquivo selecionado';

        // Se estiver editando, marca para remover a foto atual
        const fotoAtual = document.getElementById('foto_atual');
        if (fotoAtual) {
            fotoAtual.value = '';
        }
    }
</script>

<?php
require_once '../../includes/footer.php'
?>