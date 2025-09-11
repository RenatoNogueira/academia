<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$clienteId = isset($_GET['cliente_id']) ? $_GET['cliente_id'] : null;
$cliente = [];
$dates = [];
$pesos = [];
$imcs = [];
$gordura = [];

if ($clienteId) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->bindValue(':id', $clienteId);
    $stmt->bindValue(':usuario_id', getCurrentUserId());
    $result = $stmt->execute();
    $cliente = $result->fetchArray(SQLITE3_ASSOC);

    if (!$cliente) {
        header('Location: ../clientes/listar.php');
        exit();
    }
}

$query = "
    SELECT a.*, c.nome as cliente_nome
    FROM avaliacoes a
    JOIN clientes c ON a.cliente_id = c.id
    WHERE a.usuario_id = :usuario_id
";

if ($clienteId) {
    $query .= " AND a.cliente_id = :cliente_id";
}

$query .= " ORDER BY a.data_avaliacao DESC";

$stmt = $db->prepare($query);
$stmt->bindValue(':usuario_id', getCurrentUserId());

if ($clienteId) {
    $stmt->bindValue(':cliente_id', $clienteId);
}

$result = $stmt->execute();

$avaliacoes = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $avaliacoes[] = $row;
}

// Preparar dados para o gráfico
if ($clienteId && !empty($avaliacoes)) {
    foreach ($avaliacoes as $avaliacao) {
        $dates[] = date('d/m/Y', strtotime($avaliacao['data_avaliacao']));
        $pesos[] = $avaliacao['peso'];
        $imcs[] = $avaliacao['imc'];
        $gordura[] = $avaliacao['percentual_gordura'];
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <?php if ($clienteId): ?>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Avaliações de
                <?php echo htmlspecialchars($cliente['nome']); ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Histórico completo de avaliações físicas</p>
            <?php else: ?>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Todas as Avaliações</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Visualize todas as avaliações físicas registradas</p>
            <?php endif; ?>
        </div>

        <div class="flex space-x-3 mt-4 md:mt-0">
            <?php if ($clienteId): ?>
            <a href="../clientes/detalhes.php?id=<?php echo $clienteId; ?>"
                class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg inline-flex items-center transition duration-200 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <?php endif; ?>

            <a href="registrar.php?cliente_id=<?php echo $clienteId; ?>"
                class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center transition duration-200 ease-in-out">
                <i class="fas fa-plus mr-2"></i> Nova Avaliação
            </a>
        </div>
    </div>

    <?php if (isset($_GET['excluido'])): ?>
    <div
        class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 dark:border-green-400 text-green-700 dark:text-green-200 p-4 mb-6 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>Avaliação excluída com sucesso!</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros e Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/40 p-3 rounded-full mr-4">
                    <i class="fas fa-clipboard-list text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total de Avaliações</p>
                    <p class="text-2xl font-bold dark:text-gray-100"><?php echo count($avaliacoes); ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($avaliacoes)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900/40 p-3 rounded-full mr-4">
                    <i class="fas fa-weight-scale text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Peso Médio</p>
                    <p class="text-2xl font-bold dark:text-gray-100">
                        <?php
                            $somaPesos = 0;
                            foreach ($avaliacoes as $av) {
                                $somaPesos += $av['peso'];
                            }
                            echo number_format($somaPesos / count($avaliacoes), 2);
                            ?> kg
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="flex items-center">
                <div class="bg-purple-100 dark:bg-purple-900/40 p-3 rounded-full mr-4">
                    <i class="fas fa-heart-pulse text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">IMC Médio</p>
                    <p class="text-2xl font-bold dark:text-gray-100">
                        <?php
                            $somaImc = 0;
                            foreach ($avaliacoes as $av) {
                                $somaImc += $av['imc'];
                            }
                            echo number_format($somaImc / count($avaliacoes), 1);
                            ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="flex items-center">
                <div class="bg-orange-100 dark:bg-orange-900/40 p-3 rounded-full mr-4">
                    <i class="fas fa-percent text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">% Gordura Médio</p>
                    <p class="text-2xl font-bold dark:text-gray-100">
                        <?php
                            $somaGordura = 0;
                            $countGordura = 0;
                            foreach ($avaliacoes as $av) {
                                if (!empty($av['percentual_gordura'])) {
                                    $somaGordura += $av['percentual_gordura'];
                                    $countGordura++;
                                }
                            }
                            echo $countGordura > 0 ? number_format($somaGordura / $countGordura, 1) . '%' : 'N/A';
                            ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabela de Avaliações -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <?php if (!$clienteId): ?>
                        <th
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center">
                                <i class="fas fa-user mr-2"></i> Cliente
                            </div>
                        </th>
                        <?php endif; ?>
                        <th
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center">
                                <i class="fas fa-calendar mr-2"></i> Data
                            </div>
                        </th>
                        <th
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center">
                                <i class="fas fa-weight-scale mr-2"></i> Peso (kg)
                            </div>
                        </th>
                        <th
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center">
                                <i class="fas fa-heart-pulse mr-2"></i> IMC
                            </div>
                        </th>
                        <th
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center">
                                <i class="fas fa-percent mr-2"></i> % Gordura
                        </th>
                        <th
                            class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center">
                                <i class="fas fa-cog mr-2"></i> Ações
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($avaliacoes)): ?>
                    <tr>
                        <td colspan="<?php echo $clienteId ? 5 : 6; ?>" class="px-6 py-12 text-center">
                            <div class="text-gray-400 dark:text-gray-500">
                                <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                                <p class="text-lg">Nenhuma avaliação encontrada</p>
                                <p class="text-sm mt-2">Comece registrando uma nova avaliação</p>
                                <a href="registrar.php?cliente_id=<?php echo $clienteId; ?>"
                                    class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out">
                                    <i class="fas fa-plus mr-2"></i> Nova Avaliação
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($avaliacoes as $avaliacao): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                        <?php if (!$clienteId): ?>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <?php echo number_format($avaliacao['peso'], 2); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <?php echo number_format($avaliacao['imc'], 1); ?>
                            </div>
                            <div class="text-xs mt-1 <?php
                                                                $imc = $avaliacao['imc'];
                                                                if ($imc < 18.5) echo 'text-yellow-600 dark:text-yellow-400';
                                                                elseif ($imc < 25) echo 'text-green-600 dark:text-green-400';
                                                                elseif ($imc < 30) echo 'text-orange-600 dark:text-orange-400';
                                                                else echo 'text-red-600 dark:text-red-400';
                                                                ?>">
                                <?php
                                        if ($imc < 18.5) echo 'Abaixo do peso';
                                        elseif ($imc < 25) echo 'Peso normal';
                                        elseif ($imc < 30) echo 'Sobrepeso';
                                        elseif ($imc < 35) echo 'Obesidade Grau I';
                                        elseif ($imc < 40) echo 'Obesidade Grau II';
                                        else echo 'Obesidade Grau III';
                                        ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="detalhes.php?id=<?php echo $avaliacao['id']; ?>"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition duration-200 ease-in-out"
                                    title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="editar.php?id=<?php echo $avaliacao['id']; ?>"
                                    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 p-2 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 hover:bg-yellow-100 dark:hover:bg-yellow-900/50 transition duration-200 ease-in-out"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="excluir.php?id=<?php echo $avaliacao['id']; ?>"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-2 rounded-lg bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 transition duration-200 ease-in-out"
                                    title="Excluir"
                                    onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráfico de Progresso -->
    <?php if ($clienteId && !empty($avaliacoes)): ?>
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden p-6">
        <h2 class="text-xl font-bold mb-4 dark:text-gray-100 flex items-center">
            <i class="fas fa-chart-line mr-2 text-blue-500"></i> Progresso das Avaliações
        </h2>
        <div class="relative h-96">
            <canvas id="avaliacoesChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Botão de Nova Avaliação no final -->
    <?php if (!empty($avaliacoes)): ?>
    <div class="flex justify-center mt-8">
        <a href="registrar.php?cliente_id=<?php echo $clienteId; ?>"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg inline-flex items-center transition duration-200 ease-in-out shadow-md">
            <i class="fas fa-plus mr-2"></i> Nova Avaliação
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($clienteId && !empty($avaliacoes)): ?>
    const ctx = document.getElementById('avaliacoesChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                    label: 'Peso (kg)',
                    data: <?php echo json_encode($pesos); ?>,
                    borderColor: '#FF6384',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF6384',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'IMC',
                    data: <?php echo json_encode($imcs); ?>,
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#36A2EB',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: '% Gordura',
                    data: <?php echo json_encode($gordura); ?>,
                    borderColor: '#4BC0C0',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4BC0C0',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)'
                    },
                    ticks: {
                        color: '#718096'
                    }
                },
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)'
                    },
                    ticks: {
                        color: '#718096'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#718096'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../../includes/footer.php'; ?>