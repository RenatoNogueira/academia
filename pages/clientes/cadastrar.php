<?php
require_once './../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$error = '';
$success = '';

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

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Por favor, insira um email válido.');
        }

        // Upload da foto
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Validar tipo de arquivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($_FILES['foto']['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Apenas arquivos JPEG, PNG e GIF são permitidos.');
            }

            // Validar tamanho do arquivo (máx 2MB)
            if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                throw new Exception('O tamanho da imagem não pode exceder 2MB.');
            }

            $uploadDir = '../../uploads/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNome = uniqid() . '.' . $ext;
            $fotoPath = $uploadDir . $fotoNome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath)) {
                $foto = 'uploads/profile/' . $fotoNome;
            } else {
                throw new Exception('Erro ao fazer upload da imagem.');
            }
        }

        $stmt = $db->prepare("
            INSERT INTO clientes
            (nome, email, telefone, data_nascimento, sexo, altura, observacoes, foto, usuario_id)
            VALUES
            (:nome, :email, :telefone, :data_nascimento, :sexo, :altura, :observacoes, :foto, :usuario_id)
        ");

        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':telefone', $telefone);
        $stmt->bindValue(':data_nascimento', $dataNascimento);
        $stmt->bindValue(':sexo', $sexo);
        $stmt->bindValue(':altura', $altura);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':foto', $foto);
        $stmt->bindValue(':usuario_id', getCurrentUserId());

        if ($stmt->execute()) {
            $success = 'Cliente cadastrado com sucesso!';
            $_POST = []; // Limpar o formulário
        } else {
            $error = 'Erro ao cadastrar cliente.';
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
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100">Cadastrar Novo Cliente</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Preencha os dados abaixo para cadastrar um novo
                cliente</p>
        </div>
        <a href="../clientes/listar.php"
            class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg inline-flex items-center transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <?php if ($error): ?>
    <div
        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
        <i class="fas fa-exclamation-circle mr-3 mt-0.5"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div
        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-start dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
        <i class="fas fa-check-circle mr-3 mt-0.5"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
        <div class="ml-auto">
            <a href="cadastrar.php" class="text-green-800 dark:text-green-300 hover:underline text-sm">
                Cadastrar outro
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
        <form method="POST" enctype="multipart/form-data" class="space-y-6" id="clienteForm">
            <!-- Foto do Perfil -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Foto do Perfil</h2>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <div class="relative">
                        <div
                            class="h-24 w-24 rounded-full overflow-hidden border-4 border-white shadow-lg dark:border-gray-700">
                            <div id="previewFoto"
                                class="h-full w-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 dark:text-gray-500 text-3xl"></i>
                            </div>
                        </div>
                        <label for="foto"
                            class="absolute bottom-0 right-0 bg-blue-600 text-white p-1.5 rounded-full shadow-md cursor-pointer hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-camera text-xs"></i>
                            <input type="file" id="foto" name="foto" accept="image/*" class="hidden">
                        </label>
                    </div>

                    <div class="flex-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">JPEG, PNG ou GIF (Máx. 2MB)</p>
                        <div id="fileName" class="text-sm font-medium text-gray-700 dark:text-gray-300 hidden"></div>
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
                            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                            placeholder="Digite o nome completo">
                        <p id="nomeError" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden"></p>
                    </div>

                    <div>
                        <label for="email"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" id="email" name="email"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            placeholder="exemplo@email.com">
                        <p id="emailError" class="mt-1 text-sm text-red-600 dark:text-red-400 hidden"></p>
                    </div>

                    <div>
                        <label for="telefone"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone</label>
                        <input type="tel" id="telefone" name="telefone"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                            placeholder="(00) 00000-0000">
                    </div>

                    <div>
                        <label for="data_nascimento"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data de
                            Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="sexo"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sexo</label>
                        <select id="sexo" name="sexo"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="">Selecione</option>
                            <option value="masculino"
                                <?php echo (($_POST['sexo'] ?? '') === 'masculino' ? 'selected' : ''); ?>>Masculino
                            </option>
                            <option value="feminino"
                                <?php echo (($_POST['sexo'] ?? '') === 'feminino' ? 'selected' : ''); ?>>Feminino
                            </option>
                            <option value="outro" <?php echo (($_POST['sexo'] ?? '') === 'outro' ? 'selected' : ''); ?>>
                                Outro</option>
                        </select>
                    </div>

                    <div>
                        <label for="altura"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Altura (cm)</label>
                        <input type="number" id="altura" name="altura" step="0.01" min="0" max="300"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['altura'] ?? ''); ?>" placeholder="Ex: 175.5">
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
                        placeholder="Digite observações relevantes sobre o cliente"><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4">
                <a href="../clientes/listar.php"
                    class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200">
                    Cancelar
                </a>
                <button type="submit" id="submitButton"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                    <i class="fas fa-save mr-2"></i> Cadastrar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-800 dark:text-gray-200">Cadastrando cliente...</p>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clienteForm');
    const submitButton = document.getElementById('submitButton');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const fileInput = document.getElementById('foto');
    const fileName = document.getElementById('fileName');
    const previewFoto = document.getElementById('previewFoto');

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
                alert('Apenas arquivos JPEG, PNG e GIF são permitidos.');
                fileInput.value = '';
                fileName.classList.add('hidden');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                // Criar elemento de imagem para preview
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'h-full w-full object-cover';
                img.alt = 'Preview da foto';

                // Limpar preview anterior e adicionar nova imagem
                previewFoto.innerHTML = '';
                previewFoto.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    });

    // Validação em tempo real
    const nomeInput = document.getElementById('nome');
    const emailInput = document.getElementById('email');
    const nomeError = document.getElementById('nomeError');
    const emailError = document.getElementById('emailError');

    nomeInput.addEventListener('blur', function() {
        if (!this.value.trim()) {
            nomeError.textContent = 'O nome é obrigatório.';
            nomeError.classList.remove('hidden');
            this.classList.add('border-red-500');
        } else {
            nomeError.classList.add('hidden');
            this.classList.remove('border-red-500');
        }
    });

    emailInput.addEventListener('blur', function() {
        if (this.value && !this.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            emailError.textContent = 'Por favor, insira um email válido.';
            emailError.classList.remove('hidden');
            this.classList.add('border-red-500');
        } else {
            emailError.classList.add('hidden');
            this.classList.remove('border-red-500');
        }
    });

    // Máscara de telefone
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

    // Validação do formulário antes do envio
    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Validar nome
        if (!nomeInput.value.trim()) {
            nomeError.textContent = 'O nome é obrigatório.';
            nomeError.classList.remove('hidden');
            nomeInput.classList.add('border-red-500');
            isValid = false;
        }

        // Validar email
        if (emailInput.value && !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            emailError.textContent = 'Por favor, insira um email válido.';
            emailError.classList.remove('hidden');
            emailInput.classList.add('border-red-500');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Rolar para o primeiro erro
            const firstError = form.querySelector('.border-red-500');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        } else {
            // Mostrar loading
            loadingOverlay.classList.remove('hidden');
            submitButton.disabled = true;
        }
    });

    // Limpar erros ao digitar
    nomeInput.addEventListener('input', function() {
        if (this.value.trim()) {
            nomeError.classList.add('hidden');
            this.classList.remove('border-red-500');
        }
    });

    emailInput.addEventListener('input', function() {
        if (!this.value || this.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            emailError.classList.add('hidden');
            this.classList.remove('border-red-500');
        }
    });
});
</script>