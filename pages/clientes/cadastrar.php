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

        // Upload da foto
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNome = uniqid() . '.' . $ext;
            $fotoPath = $uploadDir . $fotoNome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath)) {
                $foto = 'uploads/profile/' . $fotoNome;
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
        $error = 'Erro: ' . $e->getMessage();
    }
}
?>


<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Cadastrar Novo Cliente</h1>
            <a href="../clientes/listar.php"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input type="tel" id="telefone" name="telefone"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="data_nascimento" class="block text-sm font-medium text-gray-700">Data de
                            Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="sexo" class="block text-sm font-medium text-gray-700">Sexo</label>
                        <select id="sexo" name="sexo"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
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
                        <label for="altura" class="block text-sm font-medium text-gray-700">Altura (cm)</label>
                        <input type="number" id="altura" name="altura" step="0.01"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($_POST['altura'] ?? ''); ?>">
                    </div>

                    <div class="md:col-span-2">
                        <label for="observacoes" class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label for="foto" class="block text-sm font-medium text-gray-700">Foto do Perfil</label>
                        <input type="file" id="foto" name="foto"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-save mr-2"></i> Salvar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</body>

</html>