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

<div class="container mx-auto px-4 py-8 dark:bg-gray-900 dark:text-gray-100">
    <div class="flex justify-between items-center mb-6 dark:bg-gray-900 dark:text-gray-100">
        <?php if ($clienteId): ?>
        <h1 class="text-3xl font-bold text-gray-800 dark:bg-gray-900 dark:text-gray-100">Avaliações de
            <?php echo htmlspecialchars($cliente['nome']); ?>
        </h1>

        <?php if (isset($_GET['excluido'])): ?>
        <div class="alert alert-success">
            Avaliação excluída com sucesso!
        </div>
        <?php endif; ?>

        <a href="../clientes/detalhes.php?id=<?php echo $clienteId; ?>"
            class="bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
        <?php else: ?>
        <h1 class="text-3xl font-bold text-gray-800 dark:bg-gray-900 dark:text-gray-100">Todas as Avaliações</h1>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden dark:bg-gray-800">
        <div class="overflow-x-auto dark:bg-gray-80">
            <table class="min-w-full divide-y divide-gray-200 dark:bg-gray-900 dark:text-gray-100">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Cliente</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Data</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Peso (kg)</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            IMC</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            %
                            Gordura</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:text-gray-400">
                    <?php foreach ($avaliacoes as $avaliacao): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-400">
                                <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-400">
                                <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo number_format($avaliacao['peso'], 2); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-400">
                                <?php echo number_format($avaliacao['imc'], 1); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-gray-400">
                                <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="detalhes.php?id=<?php echo $avaliacao['id']; ?>"
                                    class="text-blue-600 hover:text-blue-900" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="editar.php?id=<?php echo $avaliacao['id']; ?>"
                                    class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="excluir.php?id=<?php echo $avaliacao['id']; ?>"
                                    class="text-red-600 hover:text-red-900" title="Excluir"
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


        <?php if ($clienteId && !empty($avaliacoes)): ?>
        <div class="mt-8 bg-white rounded-lg shadow overflow-hidden dark:bg-gray-800 p-4">
            <h2 class="text-xl font-bold mb-4 dark:text-white">Progresso das Avaliações</h2>
            <div class="relative h-96">
                <canvas id="avaliacoesChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="flex justify-end mt-6 mb-8">
        <a href="registrar.php?cliente_id=<?php echo $clienteId; ?>"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center dark:bg-gray-900 dark:text-gray-100">
            <i class="fas fa-plus mr-2"></i> Nova Avaliação
        </a>
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
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        yAxisID: 'yPeso',
                        tension: 0.4
                    },
                    {
                        label: 'IMC',
                        data: <?php echo json_encode($imcs); ?>,
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'yIMC',
                        tension: 0.4
                    },
                    {
                        label: '% Gordura',
                        data: <?php echo json_encode($gordura); ?>,
                        borderColor: '#4BC0C0',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        yAxisID: 'yGordura',
                        tension: 0.4
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
                        ticks: {
                            color: '#718096'
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)'
                        }
                    },
                    yPeso: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Peso (kg)',
                            color: '#718096'
                        },
                        ticks: {
                            color: '#718096'
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.1)'
                        }
                    },
                    yIMC: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'IMC',
                            color: '#718096'
                        },
                        ticks: {
                            color: '#718096'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    yGordura: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: '% Gordura',
                            color: '#718096'
                        },
                        ticks: {
                            color: '#718096'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#718096'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    });
    </script>
    <?php
    require_once '../../includes/footer.php'
    ?>
    </body>

    </html>