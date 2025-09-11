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
$avaliacao = [];
$cliente = [];

// Obter dados da avaliação
$stmt = $db->prepare("
    SELECT a.*, c.nome as cliente_nome, c.foto as cliente_foto, c.data_nascimento, c.sexo, c.altura as cliente_altura
    FROM avaliacoes a
    JOIN clientes c ON a.cliente_id = c.id
    WHERE a.id = :id AND a.usuario_id = :usuario_id
");
$stmt->bindValue(':id', $avaliacaoId);
$stmt->bindValue(':usuario_id', getCurrentUserId());
$result = $stmt->execute();
$avaliacao = $result->fetchArray(SQLITE3_ASSOC);

if (!$avaliacao) {
    header('Location: listar.php');
    exit();
}

$clienteId = $avaliacao['cliente_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Dados básicos
        $dataAvaliacao = $_POST['data_avaliacao'];
        $peso = $_POST['peso'];
        $altura = $_POST['altura'];
        $imc = $peso / (($altura / 100) * ($altura / 100));

        // Medidas corporais
        $percentualGordura = $_POST['percentual_gordura'];
        $massaMagra = $_POST['massa_magra'];
        $massaGorda = $_POST['massa_gorda'];
        $circunferenciaAbdominal = $_POST['circunferencia_abdominal'];
        $circunferenciaQuadril = $_POST['circunferencia_quadril'];
        $rcq = $circunferenciaAbdominal / $circunferenciaQuadril;

        // Saúde
        $pressaoArterial = $_POST['pressao_arterial'];
        $frequenciaCardiaca = $_POST['frequencia_cardiaca'];

        // Capacidades físicas
        $flexibilidade = $_POST['flexibilidade'];
        $resistencia = $_POST['resistencia'];
        $forca = $_POST['forca'];

        // Observações
        $observacoes = $_POST['observacoes'];

        // Novos campos: Anamnese
        $anamneseCompleta = $_POST['anamnese_completa'] ?? null;
        $parq = $_POST['parq'] ?? null;
        $aha = $_POST['aha'] ?? null;

        // Novos campos: Perímetros
        $perimetroBraco = $_POST['perimetro_braco'] ?? null;
        $perimetroAntebraco = $_POST['perimetro_antebraco'] ?? null;
        $perimetroCoxa = $_POST['perimetro_coxa'] ?? null;
        $perimetroPanturrilha = $_POST['perimetro_panturrilha'] ?? null;

        // Cálculo da massa muscular
        $massaMuscular = null;
        if ($peso && $percentualGordura) {
            $massaMuscular = $peso * (1 - ($percentualGordura / 100));
        }

        // Verificar se novas fotos foram enviadas
        $fotoFrontal = $avaliacao['foto_frontal'];
        $fotoLateral = $avaliacao['foto_lateral'];
        $fotoPosterior = $avaliacao['foto_posterior'];

        if (!empty($_FILES['foto_frontal']['name'])) {
            $fotoFrontal = uploadFoto('foto_frontal', 'evaluations');
        }
        if (!empty($_FILES['foto_lateral']['name'])) {
            $fotoLateral = uploadFoto('foto_lateral', 'evaluations');
        }
        if (!empty($_FILES['foto_posterior']['name'])) {
            $fotoPosterior = uploadFoto('foto_posterior', 'evaluations');
        }

        // Atualizar avaliação no banco de dados (query atualizada)
        $stmt = $db->prepare("
            UPDATE avaliacoes SET
                data_avaliacao = :data_avaliacao,
                peso = :peso,
                altura = :altura,
                imc = :imc,
                percentual_gordura = :percentual_gordura,
                massa_magra = :massa_magra,
                massa_gorda = :massa_gorda,
                circunferencia_abdominal = :circunferencia_abdominal,
                circunferencia_quadril = :circunferencia_quadril,
                rcq = :rcq,
                pressao_arterial = :pressao_arterial,
                frequencia_cardiaca = :frequencia_cardiaca,
                flexibilidade = :flexibilidade,
                resistencia = :resistencia,
                forca = :forca,
                foto_frontal = :foto_frontal,
                foto_lateral = :foto_lateral,
                foto_posterior = :foto_posterior,
                observacoes = :observacoes,
                anamnese_completa = :anamnese_completa,
                parq = :parq,
                aha = :aha,
                perimetro_braco = :perimetro_braco,
                perimetro_antebraco = :perimetro_antebraco,
                perimetro_coxa = :perimetro_coxa,
                perimetro_panturrilha = :perimetro_panturrilha,
                massa_muscular = :massa_muscular
            WHERE id = :id AND usuario_id = :usuario_id
        ");

        // Bind dos parâmetros originais
        $stmt->bindValue(':data_avaliacao', $dataAvaliacao);
        $stmt->bindValue(':peso', $peso);
        $stmt->bindValue(':altura', $altura);
        $stmt->bindValue(':imc', $imc);
        $stmt->bindValue(':percentual_gordura', $percentualGordura);
        $stmt->bindValue(':massa_magra', $massaMagra);
        $stmt->bindValue(':massa_gorda', $massaGorda);
        $stmt->bindValue(':circunferencia_abdominal', $circunferenciaAbdominal);
        $stmt->bindValue(':circunferencia_quadril', $circunferenciaQuadril);
        $stmt->bindValue(':rcq', $rcq);
        $stmt->bindValue(':pressao_arterial', $pressaoArterial);
        $stmt->bindValue(':frequencia_cardiaca', $frequenciaCardiaca);
        $stmt->bindValue(':flexibilidade', $flexibilidade);
        $stmt->bindValue(':resistencia', $resistencia);
        $stmt->bindValue(':forca', $forca);
        $stmt->bindValue(':foto_frontal', $fotoFrontal);
        $stmt->bindValue(':foto_lateral', $fotoLateral);
        $stmt->bindValue(':foto_posterior', $fotoPosterior);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':id', $avaliacaoId);
        $stmt->bindValue(':usuario_id', getCurrentUserId());

        // Bind dos novos parâmetros
        $stmt->bindValue(':anamnese_completa', $anamneseCompleta);
        $stmt->bindValue(':parq', $parq);
        $stmt->bindValue(':aha', $aha);
        $stmt->bindValue(':perimetro_braco', $perimetroBraco);
        $stmt->bindValue(':perimetro_antebraco', $perimetroAntebraco);
        $stmt->bindValue(':perimetro_coxa', $perimetroCoxa);
        $stmt->bindValue(':perimetro_panturrilha', $perimetroPanturrilha);
        $stmt->bindValue(':massa_muscular', $massaMuscular);

        if ($stmt->execute()) {
            $success = 'Avaliação atualizada com sucesso!';
            // Redirecionar para a página de detalhes da avaliação
            header("Location: detalhes.php?id=$avaliacaoId");
            exit();
        } else {
            $error = 'Erro ao atualizar avaliação.';
        }
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

function uploadFoto($fieldName, $subDir)
{
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../uploads/$subDir/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $filePath)) {
            return "uploads/$subDir/" . $fileName;
        }
    }
    return null;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4 md:mb-0">Editar Avaliação Física</h1>
        <a href="detalhes.php?id=<?php echo $avaliacaoId; ?>"
            class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-medium py-2 px-4 rounded-lg inline-flex items-center transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Detalhes
        </a>
    </div>

    <!-- Card de informações do cliente -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6">
        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
            <?php if ($avaliacao['cliente_foto']): ?>
            <div class="relative">
                <img src="../../<?php echo htmlspecialchars($avaliacao['cliente_foto']); ?>" alt="Foto do cliente"
                    class="h-20 w-20 rounded-full object-cover border-4 border-white dark:border-gray-800 shadow">
                <div class="absolute -bottom-1 -right-1 bg-blue-100 dark:bg-blue-900 rounded-full p-1">
                    <i class="fas fa-user text-blue-600 dark:text-blue-400 text-xs"></i>
                </div>
            </div>
            <?php else: ?>
            <div
                class="h-20 w-20 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center border-4 border-white dark:border-gray-800 shadow">
                <i class="fas fa-user text-gray-400 text-3xl"></i>
            </div>
            <?php endif; ?>
            <div class="text-center sm:text-left">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                    <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?></h2>
                <div class="flex flex-wrap justify-center sm:justify-start gap-3 mt-2">
                    <?php if (!empty($avaliacao['data_nascimento'])):
                        $dataNasc = new DateTime($avaliacao['data_nascimento']);
                        $hoje = new DateTime();
                        $idade = $hoje->diff($dataNasc)->y;
                    ?>
                    <span
                        class="bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <i class="fas fa-birthday-cake mr-1"></i> <?php echo $idade; ?> anos
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($avaliacao['sexo'])): ?>
                    <span
                        class="bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <i
                            class="fas fa-<?php echo $avaliacao['sexo'] === 'masculino' ? 'male' : 'female'; ?> mr-1"></i>
                        <?php echo ucfirst($avaliacao['sexo']); ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($avaliacao['cliente_altura'])): ?>
                    <span
                        class="bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        <i class="fas fa-ruler-vertical mr-1"></i> <?php echo $avaliacao['cliente_altura']; ?> cm
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="avaliacaoForm">
        <!-- Sistema de abas para organização -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex flex-col sm:flex-row -mb-px">
                    <button type="button"
                        class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm whitespace-nowrap border-blue-500 text-blue-600 dark:text-blue-400"
                        data-tab="dados-basicos">
                        <i class="fas fa-info-circle mr-2"></i> Dados Básicos
                    </button>
                    <button type="button"
                        class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600"
                        data-tab="medidas">
                        <i class="fas fa-ruler-combined mr-2"></i> Medidas
                    </button>
                    <button type="button"
                        class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600"
                        data-tab="anamnese">
                        <i class="fas fa-file-medical mr-2"></i> Anamnese
                    </button>
                    <button type="button"
                        class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600"
                        data-tab="saude">
                        <i class="fas fa-heartbeat mr-2"></i> Saúde
                    </button>
                    <button type="button"
                        class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600"
                        data-tab="fotos">
                        <i class="fas fa-camera mr-2"></i> Fotos
                    </button>
                    <button type="button"
                        class="tab-button py-4 px-6 text-center border-b-2 font-medium text-sm whitespace-nowrap border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600"
                        data-tab="observacoes">
                        <i class="fas fa-sticky-note mr-2"></i> Observações
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Aba: Dados Básicos -->
                <div class="tab-content active" id="dados-basicos-content">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Dados Básicos da Avaliação</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="data_avaliacao"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data da
                                Avaliação *</label>
                            <input type="date" id="data_avaliacao" name="data_avaliacao" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['data_avaliacao']); ?>">
                        </div>

                        <div>
                            <label for="peso"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peso (kg)
                                *</label>
                            <input type="number" id="peso" name="peso" step="0.1" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['peso']); ?>" oninput="calcularIMC()">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Digite o peso em quilogramas</p>
                        </div>

                        <div>
                            <label for="altura"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Altura (cm)
                                *</label>
                            <input type="number" id="altura" name="altura" step="0.1" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['altura']); ?>" oninput="calcularIMC()">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Digite a altura em centímetros</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <h3 class="text-lg font-medium text-blue-800 dark:text-blue-300 mb-2">Resultados Calculados</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-blue-700 dark:text-blue-300">Índice de Massa Corporal (IMC):</p>
                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-200" id="imc-resultado">
                                    <?php echo isset($avaliacao['imc']) ? number_format($avaliacao['imc'], 2) : '0.00'; ?>
                                </p>
                                <p class="text-xs text-blue-600 dark:text-blue-400" id="imc-classificacao">
                                    <?php
                                    if (isset($avaliacao['imc'])) {
                                        if ($avaliacao['imc'] < 18.5) echo 'Abaixo do peso';
                                        elseif ($avaliacao['imc'] < 25) echo 'Peso normal';
                                        elseif ($avaliacao['imc'] < 30) echo 'Sobrepeso';
                                        elseif ($avaliacao['imc'] < 35) echo 'Obesidade Grau I';
                                        elseif ($avaliacao['imc'] < 40) echo 'Obesidade Grau II';
                                        else echo 'Obesidade Grau III';
                                    } else {
                                        echo 'Classificação';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aba: Medidas Corporais -->
                <div class="tab-content hidden" id="medidas-content">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Medidas Corporais</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label for="percentual_gordura"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">%
                                Gordura Corporal</label>
                            <div class="relative">
                                <input type="number" id="percentual_gordura" name="percentual_gordura" step="0.1"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white pr-10"
                                    value="<?php echo htmlspecialchars($avaliacao['percentual_gordura']); ?>"
                                    oninput="calcularMassaMuscular()">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400">%</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="massa_magra"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Massa Magra
                                (kg)</label>
                            <input type="number" id="massa_magra" name="massa_magra" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['massa_magra']); ?>">
                        </div>

                        <div>
                            <label for="massa_gorda"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Massa Gorda
                                (kg)</label>
                            <input type="number" id="massa_gorda" name="massa_gorda" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['massa_gorda']); ?>">
                        </div>

                        <div>
                            <label for="circunferencia_abdominal"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Circunferência
                                Abdominal
                                (cm)</label>
                            <input type="number" id="circunferencia_abdominal" name="circunferencia_abdominal"
                                step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['circunferencia_abdominal']); ?>"
                                oninput="calcularRCQ()">
                        </div>

                        <div>
                            <label for="circunferencia_quadril"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Circunferência
                                Quadril (cm)</label>
                            <input type="number" id="circunferencia_quadril" name="circunferencia_quadril" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['circunferencia_quadril']); ?>"
                                oninput="calcularRCQ()">
                        </div>

                        <div>
                            <label for="perimetro_braco"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Perímetro
                                do Braço (cm)</label>
                            <input type="number" id="perimetro_braco" name="perimetro_braco" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['perimetro_braco'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="perimetro_antebraco"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Perímetro do
                                Antebraço (cm)</label>
                            <input type="number" id="perimetro_antebraco" name="perimetro_antebraco" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['perimetro_antebraco'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="perimetro_coxa"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Perímetro
                                da Coxa (cm)</label>
                            <input type="number" id="perimetro_coxa" name="perimetro_coxa" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['perimetro_coxa'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="perimetro_panturrilha"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Perímetro da
                                Panturrilha
                                (cm)</label>
                            <input type="number" id="perimetro_panturrilha" name="perimetro_panturrilha" step="0.1"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['perimetro_panturrilha'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                        <h3 class="text-lg font-medium text-indigo-800 dark:text-indigo-300 mb-2">Resultados Calculados
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">Relação Cintura-Quadril (RCQ):
                                </p>
                                <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-200" id="rcq-resultado">
                                    <?php echo isset($avaliacao['rcq']) ? number_format($avaliacao['rcq'], 2) : '0.00'; ?>
                                </p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400" id="rcq-classificacao">
                                    <?php
                                    if (isset($avaliacao['rcq'])) {
                                        if ($avaliacao['sexo'] === 'masculino') {
                                            if ($avaliacao['rcq'] < 0.90) echo 'Baixo risco';
                                            elseif ($avaliacao['rcq'] < 0.99) echo 'Risco moderado';
                                            else echo 'Alto risco';
                                        } else {
                                            if ($avaliacao['rcq'] < 0.80) echo 'Baixo risco';
                                            elseif ($avaliacao['rcq'] < 0.84) echo 'Risco moderado';
                                            else echo 'Alto risco';
                                        }
                                    } else {
                                        echo 'Classificação';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">Massa Muscular Estimada:</p>
                                <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-200"
                                    id="massa-muscular-resultado">
                                    <?php echo isset($avaliacao['massa_muscular']) ? number_format($avaliacao['massa_muscular'], 2) . ' kg' : '0.00 kg'; ?>
                                </p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400">Calculado a partir do peso e %
                                    de gordura</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aba: Anamnese -->
                <div class="tab-content hidden" id="anamnese-content">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Anamnese Completa</h2>

                    <div class="mb-6">
                        <label for="anamnese_completa"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Anamnese</label>
                        <textarea id="anamnese_completa" name="anamnese_completa" rows="6"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                            placeholder="Descreva a anamnese completa do cliente..."><?php echo htmlspecialchars($avaliacao['anamnese_completa'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="parq"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Questionário
                                PAR-Q</label>
                            <textarea id="parq" name="parq" rows="6"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                placeholder="Registre as respostas do questionário PAR-Q..."><?php echo htmlspecialchars($avaliacao['parq'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="aha"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Questionário
                                AHA</label>
                            <textarea id="aha" name="aha" rows="6"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                placeholder="Registre as respostas do questionário AHA..."><?php echo htmlspecialchars($avaliacao['aha'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Aba: Saúde -->
                <div class="tab-content hidden" id="saude-content">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Indicadores de Saúde</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pressao_arterial"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pressão
                                Arterial</label>
                            <input type="text" id="pressao_arterial" name="pressao_arterial"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                placeholder="Ex: 120/80"
                                value="<?php echo htmlspecialchars($avaliacao['pressao_arterial']); ?>">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Formato: sistólica/diastólica (ex:
                                120/80)</p>
                        </div>

                        <div>
                            <label for="frequencia_cardiaca"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequência
                                Cardíaca (bpm)</label>
                            <input type="number" id="frequencia_cardiaca" name="frequencia_cardiaca"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                                value="<?php echo htmlspecialchars($avaliacao['frequencia_cardiaca']); ?>">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Batimentos por minuto em repouso
                            </p>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mt-8 mb-4">Capacidades Físicas</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="flexibilidade"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Flexibilidade</label>
                            <select id="flexibilidade" name="flexibilidade"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
                                <option value="">Selecione uma opção</option>
                                <option value="ruim"
                                    <?php echo $avaliacao['flexibilidade'] === 'ruim' ? 'selected' : ''; ?>>Ruim
                                </option>
                                <option value="regular"
                                    <?php echo $avaliacao['flexibilidade'] === 'regular' ? 'selected' : ''; ?>>Regular
                                </option>
                                <option value="boa"
                                    <?php echo $avaliacao['flexibilidade'] === 'boa' ? 'selected' : ''; ?>>Boa</option>
                                <option value="excelente"
                                    <?php echo $avaliacao['flexibilidade'] === 'excelente' ? 'selected' : ''; ?>>
                                    Excelente</option>
                            </select>
                        </div>

                        <div>
                            <label for="resistencia"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resistência</label>
                            <select id="resistencia" name="resistencia"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
                                <option value="">Selecione uma opção</option>
                                <option value="ruim"
                                    <?php echo $avaliacao['resistencia'] === 'ruim' ? 'selected' : ''; ?>>Ruim</option>
                                <option value="regular"
                                    <?php echo $avaliacao['resistencia'] === 'regular' ? 'selected' : ''; ?>>Regular
                                </option>
                                <option value="boa"
                                    <?php echo $avaliacao['resistencia'] === 'boa' ? 'selected' : ''; ?>>Boa</option>
                                <option value="excelente"
                                    <?php echo $avaliacao['resistencia'] === 'excelente' ? 'selected' : ''; ?>>Excelente
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="forca"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Força</label>
                            <select id="forca" name="forca"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
                                <option value="">Selecione uma opção</option>
                                <option value="ruim" <?php echo $avaliacao['forca'] === 'ruim' ? 'selected' : ''; ?>>
                                    Ruim</option>
                                <option value="regular"
                                    <?php echo $avaliacao['forca'] === 'regular' ? 'selected' : ''; ?>>Regular</option>
                                <option value="boa" <?php echo $avaliacao['forca'] === 'boa' ? 'selected' : ''; ?>>Boa
                                </option>
                                <option value="excelente"
                                    <?php echo $avaliacao['forca'] === 'excelente' ? 'selected' : ''; ?>>Excelente
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Aba: Fotos -->
                <div class="tab-content hidden" id="fotos-content">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Fotos da Avaliação</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Faça upload de novas fotos ou visualize as
                        atuais. As fotos
                        ajudam a acompanhar a evolução do cliente.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <label for="foto_frontal"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Foto
                                Frontal</label>
                            <div class="relative mb-3">
                                <?php if ($avaliacao['foto_frontal']): ?>
                                <img src="../../<?php echo htmlspecialchars($avaliacao['foto_frontal']); ?>"
                                    alt="Foto Frontal"
                                    class="h-48 w-full object-cover rounded-lg shadow-md border border-gray-200 dark:border-gray-600"
                                    id="preview-frontal">
                                <?php else: ?>
                                <div class="h-48 w-full bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600"
                                    id="preview-frontal">
                                    <div class="text-center">
                                        <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Nenhuma imagem</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <label for="foto_frontal"
                                class="cursor-pointer bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 border border-blue-600 dark:border-blue-500 rounded-lg py-2 px-4 inline-flex items-center justify-center text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-200">
                                <i class="fas fa-upload mr-2"></i>
                                <span>Alterar</span>
                                <input type="file" id="foto_frontal" name="foto_frontal" accept="image/*" class="hidden"
                                    onchange="previewImage(this, 'preview-frontal')">
                            </label>
                        </div>

                        <div class="text-center">
                            <label for="foto_lateral"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Foto
                                Lateral</label>
                            <div class="relative mb-3">
                                <?php if ($avaliacao['foto_lateral']): ?>
                                <img src="../../<?php echo htmlspecialchars($avaliacao['foto_lateral']); ?>"
                                    alt="Foto Lateral"
                                    class="h-48 w-full object-cover rounded-lg shadow-md border border-gray-200 dark:border-gray-600"
                                    id="preview-lateral">
                                <?php else: ?>
                                <div class="h-48 w-full bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600"
                                    id="preview-lateral">
                                    <div class="text-center">
                                        <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Nenhuma imagem</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <label for="foto_lateral"
                                class="cursor-pointer bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 border border-blue-600 dark:border-blue-500 rounded-lg py-2 px-4 inline-flex items-center justify-center text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-200">
                                <i class="fas fa-upload mr-2"></i>
                                <span>Alterar</span>
                                <input type="file" id="foto_lateral" name="foto_lateral" accept="image/*" class="hidden"
                                    onchange="previewImage(this, 'preview-lateral')">
                            </label>
                        </div>

                        <div class="text-center">
                            <label for="foto_posterior"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Foto
                                Posterior</label>
                            <div class="relative mb-3">
                                <?php if ($avaliacao['foto_posterior']): ?>
                                <img src="../../<?php echo htmlspecialchars($avaliacao['foto_posterior']); ?>"
                                    alt="Foto Posterior"
                                    class="h-48 w-full object-cover rounded-lg shadow-md border border-gray-200 dark:border-gray-600"
                                    id="preview-posterior">
                                <?php else: ?>
                                <div class="h-48 w-full bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600"
                                    id="preview-posterior">
                                    <div class="text-center">
                                        <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Nenhuma imagem</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <label for="foto_posterior"
                                class="cursor-pointer bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 border border-blue-600 dark:border-blue-500 rounded-lg py-2 px-4 inline-flex items-center justify-center text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-200">
                                <i class="fas fa-upload mr-2"></i>
                                <span>Alterar</span>
                                <input type="file" id="foto_posterior" name="foto_posterior" accept="image/*"
                                    class="hidden" onchange="previewImage(this, 'preview-posterior')">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Aba: Observações -->
                <div class="tab-content hidden" id="observacoes-content">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Observações e Considerações
                        Finais</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Registre aqui observações relevantes,
                        considerações finais e
                        recomendações para o cliente.</p>

                    <div class="mb-6">
                        <label for="observacoes"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="8"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                            placeholder="Descreva observações relevantes sobre a avaliação..."><?php echo htmlspecialchars($avaliacao['observacoes']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col-reverse sm:flex-row justify-between items-center space-y-4 space-y-reverse sm:space-y-0 sm:space-x-4 pt-6">
            <a href="detalhes.php?id=<?php echo $avaliacaoId; ?>"
                class="w-full sm:w-auto bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-medium py-2.5 px-6 rounded-lg inline-flex items-center justify-center transition-colors duration-200">
                <i class="fas fa-times mr-2"></i> Cancelar
            </a>
            <button type="submit"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-lg inline-flex items-center justify-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                <i class="fas fa-save mr-2"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

<script>
// Sistema de abas
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
        // Remover classe ativa de todos os botões
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });

        // Adicionar classe ativa ao botão clicado
        button.classList.remove('border-transparent', 'text-gray-500');
        button.classList.add('border-blue-500', 'text-blue-600');

        // Esconder todos os conteúdos
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('active');
        });

        // Mostrar o conteúdo correspondente
        const tabId = button.getAttribute('data-tab');
        document.getElementById(`${tabId}-content`).classList.remove('hidden');
        document.getElementById(`${tabId}-content`).classList.add('active');
    });
});

// Cálculo automático do IMC
function calcularIMC() {
    const peso = parseFloat(document.getElementById('peso').value) || 0;
    const altura = parseFloat(document.getElementById('altura').value) || 0;

    if (peso > 0 && altura > 0) {
        const alturaMetros = altura / 100;
        const imc = peso / (alturaMetros * alturaMetros);
        document.getElementById('imc-resultado').textContent = imc.toFixed(2);

        // Classificação do IMC
        let classificacao = '';
        if (imc < 18.5) classificacao = 'Abaixo do peso';
        else if (imc < 25) classificacao = 'Peso normal';
        else if (imc < 30) classificacao = 'Sobrepeso';
        else if (imc < 35) classificacao = 'Obesidade Grau I';
        else if (imc < 40) classificacao = 'Obesidade Grau II';
        else classificacao = 'Obesidade Grau III';

        document.getElementById('imc-classificacao').textContent = classificacao;
    }
}

// Cálculo automático do RCQ
function calcularRCQ() {
    const abdominal = parseFloat(document.getElementById('circunferencia_abdominal').value) || 0;
    const quadril = parseFloat(document.getElementById('circunferencia_quadril').value) || 0;

    if (abdominal > 0 && quadril > 0) {
        const rcq = abdominal / quadril;
        document.getElementById('rcq-resultado').textContent = rcq.toFixed(2);

        // Classificação do RCQ (simplificado)
        // Nota: Em uma implementação real, precisaríamos saber o sexo do cliente
        let classificacao = '';
        if (rcq < 0.90) classificacao = 'Baixo risco';
        else if (rcq < 0.99) classificacao = 'Risco moderado';
        else classificacao = 'Alto risco';

        document.getElementById('rcq-classificacao').textContent = classificacao;
    }
}

// Cálculo automático da massa muscular
function calcularMassaMuscular() {
    const peso = parseFloat(document.getElementById('peso').value) || 0;
    const percentualGordura = parseFloat(document.getElementById('percentual_gordura').value) || 0;

    if (peso > 0 && percentualGordura > 0) {
        const massaMuscular = peso * (1 - (percentualGordura / 100));
        document.getElementById('massa-muscular-resultado').textContent = massaMuscular.toFixed(2) + ' kg';
    }
}

// Preview de imagens antes do upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Se for uma div, substituir por uma imagem
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = "Preview";
                img.className = "h-48 w-full object-cover rounded-lg shadow-md border border-gray-200";
                img.id = previewId;
                preview.parentNode.replaceChild(img, preview);
            }
        }

        reader.readAsDataURL(file);
    }
}

// Inicializar cálculos ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    calcularIMC();
    calcularRCQ();
    calcularMassaMuscular();
});
</script>

<?php include '../../includes/footer.php'; ?>