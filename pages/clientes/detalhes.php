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
?>


<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Detalhes do Cliente</h1>
        <div class="flex space-x-2">
            <a href="editar.php?id=<?= $clienteId ?>"
                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <a href="listar.php"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Informações do Cliente -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-1">
            <div class="flex flex-col items-center mb-4">
                <?php if ($cliente['foto']): ?>
                <img src="../../<?= htmlspecialchars($cliente['foto']) ?>" alt="Foto do cliente"
                    class="h-32 w-32 rounded-full object-cover mb-4">
                <?php else: ?>
                <div class="h-32 w-32 rounded-full bg-gray-200 flex items-center justify-center mb-4">
                    <i class="fas fa-user text-gray-400 text-5xl"></i>
                </div>
                <?php endif; ?>

                <h2 class="text-2xl font-semibold text-center"><?= htmlspecialchars($cliente['nome']) ?></h2>

                <div class="mt-2 flex space-x-4">
                    <a href="tel:<?= htmlspecialchars($cliente['telefone']) ?>"
                        class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-phone"></i>
                    </a>
                    <?php if (!empty($cliente['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($cliente['email']) ?>"
                        class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Informações Pessoais</h3>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        <div>
                            <p class="text-xs text-gray-500">Idade</p>
                            <p class="font-medium"><?= $idade ? $idade . ' anos' : '--' ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Sexo</p>
                            <p class="font-medium">
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
                        <div>
                            <p class="text-xs text-gray-500">Altura</p>
                            <p class="font-medium"><?= $cliente['altura'] ? $cliente['altura'] . ' cm' : '--' ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Data de Nasc.</p>
                            <p class="font-medium">
                                <?= $cliente['data_nascimento'] ? formatarData($cliente['data_nascimento']) : '--' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($cliente['observacoes'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Observações</h3>
                    <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">
                        <?= htmlspecialchars($cliente['observacoes']) ?></p>
                </div>
                <?php endif; ?>

                <div class="pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Ações Rápidas</h3>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <a href="../avaliacoes/registrar.php?cliente_id=<?= $clienteId ?>"
                            class="bg-green-500 hover:bg-green-600 text-white text-center text-sm font-medium py-2 px-3 rounded">
                            <i class="fas fa-clipboard-check mr-1"></i> Nova Avaliação
                        </a>
                        <a href="../agendamentos/criar.php?cliente_id=<?= $clienteId ?>"
                            class="bg-blue-500 hover:bg-blue-600 text-white text-center text-sm font-medium py-2 px-3 rounded">
                            <i class="fas fa-calendar-plus mr-1"></i> Novo Agendamento
                        </a>
                        <a href="#metas" onclick="abrirModalNovaMeta()"
                            class="bg-purple-500 hover:bg-purple-600 text-white text-center text-sm font-medium py-2 px-3 rounded">
                            <i class="fas fa-bullseye mr-1"></i> Nova Meta
                        </a>
                        <a href="excluir.php?id=<?= $clienteId ?>"
                            class="bg-red-500 hover:bg-red-600 text-white text-center text-sm font-medium py-2 px-3 rounded"
                            onclick="return confirm('Tem certeza que deseja excluir este cliente e todos os seus dados?')">
                            <i class="fas fa-trash mr-1"></i> Excluir
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Gráfico de Evolução -->
            <?php if (count($historicoPeso) > 1): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Evolução de Peso</h2>
                <canvas id="pesoChart" height="200"></canvas>
            </div>
            <?php endif; ?>

            <!-- Avaliações Recentes -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Avaliações Recentes</h2>
                    <a href="../avaliacoes/listar.php?cliente_id=<?= $clienteId ?>"
                        class="text-blue-500 hover:text-blue-700 text-sm">
                        Ver todas
                    </a>
                </div>

                <?php if (empty($avaliacoes)): ?>
                <p class="text-gray-500 text-center py-4">Nenhuma avaliação registrada ainda.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Peso</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    IMC</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    % Gordura</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= formatarData($avaliacao['data_avaliacao']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= (!empty($avaliacao['peso']) && is_numeric($avaliacao['peso'])) ?
                                                    number_format((float)$avaliacao['peso'], 2) . ' kg' :
                                                    '--' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= ($avaliacao['imc'] !== '' && is_numeric($avaliacao['imc'])) ? number_format((float)$avaliacao['imc'], 1) : '--' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= (!empty($avaliacao['percentual_gordura']) && is_numeric($avaliacao['percentual_gordura'])) ?
                                                    number_format((float)$avaliacao['percentual_gordura'], 1) . '%' :
                                                    '--' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="../avaliacoes/detalhes.php?id=<?= $avaliacao['id'] ?>"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../avaliacoes/editar.php?id=<?= $avaliacao['id'] ?>"
                                        class="text-yellow-600 hover:text-yellow-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../avaliacoes/excluir.php?id=<?= $avaliacao['id'] ?>"
                                        class="text-red-600 hover:text-red-900"
                                        onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Agendamentos Futuros -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Próximos Agendamentos</h2>
                    <a href="../agendamentos/listar.php?cliente_id=<?= $clienteId ?>"
                        class="text-blue-500 hover:text-blue-700 text-sm">
                        Ver todos
                    </a>
                </div>

                <?php if (empty($agendamentos)): ?>
                <p class="text-gray-500 text-center py-4">Nenhum agendamento futuro.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data/Hora</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($agendamentos as $agendamento): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= formatarDataHora($agendamento['data_agendamento']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($agendamento['tipo']) ?>
                                    </div>
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
                                        <a href="../agendamentos/confirmar.php?id=<?= $agendamento['id'] ?>"
                                            class="text-blue-600 hover:text-blue-900" title="Confirmar">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php if ($agendamento['status'] === 'pendente' || $agendamento['status'] === 'confirmado'): ?>
                                        <a href="../agendamentos/cancelar.php?id=<?= $agendamento['id'] ?>"
                                            class="text-red-600 hover:text-red-900" title="Cancelar"
                                            onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>

                                        <a href="../agendamentos/editar.php?id=<?= $agendamento['id'] ?>"
                                            class="text-yellow-600 hover:text-yellow-900" title="Editar">
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

            <!-- Metas do Cliente -->
            <div id="metas" class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Metas</h2>
                    <button onclick="abrirModalNovaMeta()"
                        class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-1 px-3 rounded text-sm">
                        <i class="fas fa-plus mr-1"></i> Nova Meta
                    </button>
                </div>

                <?php if (empty($metas)): ?>
                <p class="text-gray-500 text-center py-4">Nenhuma meta definida ainda.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($metas as $meta): ?>
                    <div class="border border-gray-200 rounded-md p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium"><?= htmlspecialchars($meta['descricao']) ?></h3>
                                <?php if (!empty($meta['valor_alvo'])): ?>
                                <p class="text-sm text-gray-600">Valor alvo:
                                    <?= htmlspecialchars($meta['valor_alvo']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($meta['data_limite'])): ?>
                                <p class="text-sm text-gray-600">Prazo: <?= formatarData($meta['data_limite']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="flex space-x-2">
                                <form method="POST" action="marcar_meta.php" class="inline">
                                    <input type="hidden" name="id" value="<?= $meta['id'] ?>">
                                    <input type="hidden" name="concluida" value="<?= $meta['concluida'] ? '0' : '1' ?>">
                                    <button type="submit"
                                        class="text-<?= $meta['concluida'] ? 'yellow' : 'green' ?>-500 hover:text-<?= $meta['concluida'] ? 'yellow' : 'green' ?>-700">
                                        <i class="fas fa-<?= $meta['concluida'] ? 'undo' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <a href="excluir_meta.php?id=<?= $meta['id'] ?>" class="text-red-500 hover:text-red-700"
                                    onclick="return confirm('Tem certeza que deseja excluir esta meta?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center">
                            <span
                                class="px-2 py-1 text-xs font-semibold rounded-full <?= $meta['concluida'] ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                <?= $meta['concluida'] ? 'Concluída' : 'Em andamento' ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Meta -->
<div id="modalNovaMeta" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Nova Meta</h3>
            <button onclick="fecharModalNovaMeta()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="adicionar_meta.php" class="space-y-4">
            <input type="hidden" name="cliente_id" value="<?= $clienteId ?>">

            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição *</label>
                <input type="text" id="descricao" name="descricao" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="valor_alvo" class="block text-sm font-medium text-gray-700">Valor Alvo</label>
                    <input type="text" id="valor_alvo" name="valor_alvo"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="data_limite" class="block text-sm font-medium text-gray-700">Data Limite</label>
                    <input type="date" id="data_limite" name="data_limite"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-3">
                <button type="button" onclick="fecharModalNovaMeta()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancelar
                </button>
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">
                    Salvar Meta
                </button>
            </div>
        </form>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
<script>
// Gráfico de evolução de peso
<?php if (count($historicoPeso) > 1): ?>
const pesoCtx = document.getElementById('pesoChart').getContext('2d');
const pesoChart = new Chart(pesoCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($graficoLabels) ?>,
        datasets: [{
            label: 'Peso (kg)',
            data: <?= json_encode($graficoDados) ?>,
            borderColor: 'rgba(59, 130, 246, 1)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: false
            }
        }
    }
});
<?php endif; ?>

// Modal de nova meta
function abrirModalNovaMeta() {
    document.getElementById('modalNovaMeta').classList.remove('hidden');
}

function fecharModalNovaMeta() {
    document.getElementById('modalNovaMeta').classList.add('hidden');
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalNovaMeta');
    if (event.target === modal) {
        fecharModalNovaMeta();
    }
}
</script>
</body>

</html>