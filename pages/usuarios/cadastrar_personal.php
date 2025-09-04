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

<!-- Script para mostrar alertas -->
<?php if ($success): ?>
<script>
alert('<?= addslashes($success) ?>');
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
alert('Erro: <?= addslashes($error) ?>');
</script>
<?php endif; ?>

<div class="max-w-4xl mx-auto mt-5">
    <h1 class="text-2xl font-bold mb-4">Gerenciar Usuários</h1>

    <?php if ($error): ?>
    <div id="errorAlert" class="alert-auto-close bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div id="successAlert" class="alert-auto-close bg-green-100 text-green-700 px-4 py-2 rounded mb-4">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="text-lg font-semibold mb-4"><?= $usuarioEdicao ? 'Editar Usuário' : 'Novo Usuário' ?></h2>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($usuarioEdicao): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($usuarioEdicao['id']) ?>">
            <input type="hidden" name="foto_atual" value="<?= htmlspecialchars($usuarioEdicao['foto'] ?? '') ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium">Username</label>
                    <input type="text" name="username" id="username" class="w-full border px-3 py-2 rounded" required
                        value="<?= htmlspecialchars($usuarioEdicao['username'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium">Nome</label>
                    <input type="text" name="nome" id="nome" class="w-full border px-3 py-2 rounded" required
                        value="<?= htmlspecialchars($usuarioEdicao['nome'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input type="email" name="email" id="email" class="w-full border px-3 py-2 rounded" required
                        value="<?= htmlspecialchars($usuarioEdicao['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="telefone" class="block text-sm font-medium">Telefone</label>
                    <input type="text" name="telefone" id="telefone" class="w-full border px-3 py-2 rounded"
                        value="<?= htmlspecialchars($usuarioEdicao['telefone'] ?? '') ?>">
                </div>

                <div class="relative">
                    <div class="mb-4">
                        <label for="senha" class="block text-sm font-medium text-gray-700">
                            Senha
                            <?= isset($usuarioEdicao) && $usuarioEdicao ? '(Deixe em branco para manter)' : '' ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="senha" id="password"
                                class="w-full border px-3 py-2 rounded pr-10"
                                <?= isset($usuarioEdicao) && $usuarioEdicao ? '' : 'required' ?>>
                            <div id="togglePassword"
                                class="absolute inset-y-0 mt-2 right-0 pr-3 flex items-center cursor-pointer hidden"
                                style="bottom: <?= isset($usuarioEdicao) && $usuarioEdicao ? '10px' : '25px' ?>">
                                <i class="fas fa-eye-slash text-gray-400 hover:text-gray-500 mt-4" id="eyeIcon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="especialidade" class="block text-sm font-medium">Especialidade</label>
                    <input type="text" name="especialidade" id="especialidade" class="w-full border px-3 py-2 rounded"
                        value="<?= htmlspecialchars($usuarioEdicao['especialidade'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="foto" class="block text-sm font-medium">Foto</label>
                <input type="file" name="foto" id="foto" class="w-full border px-3 py-2 rounded">
                <?php if ($usuarioEdicao && !empty($usuarioEdicao['foto'])): ?>
                <div class="mt-2">
                    <img src="../../uploads/<?= htmlspecialchars($usuarioEdicao['foto']) ?>" alt="Foto do usuário"
                        class="h-20">
                    <p class="text-sm text-gray-500">Foto atual</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="cargo" class="block text-sm font-medium">Cargo</label>
                <select name="cargo" id="cargo" class="w-full border px-3 py-2 rounded">
                    <option value="profissional" selected>Profissional</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                <?= $usuarioEdicao ? 'Atualizar' : 'Cadastrar' ?>
            </button>
        </form>
    </div>

    <!-- Lista de usuários -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold mb-4">Usuários Cadastrados</h2>
        <?php if (!empty($usuarios)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left">Foto</th>
                        <th class="px-4 py-2 text-left">Nome</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Telefone</th>
                        <th class="px-4 py-2 text-left">Especialidade</th>
                        <th class="px-4 py-2 text-left">Cargo</th>
                        <th class="px-4 py-2 text-left">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td class="px-4 py-2">
                            <?php if (!empty($usuario['foto'])): ?>
                            <img src="../../uploads/<?= htmlspecialchars($usuario['foto']) ?>" alt="Foto"
                                class="h-10 rounded-full">
                            <?php else: ?>
                            <div class="h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2"><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($usuario['email']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($usuario['telefone']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($usuario['especialidade']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($usuario['cargo']) ?></td>
                        <td class="px-4 py-2">
                            <a href="?action=edit&id=<?= $usuario['id'] ?>"
                                class="text-yellow-600 hover:text-yellow-900 mr-2" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?= $usuario['id'] ?>"
                                onclick="return confirm('Deseja excluir este usuário?')"
                                class="text-red-600 hover:text-red-900" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p>Nenhum usuário cadastrado.</p>
        <?php endif; ?>
    </div>

</div>
<div class="max-w-4xl mx-auto mt-5 mb-5">
    <a href="<?= BASE_URL ?>index.php" class="bg-red-600 text-white px-4 py-2 rounded inline-block">
        Voltar
    </a>
</div>

<?php
require_once '../../includes/footer.php'
?>
</body>

</html>