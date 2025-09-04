<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se os parâmetros necessários foram passados
if (!isset($_GET['cliente_id']) || !isset($_GET['avaliacao_id'])) {
    header('Location: ../clientes/listar.php');
    exit();
}

$clienteId = $_GET['cliente_id'];
$avaliacaoId = $_GET['avaliacao_id'];

// Obter dados do cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
$stmt->bindValue(':id', $clienteId);
$stmt->bindValue(':usuario_id', getCurrentUserId());
$result = $stmt->execute();
$cliente = $result->fetchArray(SQLITE3_ASSOC);

// Obter dados da avaliação
$stmt = $db->prepare("
    SELECT a.*, c.nome as cliente_nome, c.foto as cliente_foto
    FROM avaliacoes a
    JOIN clientes c ON a.cliente_id = c.id
    WHERE a.id = :avaliacao_id AND a.cliente_id = :cliente_id AND a.usuario_id = :usuario_id
");
$stmt->bindValue(':avaliacao_id', $avaliacaoId);
$stmt->bindValue(':cliente_id', $clienteId);
$stmt->bindValue(':usuario_id', getCurrentUserId());
$result = $stmt->execute();
$avaliacao = $result->fetchArray(SQLITE3_ASSOC);

// Se não encontrar avaliação ou cliente, redireciona
if (!$avaliacao || !$cliente) {
    header("Location: ../clientes/detalhes.php?id=$clienteId");
    exit();
}

// Geração de sugestão com base nos dados da avaliação
$sugestao = gerarSugestaoTreino($avaliacao);
$objetivoPadrao = ($avaliacao['imc'] > 25) ? "Perda de gordura" : "Hipertrofia muscular";

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $objetivo = $_POST['objetivo'];
        $treino = $_POST['treino'];
        $duracao = $_POST['duracao'];
        $diasSemana = $_POST['dias_semana'];
        $observacoes = $_POST['observacoes'];
        $dataInicio = $_POST['data_inicio'];
        $dataFim = $_POST['data_fim'];

        $stmt = $db->prepare("
            INSERT INTO treinos (
                cliente_id, avaliacao_id, usuario_id, objetivo, treino,
                duracao, dias_semana, observacoes, data_inicio, data_fim
            ) VALUES (
                :cliente_id, :avaliacao_id, :usuario_id, :objetivo, :treino,
                :duracao, :dias_semana, :observacoes, :data_inicio, :data_fim
            )
        ");

        $stmt->bindValue(':cliente_id', $clienteId);
        $stmt->bindValue(':avaliacao_id', $avaliacaoId);
        $stmt->bindValue(':usuario_id', getCurrentUserId());
        $stmt->bindValue(':objetivo', $objetivo);
        $stmt->bindValue(':treino', $treino);
        $stmt->bindValue(':duracao', $duracao);
        $stmt->bindValue(':dias_semana', $diasSemana);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':data_inicio', $dataInicio);
        $stmt->bindValue(':data_fim', $dataFim);

        if ($stmt->execute()) {
            header("Location: ../clientes/detalhes.php?id=$clienteId&success=Treino+criado+com+sucesso");
            exit();
        }
    } catch (Exception $e) {
        $error = "Erro ao criar treino: " . $e->getMessage();
    }
}

/**
 * Gera sugestão de treino baseada nos dados da avaliação
 */
function gerarSugestaoTreino($avaliacao)
{
    $sugestao = "";

    // Baseado no IMC
    if ($avaliacao['imc'] > 30) {
        $sugestao = "Treino para obesidade (IMC > 30):\n";
        $sugestao .= "- Foco principal em exercícios aeróbicos de baixo impacto (caminhada, bicicleta, natação)\n";
        $sugestao .= "- 5 sessões por semana, 30-45 minutos cada\n";
        $sugestao .= "- Musculação leve 2-3x/semana, enfatizando técnica e mobilidade\n";
        $sugestao .= "- Priorizar exercícios que não sobrecarreguem articulações\n";
    } elseif ($avaliacao['imc'] > 25) {
        $sugestao = "Treino para sobrepeso (IMC 25-30):\n";
        $sugestao .= "- Combinação de cardio e musculação\n";
        $sugestao .= "- 3-4 dias de musculação (circuito inicial)\n";
        $sugestao .= "- 2-3 dias de cardio moderado (30-45 min)\n";
        $sugestao .= "- Enfatizar exercícios compostos para maior gasto calórico\n";
    } else {
        $sugestao = "Treino para normopeso (IMC < 25):\n";
        $sugestao .= "- Foco em hipertrofia e força\n";
        $sugestao .= "- Divisão ABC (3-5x/semana)\n";
        $sugestao .= "- A: Membros superiores (peito, ombros, tríceps)\n";
        $sugestao .= "- B: Membros inferiores (pernas, glúteos)\n";
        $sugestao .= "- C: Costas e bíceps\n";
        $sugestao .= "- Cardio opcional 1-2x/semana (20-30 min)\n";
    }

    // Ajustes baseados em percentual de gordura
    if ($avaliacao['percentual_gordura'] > 25) {
        $sugestao .= "\nObservação: Percentual de gordura elevado - incluir mais atividades aeróbicas e controle nutricional rigoroso.";
    }

    // Ajustes baseados em RCQ (risco cardiovascular)
    $rcq = $avaliacao['rcq'];
    if (($avaliacao['sexo'] === 'masculino' && $rcq >= 0.95) || ($avaliacao['sexo'] === 'feminino' && $rcq >= 0.80)) {
        $sugestao .= "\nAtenção: RCQ indica risco cardiovascular aumentado - monitorar intensidade e incluir aquecimento/desaquecimento adequados.";
    }

    return $sugestao;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Treino - Sistema de Avaliações</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Criar Plano de Treino</h1>
            <a href="../clientes/detalhes.php?id=<?php echo $clienteId; ?>"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center space-x-4">
                <?php if ($cliente['foto']): ?>
                <img src="../../<?php echo htmlspecialchars($cliente['foto']); ?>" alt="Foto"
                    class="h-16 w-16 rounded-full object-cover">
                <?php else: ?>
                <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($cliente['nome']); ?></h2>
                    <p class="text-gray-600">
                        Avaliação em: <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                        | IMC: <?php echo number_format($avaliacao['imc'], 1); ?>
                        | % Gordura: <?php echo number_format($avaliacao['percentual_gordura'], 1); ?>%
                    </p>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Informações do Treino</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="objetivo" class="block text-sm font-medium text-gray-700">Objetivo *</label>
                        <input type="text" id="objetivo" name="objetivo" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($objetivoPadrao); ?>">
                    </div>

                    <div>
                        <label for="duracao" class="block text-sm font-medium text-gray-700">Duração (minutos) *</label>
                        <input type="number" id="duracao" name="duracao" required min="15" max="180"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="60">
                    </div>

                    <div>
                        <label for="dias_semana" class="block text-sm font-medium text-gray-700">Dias por semana
                            *</label>
                        <select id="dias_semana" name="dias_semana" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="2">2 dias</option>
                            <option value="3" selected>3 dias</option>
                            <option value="4">4 dias</option>
                            <option value="5">5 dias</option>
                            <option value="6">6 dias</option>
                        </select>
                    </div>

                    <div>
                        <label for="data_inicio" class="block text-sm font-medium text-gray-700">Data de início
                            *</label>
                        <input type="date" id="data_inicio" name="data_inicio" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div>
                        <label for="data_fim" class="block text-sm font-medium text-gray-700">Data de término</label>
                        <input type="date" id="data_fim" name="data_fim"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Plano de Treino *</h2>
                <textarea id="treino" name="treino" rows="12" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($sugestao); ?></textarea>
                <p class="mt-2 text-sm text-gray-500">
                    Sugestão gerada automaticamente com base na avaliação física. Edite conforme necessário.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Observações</h2>
                <textarea id="observacoes" name="observacoes" rows="4"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Restrições, adaptações necessárias, recomendações especiais..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="../clientes/detalhes.php?id=<?php echo $clienteId; ?>"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-times mr-2"></i> Cancelar
                </a>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-save mr-2"></i> Salvar Treino
                </button>
            </div>
        </form>
    </div>
</body>

</html>