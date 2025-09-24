<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit(); 
}

// Obter estatísticas para o dashboard
$totalClientes = $db->querySingle("SELECT COUNT(*) FROM clientes WHERE usuario_id = " . getCurrentUserId());
$totalAvaliacoes = $db->querySingle("SELECT COUNT(*) FROM avaliacoes WHERE usuario_id = " . getCurrentUserId());
$totalAgendamentos = $db->querySingle("SELECT COUNT(*) FROM agendamentos WHERE usuario_id = " . getCurrentUserId() . " AND status = 'pendente'");

// Obter últimas avaliações
$avaliacoesRecentes = [];
$result = $db->query("
    SELECT a.*, c.nome as cliente_nome
    FROM avaliacoes a
    JOIN clientes c ON a.cliente_id = c.id
    WHERE a.usuario_id = " . getCurrentUserId() . "
    ORDER BY a.data_avaliacao DESC
    LIMIT 5
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $avaliacoesRecentes[] = $row;
}

// Obter próximos agendamentos
$agendamentosProximos = [];
$result = $db->query("
    SELECT ag.*, c.nome AS cliente_nome
    FROM agendamentos ag
    JOIN clientes c ON ag.cliente_id = c.id
    WHERE ag.usuario_id = " . getCurrentUserId() . "
    ORDER BY ag.data_agendamento ASC
");


while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $agendamentosProximos[] = $row;
}

// Configurar localização para português
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

// Formatar data em português
$dataAtual = strftime('%A, %d de %B de %Y');
?>



<?php include 'includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Dashboard</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo ucfirst(utf8_encode($dataAtual)); ?></p>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Card Clientes -->
        <div class="card stat-card bg-white p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Clientes Cadastrados</p>
                    <h2 class="text-3xl font-bold dark:text-white mt-2"><?php echo $totalClientes; ?></h2>
                    <div class="flex items-center mt-3">
                        <span class="text-green-500 text-sm font-medium flex items-center">
                            <i class="fas fa-arrow-up mr-1 text-xs"></i> 5.2%
                        </span>
                        <span class="text-gray-400 text-sm ml-2">desde o mês passado</span>
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-full">
                    <i class="fas fa-users text-blue-500 dark:text-blue-300 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Card Avaliações -->
        <div class="card stat-card bg-white p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Avaliações Realizadas</p>
                    <h2 class="text-3xl font-bold dark:text-white mt-2"><?php echo $totalAvaliacoes; ?></h2>
                    <div class="flex items-center mt-3">
                        <span class="text-green-500 text-sm font-medium flex items-center">
                            <i class="fas fa-arrow-up mr-1 text-xs"></i> 12.7%
                        </span>
                        <span class="text-gray-400 text-sm ml-2">desde o mês passado</span>
                    </div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-full">
                    <i class="fas fa-clipboard-check text-green-500 dark:text-green-300 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Card Agendamentos -->
        <div class="card stat-card bg-white p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">Agendamentos Pendentes</p>
                    <h2 class="text-3xl font-bold dark:text-white mt-2"><?php echo $totalAgendamentos; ?></h2>
                    <div class="flex items-center mt-3">
                        <span class="text-red-500 text-sm font-medium flex items-center">
                            <i class="fas fa-arrow-down mr-1 text-xs"></i> 2.3%
                        </span>
                        <span class="text-gray-400 text-sm ml-2">desde o mês passado</span>
                    </div>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-full">
                    <i class="fas fa-calendar-alt text-amber-500 dark:text-amber-300 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Seções -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Gráfico de Evolução -->
        <div class="card bg-white p-6 dark:bg-gray-800">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold dark:text-dark section-title">Evolução de Peso</h2>
                <div class="flex space-x-2">
                    <button
                        class="text-xs px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">6M</button>
                    <button
                        class="text-xs px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-500 dark:text-blue-300">12M</button>
                    <button
                        class="text-xs px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">All</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="evolucaoPesoChart"></canvas>
            </div>
        </div>

        <!-- Gráfico de IMC -->
        <div class="card bg-white p-6 dark:bg-gray-800">
            <h2 class="text-xl font-semibold dark:text-dark mb-6 section-title">Distribuição de IMC</h2>
            <div class="chart-container">
                <canvas id="imcChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Seção de Agendamentos -->
    <div class="card bg-white p-6 mb-8 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold dark:text-dark section-title">Agendamentos</h2>
            <a href="pages/agendamentos/listar.php" class="btn btn-primary flex items-center text-sm">
                Ver todos <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </a>
        </div>

        <div class="overflow-x-auto table-container">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($agendamentosProximos as $agendamento): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="font-medium dark:text-dark">
                            <?php echo htmlspecialchars($agendamento['cliente_nome']); ?>
                        </td>
                        <td class="text-gray-600 dark:text-gray-300">
                            <?php echo date('d/m/Y H:i', strtotime($agendamento['data_agendamento'])); ?>
                        </td>
                        <td class="text-gray-600 dark:text-gray-300">
                            <?php echo htmlspecialchars($agendamento['tipo']); ?>
                        </td>
                        <td>
                            <?php if ($agendamento['status'] === 'pendente'): ?>
                            <span
                                class="status-badge bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                Pendente
                            </span>
                            <?php elseif ($agendamento['status'] === 'confirmado'): ?>
                            <span
                                class="status-badge bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Confirmado
                            </span>
                            <?php elseif ($agendamento['status'] === 'cancelado'): ?>
                            <span class="status-badge bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                Cancelado
                            </span>
                            <?php else: ?>
                            <span class="status-badge bg-gray-100 text-gray-800 dark:bg-gray-700/30 dark:text-gray-300">
                                <?php echo htmlspecialchars($agendamento['status']); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Seção de Avaliações Recentes -->
    <div class="card bg-white p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold dark:text-dark section-title">Avaliações Recentes</h2>
            <a href="pages/avaliacoes/listar.php" class="btn btn-primary flex items-center text-sm">
                Ver todas <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </a>
        </div>

        <div class="overflow-x-auto table-container">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Peso (kg)</th>
                        <th>IMC</th>
                        <th>% Gordura</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($avaliacoesRecentes as $avaliacao): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="font-medium dark:text-dark">
                            <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?>
                        </td>
                        <td class="text-gray-600 dark:text-gray-300">
                            <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                        </td>
                        <td class="text-gray-600 dark:text-gray-300">
                            <?php echo number_format($avaliacao['peso'], 2); ?>
                        </td>
                        <td class="text-gray-600 dark:text-gray-300">
                            <?php echo number_format($avaliacao['imc'], 1); ?>
                        </td>
                        <td class="text-gray-600 dark:text-gray-300">
                            <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php'
?>


<script>
// Aguardar o carregamento completo do DOM
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Evolução de Peso
    const evolucaoPesoCtx = document.getElementById('evolucaoPesoChart').getContext('2d');
    const evolucaoPesoChart = new Chart(evolucaoPesoCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            datasets: [{
                label: 'Peso Médio (kg)',
                data: [78.5, 77.8, 76.2, 75.5, 74.8, 73.9],
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.05)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        drawBorder: false
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

    // Gráfico de IMC
    const imcCtx = document.getElementById('imcChart').getContext('2d');
    const imcChart = new Chart(imcCtx, {
        type: 'doughnut',
        data: {
            labels: ['Abaixo do peso', 'Peso normal', 'Sobrepeso', 'Obesidade'],
            datasets: [{
                data: [5, 15, 10, 5],
                backgroundColor: [
                    'rgba(67, 97, 238, 0.8)',
                    'rgba(76, 201, 240, 0.8)',
                    'rgba(247, 37, 133, 0.8)',
                    'rgba(230, 57, 70, 0.8)'
                ],
                borderColor: [
                    'rgba(67, 97, 238, 1)',
                    'rgba(76, 201, 240, 1)',
                    'rgba(247, 37, 133, 1)',
                    'rgba(230, 57, 70, 1)'
                ],
                borderWidth: 1,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>