<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Função para calcular tempo decorrido (adicionada aqui)
function tempoDecorrido($data)
{
    $agora = new DateTime();
    $dataPassada = new DateTime($data);
    $diferenca = $agora->diff($dataPassada);

    if ($diferenca->y > 0) {
        return $diferenca->y . ' ano' . ($diferenca->y > 1 ? 's' : '') . ' atrás';
    } elseif ($diferenca->m > 0) {
        return $diferenca->m . ' mes' . ($diferenca->m > 1 ? 'es' : '') . ' atrás';
    } elseif ($diferenca->d > 0) {
        return $diferenca->d . ' dia' . ($diferenca->d > 1 ? 's' : '') . ' atrás';
    } elseif ($diferenca->h > 0) {
        return $diferenca->h . ' hora' . ($diferenca->h > 1 ? 's' : '') . ' atrás';
    } elseif ($diferenca->i > 0) {
        return $diferenca->i . ' minuto' . ($diferenca->i > 1 ? 's' : '') . ' atrás';
    } else {
        return 'Agora mesmo';
    }
}

// Função para formatar data e hora no padrão BR
function formatarDataHoraBR($dataHora)
{
    if (empty($dataHora)) return '';
    $data = new DateTime($dataHora);
    return $data->format('d/m/Y H:i');
}

// Função para formatar apenas data no padrão BR
function formatarDataBR($data)
{
    if (empty($data)) return '';
    $dataObj = new DateTime($data);
    return $dataObj->format('d/m/Y');
}

// Obter filtros
$filtroStatus = $_GET['status'] ?? 'todos';
$filtroData = $_GET['data'] ?? '';
$visualizacao = $_GET['view'] ?? 'tabela';

// Converter data BR para formato do banco (YYYY-MM-DD) se necessário
$filtroDataBanco = '';
if (!empty($filtroData)) {
    // Verificar se a data está no formato BR (DD/MM/YYYY)
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $filtroData, $matches)) {
        $filtroDataBanco = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    } else {
        $filtroDataBanco = $filtroData; // Assume que já está no formato YYYY-MM-DD
    }
}

// Construir a query base
$query = "
    SELECT ag.*, c.nome as cliente_nome, c.telefone as cliente_telefone, c.foto as cliente_foto
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

if (!empty($filtroDataBanco)) {
    $query .= " AND date(ag.data_agendamento) = :data";
    $params[':data'] = $filtroDataBanco;
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
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Agendamentos</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Gerencie os agendamentos dos seus clientes</p>
        </div>
        <div class="flex items-center space-x-4 mt-4 md:mt-0">
            <!-- Botão de tema móvel (opcional) -->
            <button onclick="toggleDarkMode()"
                class="md:hidden p-2 rounded-full text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i id="theme-icon-mobile" class="fas fa-moon"></i>
            </button>

            <a href="criar.php"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center transition-colors duration-200 shadow-md">
                <i class="fas fa-plus-circle mr-2"></i> Novo Agendamento
            </a>
        </div>
    </div>

    <!-- Filtros e Visualização -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-5 mb-6">
        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select id="status" name="status"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
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
                <label for="data" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data</label>
                <input type="text" id="data" name="data" value="<?= htmlspecialchars($filtroData) ?>"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white datepicker"
                    placeholder="DD/MM/AAAA">
            </div>

            <div>
                <label for="view"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visualização</label>
                <select id="view" name="view"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
                    <option value="tabela" <?= $visualizacao === 'tabela' ? 'selected' : '' ?>>Visualização em Tabela
                    </option>
                    <option value="cards" <?= $visualizacao === 'cards' ? 'selected' : '' ?>>Visualização em Cards
                    </option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-filter mr-2"></i> Filtrar
                </button>
                <?php if ($filtroStatus !== 'todos' || !empty($filtroData)): ?>
                <a href="listar.php"
                    class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-medium py-2.5 px-4 rounded-lg inline-flex items-center justify-center transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i> Limpar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Resumo Rápido -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Cartões de status com classes para modo escuro -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pendentes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $contagemStatus['pendente'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Confirmados</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $contagemStatus['confirmado'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-check-double text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Realizados</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $contagemStatus['realizado'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 p-3 rounded-lg">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Cancelados</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $contagemStatus['cancelado'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Agendamentos -->
    <?php if (empty($agendamentos)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-8 text-center">
        <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-calendar-day text-gray-400 text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum agendamento encontrado</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">Tente ajustar os filtros ou criar um novo agendamento.</p>
        <a href="criar.php"
            class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
            <i class="fas fa-plus-circle mr-2"></i> Criar primeiro agendamento
        </a>
    </div>
    <?php else: ?>
    <?php if ($visualizacao === 'tabela'): ?>
    <!-- Visualização em Tabela com classes para modo escuro -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Data/Hora
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($agendamentos as $agendamento):
                                $dataAgendamento = new DateTime($agendamento['data_agendamento']);
                                $agora = new DateTime();
                                $diferenca = $agora->diff($dataAgendamento);
                                $horasRestantes = $diferenca->h + ($diferenca->days * 24);
                                $proximo = ($horasRestantes <= 24 && $horasRestantes > 0 && $agendamento['status'] === 'confirmado');
                            ?>
                    <tr class="<?= $proximo ? 'bg-blue-50 dark:bg-blue-900' : '' ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <?php if ($agendamento['cliente_foto']): ?>
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full object-cover"
                                        src="../../<?= htmlspecialchars($agendamento['cliente_foto']) ?>"
                                        alt="<?= htmlspecialchars($agendamento['cliente_nome']) ?>">
                                </div>
                                <?php else: ?>
                                <div
                                    class="flex-shrink-0 h-10 w-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <?php endif; ?>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($agendamento['cliente_nome']) ?>
                                        <?php if ($proximo): ?>
                                        <span
                                            class="ml-2 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100 text-xs font-medium px-2 py-0.5 rounded-full">Próximo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($agendamento['cliente_telefone']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= formatarDataHoraBR($agendamento['data_agendamento']) ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-white">
                                <?= tempoDecorrido($agendamento['data_agendamento']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <?= htmlspecialchars($agendamento['tipo']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                        $statusClasses = [
                                            'pendente' => 'bg-yellow-100 text-yellow-800',
                                            'confirmado' => 'bg-blue-100 text-blue-800',
                                            'realizado' => 'bg-green-100 text-green-800',
                                            'cancelado' => 'bg-red-100 text-red-800'
                                        ];
                                        $statusIcons = [
                                            'pendente' => 'clock',
                                            'confirmado' => 'check-circle',
                                            'realizado' => 'check-double',
                                            'cancelado' => 'times-circle'
                                        ];
                                        $statusText = [
                                            'pendente' => 'Pendente',
                                            'confirmado' => 'Confirmado',
                                            'realizado' => 'Realizado',
                                            'cancelado' => 'Cancelado'
                                        ];
                                        ?>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClasses[$agendamento['status']] ?>">
                                <i class="fas fa-<?= $statusIcons[$agendamento['status']] ?> mr-1"></i>
                                <?= $statusText[$agendamento['status']] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <?php if ($agendamento['status'] === 'pendente'): ?>
                                <a href="confirmar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50 transition-colors duration-200"
                                    title="Confirmar">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                                <?php endif; ?>

                                <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                <a href="cancelar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                                    title="Cancelar"
                                    onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                                <?php endif; ?>

                                <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                <a href="realizar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50 transition-colors duration-200"
                                    title="Marcar como realizado">
                                    <i class="fas fa-check-double"></i>
                                </a>
                                <?php endif; ?>

                                <a href="editar.php?id=<?= $agendamento['id'] ?>"
                                    class="text-yellow-600 hover:text-yellow-900 p-1 rounded-md hover:bg-yellow-50 transition-colors duration-200"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <a href="excluir.php?id=<?= $agendamento['id'] ?>"
                                    class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                                    title="Excluir"
                                    onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <!-- Visualização em Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($agendamentos as $agendamento):
                    $dataAgendamento = new DateTime($agendamento['data_agendamento']);
                    $agora = new DateTime();
                    $diferenca = $agora->diff($dataAgendamento);
                    $horasRestantes = $diferenca->h + ($diferenca->days * 24);
                    $proximo = ($horasRestantes <= 24 && $horasRestantes > 0 && $agendamento['status'] === 'confirmado');

                    $statusClasses = [
                        'pendente' => 'border-yellow-400',
                        'confirmado' => 'border-blue-400',
                        'realizado' => 'border-green-400',
                        'cancelado' => 'border-red-400'
                    ];

                    $statusColors = [
                        'pendente' => 'yellow',
                        'confirmado' => 'blue',
                        'realizado' => 'green',
                        'cancelado' => 'red'
                    ];
                ?>
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-md border-l-4 <?= $statusClasses[$agendamento['status']] ?> overflow-hidden">
            <div class="p-5">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $statusColors[$agendamento['status']] ?>-100 text-<?= $statusColors[$agendamento['status']] ?>-800">
                            <i class="fas fa-<?= $statusIcons[$agendamento['status']] ?> mr-1"></i>
                            <?= $statusText[$agendamento['status']] ?>
                        </span>
                        <?php if ($proximo): ?>
                        <span
                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-bell mr-1"></i> Próximo
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-2xl font-bold text-gray-300"><?= $dataAgendamento->format('d') ?></div>
                </div>

                <div class="flex items-center mb-4">
                    <?php if ($agendamento['cliente_foto']): ?>
                    <div class="flex-shrink-0 h-12 w-12">
                        <img class="h-12 w-12 rounded-full object-cover"
                            src="../../<?= htmlspecialchars($agendamento['cliente_foto']) ?>"
                            alt="<?= htmlspecialchars($agendamento['cliente_nome']) ?>">
                    </div>
                    <?php else: ?>
                    <div class="flex-shrink-0 h-12 w-12 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-400 text-xl"></i>
                    </div>
                    <?php endif; ?>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">
                            <?= htmlspecialchars($agendamento['cliente_nome']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($agendamento['cliente_telefone']) ?></p>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Data e Hora:</span>
                        <span
                            class="text-sm font-medium text-gray-900"><?= formatarDataHoraBR($agendamento['data_agendamento']) ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Tipo:</span>
                        <span
                            class="text-sm font-medium text-gray-900"><?= htmlspecialchars($agendamento['tipo']) ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-5 py-3">
                <div class="flex justify-between space-x-2">
                    <?php if ($agendamento['status'] === 'pendente'): ?>
                    <a href="confirmar.php?id=<?= $agendamento['id'] ?>"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200"
                        title="Confirmar">
                        <i class="fas fa-check-circle"></i>
                    </a>
                    <?php endif; ?>

                    <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                    <a href="realizar.php?id=<?= $agendamento['id'] ?>"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200"
                        title="Marcar como realizado">
                        <i class="fas fa-check-double"></i>
                    </a>
                    <?php endif; ?>

                    <a href="editar.php?id=<?= $agendamento['id'] ?>"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-center text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200"
                        title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>

                    <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                    <a href="cancelar.php?id=<?= $agendamento['id'] ?>"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white text-center text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200"
                        title="Cancelar" onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                        <i class="fas fa-times-circle"></i>
                    </a>
                    <?php else: ?>
                    <a href="excluir.php?id=<?= $agendamento['id'] ?>"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white text-center text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200"
                        title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
// Configuração do datepicker no formato BR
flatpickr('.datepicker', {
    dateFormat: 'd/m/Y',
    locale: 'pt',
    allowInput: true,
    disableMobile: true // Melhora a experiência em dispositivos móveis
});

// Atualizar a página quando o status for alterado
document.getElementById('status').addEventListener('change', function() {
    this.form.submit();
});

// Atualizar a página quando a visualização for alterada
document.getElementById('view').addEventListener('change', function() {
    this.form.submit();
});
</script>