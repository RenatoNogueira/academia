<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Obter filtros
$filtroStatus = $_GET['status'] ?? 'todos';
$filtroData = $_GET['data'] ?? '';

// Construir a query base
$query = "
    SELECT ag.*, c.nome as cliente_nome, c.telefone as cliente_telefone
    FROM agendamentos ag
    JOIN clientes c ON ag.cliente_id = c.id
    WHERE ag.usuario_id = :usuario_id
";

// Aplicar filtros
$params = [':usuario_id' => getCurrentUserId()];

if ($filtroStatus !== 'todos') {
    $query .= " AND ag.status = :status";
    $params[':status'] = $filtroStatus;
}

if (!empty($filtroData)) {
    $query .= " AND date(ag.data_agendamento) = :data";
    $params[':data'] = $filtroData;
}

$query .= " ORDER BY ag.data_agendamento ASC";

// Preparar e executar a query
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$result = $stmt->execute();
$agendamentos = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $agendamentos[] = $row;
}

// Obter contagem por status para os filtros
$contagemStatus = [
    'todos' => 0,
    'pendente' => 0,
    'confirmado' => 0,
    'cancelado' => 0,
    'realizado' => 0
];

$stmt = $db->prepare("
    SELECT status, COUNT(*) as total
    FROM agendamentos
    WHERE usuario_id = :usuario_id
    GROUP BY status
");
$stmt->bindValue(':usuario_id', getCurrentUserId());
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $contagemStatus[$row['status']] = $row['total'];
    $contagemStatus['todos'] += $row['total'];
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">


    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Agendamentos</h1>
        <a href="criar.php"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Novo Agendamento
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="todos" <?= $filtroStatus === 'todos' ? 'selected' : '' ?>>Todos
                        (<?= $contagemStatus['todos'] ?>)</option>
                    <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>Pendentes
                        (<?= $contagemStatus['pendente'] ?>)</option>
                    <option value="confirmado" <?= $filtroStatus === 'confirmado' ? 'selected' : '' ?>>Confirmados
                        (<?= $contagemStatus['confirmado'] ?>)</option>
                    <option value="realizado" <?= $filtroStatus === 'realizado' ? 'selected' : '' ?>>Realizados
                        (<?= $contagemStatus['realizado'] ?>)</option>
                    <option value="cancelado" <?= $filtroStatus === 'cancelado' ? 'selected' : '' ?>>Cancelados
                        (<?= $contagemStatus['cancelado'] ?>)</option>
                </select>
            </div>

            <div>
                <label for="data" class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                <input type="text" id="data" name="data" value="<?= htmlspecialchars($filtroData) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 datepicker"
                    placeholder="Selecione uma data">
            </div>

            <div class="flex items-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
                <?php if ($filtroStatus !== 'todos' || !empty($filtroData)): ?>
                <a href="listar.php"
                    class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Limpar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Lista de Agendamentos -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($agendamentos)): ?>
        <div class="p-6 text-center text-gray-500">
            Nenhum agendamento encontrado com os filtros selecionados.
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($agendamentos as $agendamento): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($agendamento['cliente_nome']) ?></div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($agendamento['cliente_telefone']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?= formatarDataHora($agendamento['data_agendamento']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= htmlspecialchars($agendamento['tipo']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                    $statusClasses = [
                                        'pendente' => 'bg-yellow-100 text-yellow-800',
                                        'confirmado' => 'bg-blue-100 text-blue-800',
                                        'realizado' => 'bg-green-100 text-green-800',
                                        'cancelado' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusText = [
                                        'pendente' => 'Pendente',
                                        'confirmado' => 'Confirmado',
                                        'realizado' => 'Realizado',
                                        'cancelado' => 'Cancelado'
                                    ];
                                    ?>
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClasses[$agendamento['status']] ?>">
                                <?= $statusText[$agendamento['status']] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <?php if ($agendamento['status'] === 'pendente'): ?>
                                <a href="confirmar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-blue-600 hover:text-blue-900" title="Confirmar">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php endif; ?>

                                <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                <a href="cancelar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-red-600 hover:text-red-900" title="Cancelar"
                                    onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>

                                <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                <a href="realizar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-green-600 hover:text-green-900" title="Marcar como realizado">
                                    <i class="fas fa-check-double"></i>
                                </a>
                                <?php endif; ?>

                                <a href="editar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="excluir.php?id=<?= $agendamento['id'] ?>"
                                    class="text-red-600 hover:text-red-900" title="Excluir"
                                    onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
// Configuração do datepicker
flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',
    locale: 'pt',
    allowInput: true
});

// Atualizar a página quando o status for alterado
document.getElementById('status').addEventListener('change', function() {
    if (this.value !== '<?= $filtroStatus ?>') {
        this.form.submit();
    }
});
</script>
</body>

</html>