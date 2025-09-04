<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$error = '';
$success = '';

// Obter lista de clientes para o select
$clientes = [];
$result = $db->query("SELECT id, nome FROM clientes WHERE usuario_id = " . getCurrentUserId() . " ORDER BY nome ASC");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $clientes[] = $row;
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clienteId = $_POST['cliente_id'];
        $dataAgendamento = $_POST['data_agendamento'];
        $tipo = $_POST['tipo'];
        $observacoes = trim($_POST['observacoes']);

        // Validar dados
        if (empty($clienteId)) {

            throw new Exception('Selecione um cliente');
        }

        if (empty($dataAgendamento)) {
            throw new Exception('Informe a data e hora do agendamento');
        }

        if (empty($tipo)) {
            throw new Exception('Informe o tipo de agendamento');
        }

        // Verificar se o cliente pertence ao usuário
        $clienteValido = false;
        foreach ($clientes as $cliente) {
            if ($cliente['id'] == $clienteId) {
                $clienteValido = true;
                break;
            }
        }

        if (!$clienteValido) {
            throw new Exception('Cliente inválido');
        }

        // Inserir no banco de dados
        $stmt = $db->prepare("
            INSERT INTO agendamentos
            (cliente_id, data_agendamento, tipo, observacoes, status, usuario_id)
            VALUES
            (:cliente_id, :data_agendamento, :tipo, :observacoes, 'pendente', :usuario_id)
        ");

        $stmt->bindValue(':cliente_id', $clienteId);
        $stmt->bindValue(':data_agendamento', $dataAgendamento);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':usuario_id', getCurrentUserId());

        if ($stmt->execute()) {
            $success = 'Agendamento criado com sucesso!';

            // Limpar o formulário ou redirecionar
            if (isset($_POST['continuar'])) {
                $_POST = [];
            } else {
                header('Location: listar.php');
                exit();
            }
        } else {
            $error = 'Erro ao criar agendamento. Tente novamente.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>


<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Novo Agendamento</h1>
        <a href="listar.php"
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
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cliente_id" class="block text-sm font-medium text-gray-700">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>"
                            <?= isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Agendamento *</label>
                    <select id="tipo" name="tipo" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione o tipo</option>
                        <option value="Avaliação Física"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Avaliação Física' ? 'selected' : '' ?>>
                            Avaliação Física</option>
                        <option value="Retorno"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Retorno' ? 'selected' : '' ?>>Retorno
                        </option>
                        <option value="Treino"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Treino' ? 'selected' : '' ?>>Treino
                        </option>
                        <option value="Consulta"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Consulta' ? 'selected' : '' ?>>Consulta
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="data_agendamento" class="block text-sm font-medium text-gray-700">Data e Hora
                        *</label>
                    <input type="text" id="data_agendamento" name="data_agendamento" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 datetimepicker"
                        value="<?= isset($_POST['data_agendamento']) ? htmlspecialchars($_POST['data_agendamento']) : '' ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : '' ?></textarea>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <div class="flex items-center">
                    <input id="continuar" name="continuar" type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="continuar" class="ml-2 block text-sm text-gray-700">
                        Continuar adicionando após salvar
                    </label>
                </div>

                <div>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-save mr-2"></i> Salvar Agendamento
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../../includes/footer.php'
?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
// Configuração do datetime picker
flatpickr('.datetimepicker', {
    enableTime: true,
    dateFormat: 'Y-m-d H:i',
    locale: 'pt',
    minDate: 'today',
    time_24hr: true,
    minuteIncrement: 15,
    allowInput: true
});
</script>
</body>

</html>