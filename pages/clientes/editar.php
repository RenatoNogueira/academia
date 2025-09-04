<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID de cliente
if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$clienteId = $_GET['id'];
$currentUserId = getCurrentUserId();

// Obter dados atuais do cliente
$stmt = $db->prepare("
    SELECT * FROM clientes
    WHERE id = :id AND usuario_id = :usuario_id
");
$stmt->bindValue(':id', $clienteId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();
$cliente = $result->fetchArray(SQLITE3_ASSOC);

if (!$cliente) {
    header('Location: listar.php');
    exit();
}

$error = '';
$success = '';
$fotoAtual = $cliente['foto'];

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $telefone = trim($_POST['telefone']);
        $dataNascimento = trim($_POST['data_nascimento']);
        $sexo = trim($_POST['sexo']);
        $altura = trim($_POST['altura']);
        $observacoes = trim($_POST['observacoes']);

        // Validações básicas
        if (empty($nome)) {
            throw new Exception('O nome é obrigatório.');
        }

        if (!empty($email) && !validarEmail($email)) {
            throw new Exception('O e-mail informado não é válido.');
        }

        if (!empty($dataNascimento) && !strtotime($dataNascimento)) {
            throw new Exception('Data de nascimento inválida.');
        }

        if (!empty($altura) && (!is_numeric($altura) || $altura <= 0)) {
            throw new Exception('A altura deve ser um número positivo.');
        }

        // Processar upload da nova foto (se fornecida)
        $novaFoto = $fotoAtual;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Validar a imagem
            $tipoPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
            $tipoArquivo = mime_content_type($_FILES['foto']['tmp_name']);

            if (!in_array($tipoArquivo, $tipoPermitidos)) {
                throw new Exception('Tipo de arquivo não permitido. Use apenas JPEG, PNG ou GIF.');
            }

            // Limitar tamanho do arquivo (2MB)
            if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                throw new Exception('O tamanho da imagem não pode exceder 2MB.');
            }

            // Criar diretório se não existir
            $uploadDir = '../../uploads/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Gerar nome único para o arquivo
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nomeArquivo = uniqid() . '.' . $extensao;
            $caminhoCompleto = $uploadDir . $nomeArquivo;

            // Mover o arquivo
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
                $novaFoto = 'uploads/profile/' . $nomeArquivo;

                // Remover a foto antiga (se existir e for diferente da nova)
                if ($fotoAtual && $fotoAtual !== $novaFoto && file_exists('../../' . $fotoAtual)) {
                    unlink('../../' . $fotoAtual);
                }
            } else {
                throw new Exception('Erro ao fazer upload da imagem. Tente novamente.');
            }
        }

        // Se a opção para remover a foto foi marcada
        if (isset($_POST['remover_foto']) && $_POST['remover_foto'] === '1') {
            if ($fotoAtual && file_exists('../../' . $fotoAtual)) {
                unlink('../../' . $fotoAtual);
            }
            $novaFoto = null;
        }

        // Atualizar no banco de dados
        $stmt = $db->prepare("
            UPDATE clientes
            SET nome = :nome,
                email = :email,
                telefone = :telefone,
                data_nascimento = :data_nascimento,
                sexo = :sexo,
                altura = :altura,
                observacoes = :observacoes,
                foto = :foto
            WHERE id = :id
        ");

        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':telefone', $telefone);
        $stmt->bindValue(':data_nascimento', !empty($dataNascimento) ? $dataNascimento : null);
        $stmt->bindValue(':sexo', $sexo);
        $stmt->bindValue(':altura', !empty($altura) ? $altura : null);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':foto', $novaFoto);
        $stmt->bindValue(':id', $clienteId);

        if ($stmt->execute()) {
            $success = 'Cliente atualizado com sucesso!';
            // Atualizar a foto atual para exibição
            $fotoAtual = $novaFoto;
        } else {
            throw new Exception('Erro ao atualizar cliente no banco de dados.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Editar Cliente</h1>
        <a href="detalhes.php?id=<?= $clienteId ?>"
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Foto do Perfil -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto do Perfil</label>
                    <div class="flex items-center space-x-6">
                        <div class="shrink-0">
                            <?php if ($fotoAtual): ?>
                            <img id="previewFoto" src="../../<?= htmlspecialchars($fotoAtual) ?>" alt="Foto atual"
                                class="h-20 w-20 rounded-full object-cover">
                            <?php else: ?>
                            <div id="previewFoto"
                                class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <label class="block">
                                <span class="sr-only">Escolher foto</span>
                                <input type="file" id="foto" name="foto" accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </label>
                            <p class="mt-1 text-xs text-gray-500">JPEG, PNG ou GIF (Máx. 2MB)</p>

                            <?php if ($fotoAtual): ?>
                            <div class="mt-2 flex items-center">
                                <input id="remover_foto" name="remover_foto" type="checkbox" value="1"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remover_foto" class="ml-2 block text-sm text-gray-700">
                                    Remover foto atual
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Dados Pessoais -->
                <div>
                    <label for="nome" class="block text-sm font-medium text-gray-700">Nome Completo *</label>
                    <input type="text" id="nome" name="nome" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?= htmlspecialchars($cliente['nome']) ?>">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" id="email" name="email"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                </div>

                <div>
                    <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="tel" id="telefone" name="telefone"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>">
                </div>

                <div>
                    <label for="data_nascimento" class="block text-sm font-medium text-gray-700">Data de
                        Nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?= htmlspecialchars($cliente['data_nascimento'] ?? '') ?>">
                </div>

                <div>
                    <label for="sexo" class="block text-sm font-medium text-gray-700">Sexo</label>
                    <select id="sexo" name="sexo"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione</option>
                        <option value="masculino" <?= ($cliente['sexo'] ?? '') === 'masculino' ? 'selected' : '' ?>>
                            Masculino</option>
                        <option value="feminino" <?= ($cliente['sexo'] ?? '') === 'feminino' ? 'selected' : '' ?>>
                            Feminino</option>
                        <option value="outro" <?= ($cliente['sexo'] ?? '') === 'outro' ? 'selected' : '' ?>>Outro
                        </option>
                    </select>
                </div>

                <div>
                    <label for="altura" class="block text-sm font-medium text-gray-700">Altura (cm)</label>
                    <input type="number" id="altura" name="altura" step="0.01" min="0"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?= htmlspecialchars($cliente['altura'] ?? '') ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($cliente['observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
<script>
// Preview da imagem selecionada
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('previewFoto');

            // Se for uma div, transformar em img
            if (preview.tagName.toLowerCase() === 'div') {
                const img = document.createElement('img');
                img.id = 'previewFoto';
                img.className = 'h-20 w-20 rounded-full object-cover';
                img.src = e.target.result;
                img.alt = 'Preview da foto';

                preview.parentNode.replaceChild(img, preview);
            } else {
                preview.src = e.target.result;
            }

            // Desmarcar a opção de remover foto se estiver marcada
            const removerFoto = document.getElementById('remover_foto');
            if (removerFoto) {
                removerFoto.checked = false;
            }
        }
        reader.readAsDataURL(file);
    }
});

// Se marcar para remover foto, limpar o preview
const removerFoto = document.getElementById('remover_foto');
if (removerFoto) {
    removerFoto.addEventListener('change', function() {
        if (this.checked) {
            const previewContainer = document.getElementById('previewFoto').parentNode;
            const div = document.createElement('div');
            div.id = 'previewFoto';
            div.className = 'h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center';

            const icon = document.createElement('i');
            icon.className = 'fas fa-user text-gray-400 text-2xl';
            div.appendChild(icon);

            previewContainer.parentNode.replaceChild(div, previewContainer);

            // Limpar o input de arquivo
            document.getElementById('foto').value = '';
        }
    });
}
</script>
</body>

</html>