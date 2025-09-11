<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID de avaliação
if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$avaliacaoId = $_GET['id'];

// Obter dados da avaliação com informações do cliente
$stmt = $db->prepare("
    SELECT a.*,
           c.nome as cliente_nome,
           c.foto as cliente_foto,
           c.data_nascimento,
           c.sexo
    FROM avaliacoes a
    JOIN clientes c ON a.cliente_id = c.id
    WHERE a.id = :id AND a.usuario_id = :usuario_id
");
$stmt->bindValue(':id', $avaliacaoId, SQLITE3_INTEGER);
$stmt->bindValue(':usuario_id', getCurrentUserId(), SQLITE3_INTEGER);
$result = $stmt->execute();
$avaliacao = $result->fetchArray(SQLITE3_ASSOC);

// Se não encontrar a avaliação, redireciona
if (!$avaliacao) {
    header('Location: listar.php');
    exit();
}

// Calcular idade do cliente
$idade = '';
if (!empty($avaliacao['data_nascimento'])) {
    $dataNasc = new DateTime($avaliacao['data_nascimento']);
    $hoje = new DateTime();
    $idade = $hoje->diff($dataNasc)->y;
}

// Gerar sugestão de treino com base no IMC
$sugestao = "";
$imc = $avaliacao['imc'];

if ($imc > 30) {
    $sugestao = "Treino para obesidade (IMC > 30):\n";
    $sugestao .= "- Foco principal em exercícios aeróbicos de baixo impacto (caminhada, bicicleta, natação)\n";
    $sugestao .= "- 5 sessões por semana, 30-45 minutos cada\n";
    $sugestao .= "- Musculação leve 2-3x/semana, enfatizando técnica e mobilidade\n";
    $sugestao .= "- Priorizar exercícios que não sobrecarreguem articulações\n";
} elseif ($imc > 25) {
    $sugestao = "Treino para sobrepeso (IMC 25-30):\n";
    $sugestao .= "- Combinação de cardio e musculação\n";
    $sugestao .= "- 3-4 dias de musculação (circuito inicial)\n";
    $sugestao .= "- 2-3 dias de cardio moderado (30-45 min)\n";
    $sugestao .= "- Enfatizar exercícios compostos para maior gasto calórico\n";
} else {
    $sugestao = "Treino para ganho de massa muscular e massa magra (IMC < 25):\n";
    $sugestao .= "- Objetivo: Hipertrofia muscular e aumento de massa magra\n";
    $sugestao .= "- Frequência: 4 a 6 treinos por semana\n";
    $sugestao .= "- Divisão ABC ou ABCD:\n";
    $sugestao .= "  A: Peito, tríceps e ombros\n";
    $sugestao .= "  B: Costas e bíceps\n";
    $sugestao .= "  C: Pernas e glúteos\n";
    $sugestao .= "- Séries: 3-4 por exercício / Repetições: 8-12 / Intervalos: 60-90s\n";
    $sugestao .= "- Cardio leve 2x por semana para manter condicionamento\n";
    $sugestao .= "- Alimentação: dieta rica em proteínas e calorias controladas\n";
    $sugestao .= "- Suplementação e sono adequado potencializam os resultados\n";
}

// Ajustes baseados em percentual de gordura
if (!empty($avaliacao['percentual_gordura']) && $avaliacao['percentual_gordura'] > 25) {
    $sugestao .= "\n\nObservação: Percentual de gordura elevado - incluir mais atividades aeróbicas e controle nutricional rigoroso.";
}

// Ajustes baseados em RCQ (risco cardiovascular)
$rcq = $avaliacao['rcq'] ?? null;
$sexo = strtolower($avaliacao['sexo'] ?? '');

if (($sexo === 'masculino' && $rcq >= 0.95) || ($sexo === 'feminino' && $rcq >= 0.80)) {
    $sugestao .= "\n\nAtenção: RCQ indica risco cardiovascular aumentado - monitorar intensidade e incluir aquecimento/desaquecimento adequados.";
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Detalhes da Avaliação</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Visualização completa dos dados da avaliação física</p>
        </div>
        <a href="listar.php?cliente_id=<?php echo $avaliacao['cliente_id']; ?>"
            class="mt-4 md:mt-0 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg inline-flex items-center transition duration-200 ease-in-out">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para lista
        </a>
    </div>

    <!-- Informações do Cliente -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                <?php if ($avaliacao['cliente_foto']): ?>
                <img src="../../<?php echo htmlspecialchars($avaliacao['cliente_foto']); ?>" alt="Foto"
                    class="h-16 w-16 rounded-full object-cover shadow-sm">
                <?php else: ?>
                <div
                    class="h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shadow-sm">
                    <i class="fas fa-user text-gray-400 dark:text-gray-500 text-2xl"></i>
                </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-semibold dark:text-gray-100">
                        <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?>
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300">
                        <?php
                        if ($idade) echo $idade . ' anos | ';
                        echo ucfirst($avaliacao['sexo']) . ' | ' . $avaliacao['altura'] . ' cm';
                        ?>
                    </p>
                </div>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 px-4 py-2 rounded-lg">
                <p class="font-medium">Data da Avaliação:
                    <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Resumo da Avaliação -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Card Resumo -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 dark:text-gray-100 flex items-center">
                <i class="fas fa-chart-line mr-2 text-blue-500"></i> Resumo
            </h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Peso</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo number_format($avaliacao['peso'], 2); ?> kg</p>
                    </div>
                    <div class="text-blue-500">
                        <i class="fas fa-weight-scale text-xl"></i>
                    </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">IMC</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo number_format($avaliacao['imc'], 1); ?></p>
                        <p class="text-xs mt-1 <?php
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
                        </p>
                    </div>
                    <div class="text-blue-500">
                        <i class="fas fa-heart-pulse text-xl"></i>
                    </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">% Gordura</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%
                        </p>
                    </div>
                    <div class="text-blue-500">
                        <i class="fas fa-percent text-xl"></i>
                    </div>
                </div>

                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">RCQ</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo number_format($avaliacao['rcq'], 2); ?></p>
                        <p class="text-xs mt-1 <?php
                                                $rcq = $avaliacao['rcq'];
                                                $risco = false;
                                                if ($avaliacao['sexo'] === 'masculino') {
                                                    $risco = $rcq >= 0.95;
                                                } else {
                                                    $risco = $rcq >= 0.80;
                                                }
                                                echo $risco ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                                                ?>">
                            <?php echo $risco ? 'Risco aumentado' : 'Risco normal'; ?>
                        </p>
                    </div>
                    <div class="text-blue-500">
                        <i class="fas fa-ruler-combined text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fotos da Avaliação -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 lg:col-span-2">
            <h2 class="text-xl font-semibold mb-4 dark:text-gray-100 flex items-center">
                <i class="fas fa-images mr-2 text-blue-500"></i> Fotos
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                $fotos = [
                    'frontal' => $avaliacao['foto_frontal'],
                    'lateral' => $avaliacao['foto_lateral'],
                    'posterior' => $avaliacao['foto_posterior']
                ];

                foreach ($fotos as $tipo => $foto):
                ?>
                <div class="text-center">
                    <?php if ($foto): ?>
                    <div class="relative group">
                        <img src="../../<?php echo htmlspecialchars($foto); ?>" alt="<?php echo ucfirst($tipo); ?>"
                            class="w-full h-48 object-cover rounded-lg mb-2 shadow-md transition duration-300 group-hover:opacity-90">
                        <div
                            class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition duration-300 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <button onclick="openModal('../../<?php echo htmlspecialchars($foto); ?>')"
                                class="bg-white bg-opacity-80 p-2 rounded-full text-blue-600">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo ucfirst($tipo); ?></p>
                    <?php else: ?>
                    <div
                        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg h-48 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 p-4">
                        <i class="fas fa-camera text-2xl mb-2"></i>
                        <p class="text-xs text-center">Sem foto <?php echo $tipo; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Navegação por abas para conteúdo detalhado -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex overflow-x-auto -mb-px">
                <button type="button" data-tab="medidas"
                    class="tab-button tab-active py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-blue-500 text-blue-600 dark:text-blue-400">
                    Medidas
                </button>
                <button type="button" data-tab="saude"
                    class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600">
                    Saúde
                </button>
                <button type="button" data-tab="anamnese"
                    class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600">
                    Anamnese
                </button>
                <button type="button" data-tab="observacoes"
                    class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600">
                    Observações
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Aba: Medidas Detalhadas -->
            <div id="tab-medidas" class="tab-content active">
                <h2 class="text-xl font-semibold mb-4 dark:text-gray-100 flex items-center">
                    <i class="fas fa-ruler mr-2 text-blue-500"></i> Medidas Detalhadas
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php
                    $medidas = [
                        ['Massa Muscular', $avaliacao['massa_muscular'], 'kg', 'fas fa-dumbbell'],
                        ['Massa Magra', $avaliacao['massa_magra'], 'kg', 'fas fa-weight-scale'],
                        ['Massa Gorda', $avaliacao['massa_gorda'], 'kg', 'fas fa-weight-scale'],
                        ['Circ. Abdominal', $avaliacao['circunferencia_abdominal'], 'cm', 'fas fa-ruler'],
                        ['Circ. Quadril', $avaliacao['circunferencia_quadril'], 'cm', 'fas fa-ruler'],
                        ['Braço', $avaliacao['perimetro_braco'], 'cm', 'fas fa-ruler-vertical'],
                        ['Antebraço', $avaliacao['perimetro_antebraco'], 'cm', 'fas fa-ruler-vertical'],
                        ['Coxa', $avaliacao['perimetro_coxa'], 'cm', 'fas fa-ruler-vertical'],
                        ['Panturrilha', $avaliacao['perimetro_panturrilha'], 'cm', 'fas fa-ruler-vertical'],
                    ];

                    foreach ($medidas as $medida):
                        if (!is_null($medida[1])):
                    ?>
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg flex items-center">
                        <div class="bg-blue-100 dark:bg-blue-800/40 p-3 rounded-full mr-3">
                            <i class="<?php echo $medida[3]; ?> text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo $medida[0]; ?></p>
                            <p class="text-lg font-semibold dark:text-gray-100">
                                <?php echo number_format($medida[1], 1) . ' ' . $medida[2]; ?>
                            </p>
                        </div>
                    </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>

            <!-- Aba: Saúde -->
            <div id="tab-saude" class="tab-content hidden">
                <h2 class="text-xl font-semibold mb-4 dark:text-gray-100 flex items-center">
                    <i class="fas fa-heart-pulse mr-2 text-blue-500"></i> Saúde
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-5 rounded-lg">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 dark:bg-blue-800/40 p-2 rounded-full mr-3">
                                <i class="fas fa-heart text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-100">Pressão Arterial</h3>
                        </div>
                        <p class="text-2xl font-bold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['pressao_arterial']); ?>
                        </p>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/30 p-5 rounded-lg">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 dark:bg-blue-800/40 p-2 rounded-full mr-3">
                                <i class="fas fa-heartbeat text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-100">Freq. Cardíaca</h3>
                        </div>
                        <p class="text-2xl font-bold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['frequencia_cardiaca']); ?> bpm
                        </p>
                    </div>
                </div>

                <h3 class="text-lg font-medium text-gray-800 dark:text-gray-100 mt-8 mb-4 flex items-center">
                    <i class="fas fa-running mr-2 text-blue-500"></i> Capacidades Físicas
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php
                    $capacidades = [
                        ['Flexibilidade', $avaliacao['flexibilidade'], 'fas fa-person-walking'],
                        ['Resistência', $avaliacao['resistencia'], 'fas fa-road'],
                        ['Força', $avaliacao['forca'], 'fas fa-dumbbell'],
                    ];

                    foreach ($capacidades as $cap):
                    ?>
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg text-center">
                        <div class="bg-blue-100 dark:bg-blue-800/40 p-3 rounded-full inline-flex mb-3">
                            <i class="<?php echo $cap[2]; ?> text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo $cap[0]; ?></p>
                        <p class="text-lg font-semibold dark:text-gray-100 mt-1">
                            <?php echo htmlspecialchars($cap[1]); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Aba: Anamnese -->
            <div id="tab-anamnese" class="tab-content hidden">
                <h2 class="text-xl font-semibold mb-4 dark:text-gray-100 flex items-center">
                    <i class="fas fa-file-medical mr-2 text-blue-500"></i> Anamnese e Questionários
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($avaliacao['anamnese_completa'])): ?>
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-5 rounded-lg">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 dark:bg-blue-800/40 p-2 rounded-full mr-3">
                                <i class="fas fa-stethoscope text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-100">Anamnese Completa</h3>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-md">
                            <p class="text-gray-700 dark:text-gray-200 whitespace-pre-line">
                                <?php echo htmlspecialchars($avaliacao['anamnese_completa']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($avaliacao['parq'])): ?>
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-5 rounded-lg">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 dark:bg-blue-800/40 p-2 rounded-full mr-3">
                                <i class="fas fa-question-circle text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-100">Questionário PAR-Q</h3>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-md">
                            <p class="text-gray-700 dark:text-gray-200 whitespace-pre-line">
                                <?php echo htmlspecialchars($avaliacao['parq']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($avaliacao['aha'])): ?>
                    <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/30 p-5 rounded-lg">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 dark:bg-blue-800/40 p-2 rounded-full mr-3">
                                <i class="fas fa-file-medical-alt text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 dark:text-gray-100">Questionário AHA</h3>
                        </div>
                        <div class="bg-white dark:bg-gray-700 p-4 rounded-md">
                            <p class="text-gray-700 dark:text-gray-200 whitespace-pre-line">
                                <?php echo htmlspecialchars($avaliacao['aha']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aba: Observações -->
            <div id="tab-observacoes" class="tab-content hidden">
                <h2 class="text-xl font-semibold mb-4 dark:text-gray-100 flex items-center">
                    <i class="fas fa-clipboard-list mr-2 text-blue-500"></i> Observações
                </h2>
                <?php if (!empty($avaliacao['observacoes'])): ?>
                <div class="bg-blue-50 dark:bg-blue-900/30 p-5 rounded-lg">
                    <div class="bg-white dark:bg-gray-700 p-4 rounded-md">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
                            <?php echo htmlspecialchars($avaliacao['observacoes']); ?>
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-clipboard text-4xl mb-3"></i>
                    <p>Nenhuma observação registrada para esta avaliação.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sugestão de Treino -->
    <?php if (!empty($sugestao)): ?>
    <div
        class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border-l-4 border-green-500 dark:border-green-400 p-5 rounded-lg mb-6">
        <div class="flex items-start">
            <div class="bg-green-100 dark:bg-green-800/40 p-3 rounded-full mr-4 flex-shrink-0">
                <i class="fas fa-dumbbell text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-2">Sugestão de Treino
                    Personalizado</h3>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-inner">
                    <pre
                        class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans"><?= htmlspecialchars($sugestao) ?></pre>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ações -->
    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8">
        <a href="editar.php?id=<?php echo $avaliacaoId; ?>"
            class="bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition duration-200 ease-in-out">
            <i class="fas fa-edit mr-2"></i> Editar Avaliação
        </a>
        <a href="../relatorios/gerar.php?avaliacao_id=<?php echo $avaliacaoId; ?>"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition duration-200 ease-in-out"
            target="_blank" rel="noopener">
            <i class="fas fa-file-pdf mr-2"></i> Gerar Relatório
        </a>
        <a href="excluir.php?id=<?php echo $avaliacaoId; ?>"
            class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition duration-200 ease-in-out"
            onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
            <i class="fas fa-trash mr-2"></i> Excluir
        </a>
    </div>
</div>

<!-- Modal para visualização de imagens -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
    <div class="max-w-4xl max-h-full">
        <div class="relative">
            <button onclick="closeModal()" class="absolute -top-10 right-0 text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>
            <img id="modalImage" src="" class="max-w-full max-h-screen">
        </div>
    </div>
</div>

<script>
// Navegação por abas
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
        const tabId = button.getAttribute('data-tab');

        // Atualizar botões de aba
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('tab-active', 'border-blue-500', 'text-blue-600',
                'dark:text-blue-400');
            btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
        });
        button.classList.add('tab-active', 'border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        button.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');

        // Mostrar conteúdo da aba
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabId}`).classList.remove('hidden');
        document.getElementById(`tab-${tabId}`).classList.add('active');
    });
});

// Modal para imagens
function openModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

// Fechar modal clicando fora da imagem
document.getElementById('imageModal').addEventListener('click', (e) => {
    if (e.target.id === 'imageModal') {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>