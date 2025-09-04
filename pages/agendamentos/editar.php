<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID
if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$agendamentoId = $_GET['id'];
$currentUserId = getCurrentUserId();

// Obter dados do agendamento
$stmt = $db->prepare("
    SELECT * FROM agendamentos
    WHERE id = :id AND usuario_id = :usuario_id
");
$stmt->bindValue(':id', $agendamentoId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();
$agendamento = $result->fetchArray(SQLITE3_ASSOC);

if (!$agendamento) {
    redirectWithMessage('listar.php', 'error', 'Agendamento não encontrado.');
}

// Obter lista de clientes
$clientes = [];
$result = $db->query("SELECT id, nome FROM clientes WHERE usuario_id = $currentUserId ORDER BY nome ASC");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $clientes[] = $row;
}

$error = '';
$success = '';

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clienteId = $_POST['cliente_id'];
        $dataAgendamento = $_POST['data_agendamento'];
        $tipo = $_POST['tipo'];
        $observacoes = trim($_POST['observacoes']);
        $status = $_POST['status'];

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

        if (empty($status)) {
            throw new Exception('Selecione um status');
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

        // Atualizar no banco de dados
        $stmt = $db->prepare("
            UPDATE agendamentos
            SET cliente_id = :cliente_id,
                data_agendamento = :data_agendamento,
                tipo = :tipo,
                observacoes = :observacoes,
                status = :status
            WHERE id = :id
        ");

        $stmt->bindValue(':cliente_id', $clienteId);
        $stmt->bindValue(':data_agendamento', $dataAgendamento);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $agendamentoId);

        if ($stmt->execute()) {
            $success = 'Agendamento atualizado com sucesso!';
        } else {
            $error = 'Erro ao atualizar agendamento. Tente novamente.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Editar Agendamento</h1>
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
                            <?= ($agendamento['cliente_id'] == $cliente['id'] || (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'])) ? 'selected' : '' ?>>
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
                            <?= ($agendamento['tipo'] === 'Avaliação Física' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Avaliação Física')) ? 'selected' : '' ?>>
                            Avaliação Física</option>
                        <option value="Retorno"
                            <?= ($agendamento['tipo'] === 'Retorno' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Retorno')) ? 'selected' : '' ?>>
                            Retorno</option>
                        <option value="Treino"
                            <?= ($agendamento['tipo'] === 'Treino' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Treino')) ? 'selected' : '' ?>>
                            Treino</option>
                        <option value="Consulta"
                            <?= ($agendamento['tipo'] === 'Consulta' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Consulta')) ? 'selected' : '' ?>>
                            Consulta</option>
                    </select>
                </div>

                <div>
                    <label for="data_agendamento" class="block text-sm font-medium text-gray-700">Data e Hora
                        *</label>
                    <input type="text" id="data_agendamento" name="data_agendamento" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 datetimepicker"
                        value="<?= isset($_POST['data_agendamento']) ? htmlspecialchars($_POST['data_agendamento']) : htmlspecialchars($agendamento['data_agendamento']) ?>">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                    <select id="status" name="status" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="pendente"
                            <?= ($agendamento['status'] === 'pendente' || (isset($_POST['status']) && $_POST['status'] === 'pendente')) ? 'selected' : '' ?>>
                            Pendente</option>
                        <option value="confirmado"
                            <?= ($agendamento['status'] === 'confirmado' || (isset($_POST['status']) && $_POST['status'] === 'confirmado')) ? 'selected' : '' ?>>
                            Confirmado</option>
                        <option value="realizado"
                            <?= ($agendamento['status'] === 'realizado' || (isset($_POST['status']) && $_POST['status'] === 'realizado')) ? 'selected' : '' ?>>
                            Realizado</option>
                        <option value="cancelado"
                            <?= ($agendamento['status'] === 'cancelado' || (isset($_POST['status']) && $_POST['status'] === 'cancelado')) ? 'selected' : '' ?>>
                            Cancelado</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : htmlspecialchars($agendamento['observacoes']) ?></textarea>
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
// Configuração do datetime picker
flatpickr('.datetimepicker', {
    enableTime: true,
    dateFormat: 'Y-m-d H:i',
    locale: 'pt',
    time_24hr: true,
    minuteIncrement: 15,
    allowInput: true
});
</script>
</body>

</html>