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
            // Atualizar os dados do cliente para exibição no formulário
            $cliente['nome'] = $nome;
            $cliente['email'] = $email;
            $cliente['telefone'] = $telefone;
            $cliente['data_nascimento'] = $dataNascimento;
            $cliente['sexo'] = $sexo;
            $cliente['altura'] = $altura;
            $cliente['observacoes'] = $observacoes;
        } else {
            throw new Exception('Erro ao atualizar cliente no banco de dados.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100">Editar Cliente</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Atualize as informações do cliente</p>
        </div>
        <a href="detalhes.php?id=<?= $clienteId ?>"
            class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg inline-flex items-center transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <?php if ($error): ?>
    <div
        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
        <i class="fas fa-exclamation-circle mr-3 mt-0.5"></i>
        <div><?= htmlspecialchars($error) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div
        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-start dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
        <i class="fas fa-check-circle mr-3 mt-0.5"></i>
        <div><?= htmlspecialchars($success) ?></div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
        <form method="POST" enctype="multipart/form-data" class="space-y-6" id="formEditarCliente">
            <!-- Foto do Perfil -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Foto do Perfil</h2>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <div class="relative">
                        <div
                            class="h-24 w-24 rounded-full overflow-hidden border-4 border-white shadow-lg dark:border-gray-700">
                            <?php if ($fotoAtual): ?>
                            <img id="previewFoto" src="../../<?= htmlspecialchars($fotoAtual) ?>" alt="Foto atual"
                                class="h-full w-full object-cover">
                            <?php else: ?>
                            <div id="previewFoto"
                                class="h-full w-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 dark:text-gray-500 text-3xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <label for="foto"
                            class="absolute bottom-0 right-0 bg-blue-600 text-white p-1.5 rounded-full shadow-md cursor-pointer hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-camera text-xs"></i>
                            <input type="file" id="foto" name="foto" accept="image/*" class="hidden">
                        </label>
                    </div>

                    <div class="flex-1 space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">JPEG, PNG ou GIF (Máx. 2MB)</p>
                            <div id="fileName" class="text-sm font-medium text-gray-700 dark:text-gray-300 hidden">
                            </div>
                        </div>

                        <?php if ($fotoAtual): ?>
                        <div class="flex items-center">
                            <input id="remover_foto" name="remover_foto" type="checkbox" value="1"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                            <label for="remover_foto" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Remover foto atual
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Dados Pessoais -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Dados Pessoais</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome
                            Completo *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?= htmlspecialchars($cliente['nome']) ?>" placeholder="Digite o nome completo">
                    </div>

                    <div>
                        <label for="email"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail</label>
                        <input type="email" id="email" name="email"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" placeholder="exemplo@email.com">
                    </div>

                    <div>
                        <label for="telefone"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                        <input type="tel" id="telefone" name="telefone"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                    </div>

                    <div>
                        <label for="data_nascimento"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data de
                            Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?= htmlspecialchars($cliente['data_nascimento'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="sexo"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sexo</label>
                        <select id="sexo" name="sexo"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
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
                        <label for="altura"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Altura (cm)</label>
                        <input type="number" id="altura" name="altura" step="0.01" min="0"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?= htmlspecialchars($cliente['altura'] ?? '') ?>" placeholder="Ex: 175.5">
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Observações</h2>
                <div>
                    <label for="observacoes"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Anotações sobre o
                        cliente</label>
                    <textarea id="observacoes" name="observacoes" rows="4"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Digite observações relevantes sobre o cliente"><?= htmlspecialchars($cliente['observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4">
                <a href="detalhes.php?id=<?= $clienteId ?>"
                    class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200">
                    Cancelar
                </a>
                <button type="submit" id="submitButton"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                    <i class="fas fa-save mr-2"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-800 dark:text-gray-200">Salvando alterações...</p>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEditarCliente');
    const submitButton = document.getElementById('submitButton');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const fileInput = document.getElementById('foto');
    const fileName = document.getElementById('fileName');
    const removerFoto = document.getElementById('remover_foto');

    // Preview da imagem selecionada
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Mostrar nome do arquivo
            fileName.textContent = file.name;
            fileName.classList.remove('hidden');

            // Validar tamanho do arquivo
            if (file.size > 2 * 1024 * 1024) {
                alert('O tamanho da imagem não pode exceder 2MB.');
                fileInput.value = '';
                fileName.classList.add('hidden');
                return;
            }

            // Validar tipo do arquivo
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Tipo de arquivo não permitido. Use apenas JPEG, PNG ou GIF.');
                fileInput.value = '';
                fileName.classList.add('hidden');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('previewFoto');

                // Se for uma div, transformar em img
                if (preview.tagName.toLowerCase() === 'div') {
                    const img = document.createElement('img');
                    img.id = 'previewFoto';
                    img.className = 'h-full w-full object-cover';
                    img.src = e.target.result;
                    img.alt = 'Preview da foto';

                    preview.parentNode.replaceChild(img, preview);
                } else {
                    preview.src = e.target.result;
                }

                // Desmarcar a opção de remover foto se estiver marcada
                if (removerFoto) {
                    removerFoto.checked = false;
                }
            }
            reader.readAsDataURL(file);
        }
    });

    // Se marcar para remover foto, pedir confirmação
    if (removerFoto) {
        removerFoto.addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('Tem certeza que deseja remover a foto deste cliente?')) {
                    this.checked = false;
                    return;
                }

                const previewContainer = document.getElementById('previewFoto').parentNode;
                const div = document.createElement('div');
                div.id = 'previewFoto';
                div.className =
                    'h-full w-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center';

                const icon = document.createElement('i');
                icon.className = 'fas fa-user text-gray-400 dark:text-gray-500 text-3xl';
                div.appendChild(icon);

                previewContainer.parentNode.replaceChild(div, previewContainer);

                // Limpar o input de arquivo
                fileInput.value = '';
                fileName.classList.add('hidden');
            }
        });
    }

    // Mostrar loading ao enviar formulário
    form.addEventListener('submit', function() {
        loadingOverlay.classList.remove('hidden');
        submitButton.disabled = true;
    });

    // Inicializar máscara de telefone
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 0) {
                if (value.length <= 2) {
                    value = value.replace(/^(\d{0,2})/, '($1');
                } else if (value.length <= 7) {
                    value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
                } else {
                    value = value.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                }
            }

            e.target.value = value;
        });
    }
});
</script>