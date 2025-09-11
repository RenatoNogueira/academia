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

// Obter dados do cliente
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

// Calcular idade
$idade = '';
if (!empty($cliente['data_nascimento'])) {
    $dataNasc = new DateTime($cliente['data_nascimento']);
    $hoje = new DateTime();
    $idade = $hoje->diff($dataNasc)->y;
}

// Obter avaliações recentes do cliente
$avaliacoes = [];
$stmt = $db->prepare("
    SELECT * FROM avaliacoes
    WHERE cliente_id = :cliente_id AND usuario_id = :usuario_id
    ORDER BY data_avaliacao DESC
    LIMIT 5
");
$stmt->bindValue(':cliente_id', $clienteId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $avaliacoes[] = $row;
}

// Obter agendamentos futuros do cliente
$agendamentos = [];
$stmt = $db->prepare("
    SELECT * FROM agendamentos
    WHERE cliente_id = :cliente_id AND usuario_id = :usuario_id
    AND status IN ('pendente', 'confirmado')
    AND datetime(data_agendamento) >= datetime('now')
    ORDER BY data_agendamento ASC
    LIMIT 5
");
$stmt->bindValue(':cliente_id', $clienteId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $agendamentos[] = $row;
}

// Obter metas do cliente
$metas = [];
$stmt = $db->prepare("
    SELECT * FROM metas
    WHERE cliente_id = :cliente_id AND usuario_id = :usuario_id
    ORDER BY data_limite ASC
");
$stmt->bindValue(':cliente_id', $clienteId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $metas[] = $row;
}

// Obter histórico de peso para o gráfico
$historicoPeso = [];
$stmt = $db->prepare("
    SELECT data_avaliacao, peso
    FROM avaliacoes
    WHERE cliente_id = :cliente_id AND usuario_id = :usuario_id
    ORDER BY data_avaliacao ASC
");
$stmt->bindValue(':cliente_id', $clienteId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $historicoPeso[] = $row;
}

// Preparar dados para o gráfico
$graficoLabels = [];
$graficoDados = [];

foreach ($historicoPeso as $avaliacao) {
    $graficoLabels[] = date('d/m/Y', strtotime($avaliacao['data_avaliacao']));
    $graficoDados[] = $avaliacao['peso'];
}

// Contar totais para os badges
$totalAvaliacoes = count($avaliacoes);
$totalAgendamentos = count($agendamentos);
$totalMetas = count($metas);
$metasConcluidas = array_filter($metas, function ($meta) {
    return $meta['concluida'];
});
$percentualConclusao = $totalMetas > 0 ? round(count($metasConcluidas) / $totalMetas * 100) : 0;
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100">Detalhes do Cliente</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Visualize e gerencie todas as informações do
                cliente</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="editar.php?id=<?= $clienteId ?>"
                class="bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center transition-colors duration-200">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <a href="listar.php"
                class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg inline-flex items-center transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <!-- Sidebar - Informações do Cliente -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
                <div class="flex flex-col items-center mb-6">
                    <?php if ($cliente['foto']): ?>
                    <img src="../../<?= htmlspecialchars($cliente['foto']) ?>" alt="Foto do cliente"
                        class="h-32 w-32 rounded-full object-cover mb-4 border-4 border-white shadow-lg dark:border-gray-700">
                    <?php else: ?>
                    <div
                        class="h-32 w-32 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center mb-4 border-4 border-white shadow-lg dark:border-gray-700">
                        <i class="fas fa-user text-gray-400 dark:text-gray-500 text-5xl"></i>
                    </div>
                    <?php endif; ?>

                    <h2 class="text-xl font-semibold text-center text-gray-800 dark:text-gray-100">
                        <?= htmlspecialchars($cliente['nome']) ?></h2>

                    <div class="mt-3 flex space-x-4">
                        <?php if (!empty($cliente['telefone'])): ?>
                        <a href="tel:<?= htmlspecialchars($cliente['telefone']) ?>"
                            class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                            title="Ligar">
                            <i class="fas fa-phone text-lg"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($cliente['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($cliente['email']) ?>"
                            class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                            title="Enviar e-mail">
                            <i class="fas fa-envelope text-lg"></i>
                        </a>
                        <?php endif; ?>

                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $cliente['telefone']) ?>?text=Olá <?= urlencode($cliente['nome']) ?>"
                            target="_blank"
                            class="text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200"
                            title="Enviar mensagem no WhatsApp">
                            <i class="fab fa-whatsapp text-lg"></i>
                        </a>
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            Informações Pessoais</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Idade</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    <?= $idade ? $idade . ' anos' : '--' ?></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Sexo</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    <?php
                                    switch ($cliente['sexo']) {
                                        case 'masculino':
                                            echo 'Masculino';
                                            break;
                                        case 'feminino':
                                            echo 'Feminino';
                                            break;
                                        case 'outro':
                                            echo 'Outro';
                                            break;
                                        default:
                                            echo '--';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Altura</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    <?= $cliente['altura'] ? $cliente['altura'] . ' cm' : '--' ?></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Data Nasc.</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    <?= $cliente['data_nascimento'] ? formatarData($cliente['data_nascimento']) : '--' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($cliente['observacoes'])): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Observações</h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                <?= htmlspecialchars($cliente['observacoes']) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            Ações Rápidas</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="../avaliacoes/registrar.php?cliente_id=<?= $clienteId ?>"
                                class="bg-emerald-500 hover:bg-emerald-600 text-white text-center text-sm font-medium py-2.5 px-3 rounded-lg flex flex-col items-center justify-center transition-colors duration-200">
                                <i class="fas fa-clipboard-check text-lg mb-1"></i>
                                <span class="text-xs">Avaliação</span>
                            </a>
                            <a href="../agendamentos/criar.php?cliente_id=<?= $clienteId ?>"
                                class="bg-blue-500 hover:bg-blue-600 text-white text-center text-sm font-medium py-2.5 px-3 rounded-lg flex flex-col items-center justify-center transition-colors duration-200">
                                <i class="fas fa-calendar-plus text-lg mb-1"></i>
                                <span class="text-xs">Agendamento</span>
                            </a>
                            <button onclick="abrirModalNovaMeta()"
                                class="bg-purple-500 hover:bg-purple-600 text-white text-center text-sm font-medium py-2.5 px-3 rounded-lg flex flex-col items-center justify-center transition-colors duration-200">
                                <i class="fas fa-bullseye text-lg mb-1"></i>
                                <span class="text-xs">Meta</span>
                            </button>
                            <a href="excluir.php?id=<?= $clienteId ?>"
                                class="bg-red-500 hover:bg-red-600 text-white text-center text-sm font-medium py-2.5 px-3 rounded-lg flex flex-col items-center justify-center transition-colors duration-200"
                                onclick="return confirm('Tem certeza que deseja excluir este cliente e todos os seus dados?')">
                                <i class="fas fa-trash text-lg mb-1"></i>
                                <span class="text-xs">Excluir</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo de Estatísticas -->
            <div class="bg-white rounded-xl shadow-md p-6 mt-6 dark:bg-gray-800 transition-colors duration-200">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">
                    Estatísticas</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
                                <i class="fas fa-clipboard-check text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avaliações</p>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                    <?= $totalAvaliacoes ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-amber-100 dark:bg-amber-900/30 p-2 rounded-lg">
                                <i class="fas fa-calendar-check text-amber-600 dark:text-amber-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Agendamentos</p>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                    <?= $totalAgendamentos ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-purple-100 dark:bg-purple-900/30 p-2 rounded-lg">
                                <i class="fas fa-bullseye text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Metas</p>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200"><?= $totalMetas ?></p>
                            </div>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-full px-3 py-1">
                            <span
                                class="text-xs font-medium text-gray-800 dark:text-gray-200"><?= $percentualConclusao ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo principal -->
        <div class="lg:col-span-3">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-xl shadow-md mb-6 dark:bg-gray-800 transition-colors duration-200">
                <div class="flex overflow-x-auto">
                    <button id="tab-avaliacoes"
                        class="tab-button active px-6 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600 dark:text-blue-400">
                        <i class="fas fa-clipboard-check mr-2"></i>Avaliações
                    </button>
                    <button id="tab-agendamentos"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-calendar-alt mr-2"></i>Agendamentos
                    </button>
                    <button id="tab-metas"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-bullseye mr-2"></i>Metas
                    </button>
                    <?php if (count($historicoPeso) > 1): ?>
                    <button id="tab-evolucao"
                        class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <i class="fas fa-chart-line mr-2"></i>Evolução
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Avaliações -->
                <div id="content-avaliacoes" class="tab-pane active">
                    <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Avaliações Recentes</h2>
                            <a href="../avaliacoes/listar.php?cliente_id=<?= $clienteId ?>"
                                class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium inline-flex items-center transition-colors duration-200">
                                Ver todas <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </a>
                        </div>

                        <?php if (empty($avaliacoes)): ?>
                        <div class="text-center py-8">
                            <div
                                class="mx-auto w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-gray-400 dark:text-gray-500 text-xl"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">Nenhuma avaliação registrada ainda.</p>
                            <a href="../avaliacoes/registrar.php?cliente_id=<?= $clienteId ?>"
                                class="inline-block mt-4 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                Fazer primeira avaliação
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Data</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Peso</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            IMC</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            % Gordura</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    <?php foreach ($avaliacoes as $avaliacao): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                <?= formatarData($avaliacao['data_avaliacao']) ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                <?= (!empty($avaliacao['peso']) && is_numeric($avaliacao['peso'])) ?
                                                            number_format((float)$avaliacao['peso'], 2) . ' kg' :
                                                            '--' ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                <?= ($avaliacao['imc'] !== '' && is_numeric($avaliacao['imc'])) ? number_format((float)$avaliacao['imc'], 1) : '--' ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                <?= (!empty($avaliacao['percentual_gordura']) && is_numeric($avaliacao['percentual_gordura'])) ?
                                                            number_format((float)$avaliacao['percentual_gordura'], 1) . '%' :
                                                            '--' ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="../avaliacoes/detalhes.php?id=<?= $avaliacao['id'] ?>"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200"
                                                    title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../avaliacoes/editar.php?id=<?= $avaliacao['id'] ?>"
                                                    class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300 transition-colors duration-200"
                                                    title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../avaliacoes/excluir.php?id=<?= $avaliacao['id'] ?>"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                                    title="Excluir"
                                                    onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
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

                <!-- Agendamentos -->
                <div id="content-agendamentos" class="tab-pane hidden">
                    <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Próximos Agendamentos
                            </h2>
                            <a href="../agendamentos/listar.php?cliente_id=<?= $clienteId ?>"
                                class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium inline-flex items-center transition-colors duration-200">
                                Ver todos <i class="fas fa-arrow-right ml-1 text-xs"></i>
                            </a>
                        </div>

                        <?php if (empty($agendamentos)): ?>
                        <div class="text-center py-8">
                            <div
                                class="mx-auto w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-calendar-times text-gray-400 dark:text-gray-500 text-xl"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">Nenhum agendamento futuro.</p>
                            <a href="../agendamentos/criar.php?cliente_id=<?= $clienteId ?>"
                                class="inline-block mt-4 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                Agendar agora
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Data/Hora</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Tipo</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    <?php foreach ($agendamentos as $agendamento): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                <?= formatarDataHora($agendamento['data_agendamento']) ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                <?= htmlspecialchars($agendamento['tipo']) ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?php
                                                    $statusClasses = [
                                                        'pendente' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                        'confirmado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                        'realizado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                        'cancelado' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                                    ];
                                                    $statusText = [
                                                        'pendente' => 'Pendente',
                                                        'confirmado' => 'Confirmado',
                                                        'realizado' => 'Realizado',
                                                        'cancelado' => 'Cancelado'
                                                    ];
                                                    ?>
                                            <span
                                                class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClasses[$agendamento['status']] ?>">
                                                <?= $statusText[$agendamento['status']] ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <?php if ($agendamento['status'] === 'pendente'): ?>
                                                <a href="../agendamentos/confirmar.php?id=<?= $agendamento['id'] ?>"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200"
                                                    title="Confirmar">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>

                                                <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                                <a href="../agendamentos/cancelar.php?id=<?= $agendamento['id'] ?>"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                                    title="Cancelar"
                                                    onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php endif; ?>

                                                <a href="../agendamentos/editar.php?id=<?= $agendamento['id'] ?>"
                                                    class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300 transition-colors duration-200"
                                                    title="Editar">
                                                    <i class="fas fa-edit"></i>
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

                <!-- Metas -->
                <div id="content-metas" class="tab-pane hidden">
                    <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Metas do Cliente</h2>
                            <button onclick="abrirModalNovaMeta()"
                                class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded-lg text-sm inline-flex items-center transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i> Nova Meta
                            </button>
                        </div>

                        <?php if (empty($metas)): ?>
                        <div class="text-center py-8">
                            <div
                                class="mx-auto w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-bullseye text-gray-400 dark:text-gray-500 text-xl"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">Nenhuma meta definida ainda.</p>
                            <button onclick="abrirModalNovaMeta()"
                                class="inline-block mt-4 text-purple-500 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-medium">
                                Criar primeira meta
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($metas as $meta): ?>
                            <div
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-medium text-gray-800 dark:text-gray-100">
                                            <?= htmlspecialchars($meta['descricao']) ?></h3>
                                        <?php if (!empty($meta['valor_alvo'])): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Valor alvo:
                                            <?= htmlspecialchars($meta['valor_alvo']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($meta['data_limite'])): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Prazo:
                                            <?= formatarData($meta['data_limite']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex space-x-2">
                                        <form method="POST" action="marcar_meta.php" class="inline">
                                            <input type="hidden" name="id" value="<?= $meta['id'] ?>">
                                            <input type="hidden" name="concluida"
                                                value="<?= $meta['concluida'] ? '0' : '1' ?>">
                                            <button type="submit"
                                                class="text-<?= $meta['concluida'] ? 'amber' : 'green' ?>-500 hover:text-<?= $meta['concluida'] ? 'amber' : 'green' ?>-700 dark:text-<?= $meta['concluida'] ? 'amber' : 'green' ?>-400 dark:hover:text-<?= $meta['concluida'] ? 'amber' : 'green' ?>-300 transition-colors duration-200"
                                                title="<?= $meta['concluida'] ? 'Reabrir meta' : 'Concluir meta' ?>">
                                                <i class="fas fa-<?= $meta['concluida'] ? 'undo' : 'check' ?>"></i>
                                            </button>
                                        </form>
                                        <a href="excluir_meta.php?id=<?= $meta['id'] ?>"
                                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200"
                                            onclick="return confirm('Tem certeza que deseja excluir esta meta?')"
                                            title="Excluir meta">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?= $meta['concluida'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' ?>">
                                        <?= $meta['concluida'] ? 'Concluída' : 'Em andamento' ?>
                                    </span>
                                    <?php if (!empty($meta['data_limite']) && !$meta['concluida']): ?>
                                    <?php
                                                $dataLimite = new DateTime($meta['data_limite']);
                                                $hoje = new DateTime();
                                                $diasRestantes = $hoje->diff($dataLimite)->days;
                                                $diasRestantes = $dataLimite > $hoje ? $diasRestantes : -$diasRestantes;
                                                ?>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        <?= $diasRestantes >= 0 ? $diasRestantes . ' dias restantes' : 'Expirada há ' . abs($diasRestantes) . ' dias' ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Evolução -->
                <?php if (count($historicoPeso) > 1): ?>
                <div id="content-evolucao" class="tab-pane hidden">
                    <div class="bg-white rounded-xl shadow-md p-6 dark:bg-gray-800 transition-colors duration-200">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Evolução de Peso</h2>
                        <div class="h-80">
                            <canvas id="pesoChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Meta -->
<div id="modalNovaMeta"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50 transition-opacity duration-200">
    <div
        class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4 dark:bg-gray-800 transition-all duration-200 transform scale-95 opacity-0">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Nova Meta</h3>
            <button onclick="fecharModalNovaMeta()"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="adicionar_meta.php" class="space-y-4">
            <input type="hidden" name="cliente_id" value="<?= $clienteId ?>">

            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição
                    *</label>
                <input type="text" id="descricao" name="descricao" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-purple-500 dark:focus:border-purple-500"
                    placeholder="Ex: Perder 5kg">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="valor_alvo"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Alvo</label>
                    <input type="text" id="valor_alvo" name="valor_alvo"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-purple-500 dark:focus:border-purple-500"
                        placeholder="Ex: 75">
                </div>

                <div>
                    <label for="data_limite"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Limite</label>
                    <input type="date" id="data_limite" name="data_limite"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-purple-500 dark:focus:border-purple-500">
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-3">
                <button type="button" onclick="fecharModalNovaMeta()"
                    class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2.5 px-5 rounded-lg transition-colors duration-200">
                    Cancelar
                </button>
                <button type="submit"
                    class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-2.5 px-5 rounded-lg transition-colors duration-200">
                    Salvar Meta
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
// Sistema de abas
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        // Remover classe ativa de todos os botões
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-blue-500', 'text-blue-600',
                'dark:text-blue-400');
            btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
        });

        // Adicionar classe ativa ao botão clicado
        this.classList.add('active', 'border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        this.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');

        // Esconder todos os conteúdos
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.add('hidden');
        });

        // Mostrar o conteúdo correspondente
        const contentId = this.id.replace('tab-', 'content-');
        document.getElementById(contentId).classList.remove('hidden');

        // Se for a aba de evolução, inicializar o gráfico
        if (contentId === 'content-evolucao') {
            inicializarGraficoPeso();
        }
    });
});

// Modal de nova meta
function abrirModalNovaMeta() {
    const modal = document.getElementById('modalNovaMeta');
    const modalContent = modal.querySelector('div');

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('bg-opacity-50');
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function fecharModalNovaMeta() {
    const modal = document.getElementById('modalNovaMeta');
    const modalContent = modal.querySelector('div');

    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    modal.classList.remove('bg-opacity-50');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Fechar modal ao clicar fora
document.getElementById('modalNovaMeta').addEventListener('click', function(event) {
    if (event.target === this) {
        fecharModalNovaMeta();
    }
});

// Inicializar tooltips
function inicializarTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const title = this.getAttribute('title');
            if (title) {
                // Criar tooltip
                const tooltip = document.createElement('div');
                tooltip.className =
                    'fixed bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-lg z-50';
                tooltip.textContent = title;
                document.body.appendChild(tooltip);

                // Posicionar tooltip
                const rect = this.getBoundingClientRect();
                tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';

                // Remover title para evitar duplicata
                this.removeAttribute('title');
                this.setAttribute('data-original-title', title);

                // Remover tooltip ao sair
                this.addEventListener('mouseleave', function() {
                    tooltip.remove();
                    this.setAttribute('title', title);
                }, {
                    once: true
                });
            }
        });
    });
}

// Inicializar gráfico de peso
function inicializarGraficoPeso() {
    <?php if (count($historicoPeso) > 1): ?>
    const canvas = document.getElementById('pesoChart');
    if (!canvas) return;

    // Destruir gráfico existente se houver
    if (canvas.chart) {
        canvas.chart.destroy();
    }

    const ctx = canvas.getContext('2d');
    canvas.chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($graficoLabels) ?>,
            datasets: [{
                label: 'Peso (kg)',
                data: <?= json_encode($graficoDados) ?>,
                borderColor: 'rgba(99, 102, 241, 1)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    <?php endif; ?>
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    inicializarTooltips();

    // Inicializar gráfico se estiver na aba ativa
    if (document.getElementById('content-evolucao') && !document.getElementById('content-evolucao').classList
        .contains('hidden')) {
        inicializarGraficoPeso();
    }
});
</script>