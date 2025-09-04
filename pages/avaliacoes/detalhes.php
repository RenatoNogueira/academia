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
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Detalhes da Avaliação</h1>
        <a href="listar.php?cliente_id=<?php echo $avaliacao['cliente_id']; ?>"
            class="bg-gray-300 hover:bg-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                <?php if ($avaliacao['cliente_foto']): ?>
                <img src="../../<?php echo htmlspecialchars($avaliacao['cliente_foto']); ?>" alt="Foto"
                    class="h-16 w-16 rounded-full object-cover">
                <?php else: ?>
                <div class="h-16 w-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
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
            <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 px-4 py-2 rounded-md">
                <p class="font-semibold">Data da Avaliação:
                    <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Resumo da Avaliação -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 lg:col-span-1">
            <h2 class="text-xl font-semibold mb-4 dark:text-gray-100">Resumo</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Peso</p>
                    <p class="text-lg font-semibold"><?php echo number_format($avaliacao['peso'], 2); ?> kg</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">IMC</p>
                    <p class="text-lg font-semibold"><?php echo number_format($avaliacao['imc'], 1); ?></p>
                    <p class="text-sm text-gray-500">
                        <?php
                        $imc = $avaliacao['imc'];
                        if ($imc < 18.5) echo 'Abaixo do peso';
                        elseif ($imc < 25) echo 'Peso normal';
                        elseif ($imc < 30) echo 'Sobrepeso';
                        elseif ($imc < 35) echo 'Obesidade Grau I';
                        elseif ($imc < 40) echo 'Obesidade Grau II';
                        else echo 'Obesidade Grau III';
                        ?>
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">% Gordura</p>
                    <p class="text-lg font-semibold">
                        <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">RCQ</p>
                    <p class="text-lg font-semibold"><?php echo number_format($avaliacao['rcq'], 2); ?></p>
                    <p class="text-sm text-gray-500">
                        <?php
                        $rcq = $avaliacao['rcq'];
                        if ($avaliacao['sexo'] === 'masculino') {
                            echo $rcq >= 0.95 ? 'Risco aumentado' : 'Risco normal';
                        } else {
                            echo $rcq >= 0.80 ? 'Risco aumentado' : 'Risco normal';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Fotos da Avaliação -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 lg:col-span-2">
            <h2 class="text-xl font-semibold mb-4 dark:text-gray-100">Fotos</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php if ($avaliacao['foto_frontal']): ?>
                <div class="text-center">
                    <img src="../../<?php echo htmlspecialchars($avaliacao['foto_frontal']); ?>" alt="Frontal"
                        class="w-full h-48 object-cover rounded-md mb-2">
                    <p class="text-sm text-gray-600">Frontal</p>
                </div>
                <?php else: ?>
                <div
                    class="border-2 border-dashed border-gray-300 rounded-md h-48 flex items-center justify-center text-gray-400">
                    <p>Sem foto frontal</p>
                </div>
                <?php endif; ?>

                <?php if ($avaliacao['foto_lateral']): ?>
                <div class="text-center">
                    <img src="../../<?php echo htmlspecialchars($avaliacao['foto_lateral']); ?>" alt="Lateral"
                        class="w-full h-48 object-cover rounded-md mb-2">
                    <p class="text-sm text-gray-600">Lateral</p>
                </div>
                <?php else: ?>
                <div
                    class="border-2 border-dashed border-gray-300 rounded-md h-48 flex items-center justify-center text-gray-400">
                    <p>Sem foto lateral</p>
                </div>
                <?php endif; ?>

                <?php if ($avaliacao['foto_posterior']): ?>
                <div class="text-center">
                    <img src="../../<?php echo htmlspecialchars($avaliacao['foto_posterior']); ?>" alt="Posterior"
                        class="w-full h-48 object-cover rounded-md mb-2">
                    <p class="text-sm text-gray-600">Posterior</p>
                </div>
                <?php else: ?>
                <div
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md h-48 flex items-center justify-center text-gray-400 dark:text-gray-500">
                    <p>Sem foto posterior</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Novo bloco: Anamnese e Questionários -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4 dark:text-gray-100">Anamnese e Questionários</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if (!empty($avaliacao['anamnese_completa'])): ?>
            <div>
                <h3 class="text-lg font-medium text-gray-800 mb-2 dark:text-gray-100">Anamnese Completa</h3>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                    <p class="text-gray-700 dark:text-gray-200 whitespace-line">
                        <?php echo htmlspecialchars($avaliacao['anamnese_completa']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($avaliacao['parq'])): ?>
            <div>
                <h3 class="text-lg font-medium text-gray-800 mb-2 dark:text-gray-100">Questionário PAR-Q</h3>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                    <p class="text-gray-700 dark:text-gray-200 whitespace-line">
                        <?php echo htmlspecialchars($avaliacao['parq']); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($avaliacao['aha'])): ?>
            <div class="md:col-span-2">
                <h3 class="text-lg font-medium text-gray-800 mb-2 dark:text-gray-100">Questionário AHA</h3>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                    <p class="text-gray-700 dark:text-gray-200 whitespace-line">
                        <?php echo htmlspecialchars($avaliacao['aha']); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seção de Medidas Corporais Atualizada -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4 dark:text-gray-100">Medidas Detalhadas</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Massa Muscular</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['massa_muscular'], 1); ?> kg
                </p>
            </div>

            <!-- Seção atualizando -->
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Massa Magra</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['massa_magra'], 1); ?>
                    kg
                </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Massa Gorda</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['massa_gorda'], 1); ?>
                    kg
                </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Circ. Abdominal</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['circunferencia_abdominal'], 1); ?>
                    cm
                </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Circ. Quadril</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['circunferencia_quadril'], 1); ?>
                    cm
                </p>
            </div>
            <!-- Seção atualizando -->



            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Braço</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['perimetro_braco'], 1); ?> cm
                </p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Antebraço</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['perimetro_antebraco'], 1); ?> cm
                </p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Coxa</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['perimetro_coxa'], 1); ?> cm
                </p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                <p class="text-sm text-gray-600 dark:text-gray-300">Panturrilha</p>
                <p class="text-lg font-semibold dark:text-gray-100">
                    <?php echo number_format($avaliacao['perimetro_panturrilha'], 1); ?> cm
                </p>
            </div>
        </div>
    </div>

    <!-- Detalhes Completo -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Saúde -->
            <div>
                <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Saúde</h3>
                <div class="space-y-2">
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Pressão Arterial</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['pressao_arterial']); ?>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Freq. Cardíaca</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['frequencia_cardiaca']); ?>
                            bpm
                    </div>
                </div>
            </div>
            <!-- Capacidades Físicas -->
            <div>
                <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Capacidades Físicas</h3>
                <div class="space-y-2">
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Flexibilidade</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['flexibilidade']); ?>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Resistência</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['resistencia']); ?>
                    </div>
                </div>
            </div>
            <div>
                <div class="space-y-2 mt-0 md:mt-10">
                    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-md">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Força</p>
                        <p class="text-lg font-semibold dark:text-gray-100">
                            <?php echo htmlspecialchars($avaliacao['forca']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Observações -->
    <?php if (!empty($avaliacao['observacoes'])): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4 dark:text-gray-100">Observações</h2>
        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">
            <?php echo htmlspecialchars($avaliacao['observacoes']); ?></p>
    </div>
    <?php endif; ?>


    <!-- Seção de Sugestão de Treino Atualizada -->
    <?php if (!empty($sugestao)): ?>
    <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-400 dark:border-green-600 p-4 rounded-md mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-dumbbell text-green-400 dark:text-green-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-semibold text-green-800 dark:text-green-200">Sugestão de Treino</h3>
                <div class="mb-6">
                    <pre
                        class=" p-4 rounded-xl text-gray-500 whitespace-pre-wrap dark:text-green-200"><?= htmlspecialchars($sugestao) ?></pre>
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ações -->
    <div class="flex justify-end mt-6 space-x-3">
        <a href="editar.php?id=<?php echo $avaliacaoId; ?>"
            class="bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-edit mr-2"></i> Editar
        </a>
        <a href="excluir.php?id=<?php echo $avaliacaoId; ?>"
            class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
            onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">
            <i class="fas fa-trash mr-2"></i> Excluir
        </a>
        <a href="../relatorios/gerar.php?avaliacao_id=<?php echo $avaliacaoId; ?>"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center"
            target=”_blank” rel=”noopener”>
            <i class="fas fa-file-pdf mr-2"></i> Gerar Relatório
        </a>
    </div>
</div>

<?php
require_once '../../includes/footer.php'
?>

</body>

</html>