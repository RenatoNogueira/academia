<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

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
    SELECT ag.*, c.nome as cliente_nome
    FROM agendamentos ag
    JOIN clientes c ON ag.cliente_id = c.id
    WHERE ag.usuario_id = " . getCurrentUserId() . "
    AND ag.status = 'pendente'
    AND ag.data_agendamento >= datetime('now')
    ORDER BY ag.data_agendamento ASC
    LIMIT 5
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $agendamentosProximos[] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard</h1>

    <!-- Cards de Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500">Clientes Cadastrados</p>
                    <h2 class="text-3xl font-bold"><?php echo $totalClientes; ?></h2>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500">Avaliações Realizadas</p>
                    <h2 class="text-3xl font-bold"><?php echo $totalAvaliacoes; ?></h2>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-clipboard-check text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500">Agendamentos Pendentes</p>
                    <h2 class="text-3xl font-bold"><?php echo $totalAgendamentos; ?></h2>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-calendar-alt text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Seções -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Gráfico de Evolução -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Evolução de Peso (Últimos 6 meses)</h2>
            <canvas id="evolucaoPesoChart" height="300"></canvas>
        </div>

        <!-- Gráfico de IMC -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Distribuição de IMC dos Clientes</h2>
            <canvas id="imcChart" height="300"></canvas>
        </div>
    </div>

    <!-- Seção de Agendamentos Próximos -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Próximos Agendamentos</h2>
            <a href="pages/agendamentos/listar.php" class="text-blue-500 hover:text-blue-700">Ver todos</a>
        </div>

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
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($agendamentosProximos as $agendamento): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($agendamento['cliente_nome']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('d/m/Y H:i', strtotime($agendamento['data_agendamento'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($agendamento['tipo']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <?php echo htmlspecialchars($agendamento['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Seção de Avaliações Recentes -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Avaliações Recentes</h2>
            <a href="pages/avaliacoes/listar.php" class="text-blue-500 hover:text-blue-700">Ver todas</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Peso (kg)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            IMC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">%
                            Gordura</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($avaliacoesRecentes as $avaliacao): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo number_format($avaliacao['peso'], 2); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo number_format($avaliacao['imc'], 1); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%</div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<script>
// Gráfico de Evolução de Peso
const evolucaoPesoCtx = document.getElementById('evolucaoPesoChart').getContext('2d');
const evolucaoPesoChart = new Chart(evolucaoPesoCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        datasets: [{
            label: 'Peso Médio (kg)',
            data: [78.5, 77.8, 76.2, 75.5, 74.8, 73.9],
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
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: false
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
                'rgba(59, 130, 246, 0.7)',
                'rgba(16, 185, 129, 0.7)',
                'rgba(245, 158, 11, 0.7)',
                'rgba(239, 68, 68, 0.7)'
            ],
            borderColor: [
                'rgba(59, 130, 246, 1)',
                'rgba(16, 185, 129, 1)',
                'rgba(245, 158, 11, 1)',
                'rgba(239, 68, 68, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});
</script>
</body>

</html>