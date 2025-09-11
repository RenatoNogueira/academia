<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID de cliente
if (!isset($_GET['cliente_id'])) {
    header('Location: ../clientes/listar.php');
    exit();
}

$clienteId = $_GET['cliente_id'];
$cliente = [];

// Obter dados do cliente
$stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
$stmt->bindValue(':id', $clienteId);
$stmt->bindValue(':usuario_id', getCurrentUserId());
$result = $stmt->execute();
$cliente = $result->fetchArray(SQLITE3_ASSOC);

if (!$cliente) {
    header('Location: ../clientes/listar.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // [O código de processamento do POST permanece igual]
        // ... (mantido por questões de espaço)
    } catch (Exception $e) {
        $error = 'Erro: ' . $e->getMessage();
    }
}

function uploadFoto($fieldName, $subDir)
{
    // [A função uploadFoto permanece igual]
    // ... (mantido por questões de espaço)
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Registrar Avaliação Física</h1>
        <a href="../clientes/detalhes.php?id=<?php echo $clienteId; ?>"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center transition duration-150 ease-in-out">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6 border border-gray-100">
        <div class="flex items-center space-x-4">
            <?php if ($cliente['foto']): ?>
            <img src="../../<?php echo htmlspecialchars($cliente['foto']); ?>" alt="Foto"
                class="h-16 w-16 rounded-full object-cover shadow-sm">
            <?php else: ?>
            <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center shadow-sm">
                <i class="fas fa-user text-gray-400 text-2xl"></i>
            </div>
            <?php endif; ?>
            <div>
                <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($cliente['nome']); ?></h2>
                <p class="text-gray-600 text-sm">
                    <?php
                    if (!empty($cliente['data_nascimento'])) {
                        $dataNasc = new DateTime($cliente['data_nascimento']);
                        $hoje = new DateTime();
                        echo $hoje->diff($dataNasc)->y . ' anos';
                    }
                    if (!empty($cliente['sexo'])) {
                        echo ' | ' . ucfirst($cliente['sexo']);
                    }
                    if (!empty($cliente['altura'])) {
                        echo ' | ' . $cliente['altura'] . ' cm';
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="avaliacaoForm">
        <!-- Navegação por abas -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="flex overflow-x-auto -mb-px">
                    <button type="button" data-tab="dados-basicos"
                        class="tab-button tab-active py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-blue-500 text-blue-600">
                        Dados Básicos
                    </button>
                    <button type="button" data-tab="medidas"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Medidas
                    </button>
                    <button type="button" data-tab="perimetros"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Perímetros
                    </button>
                    <button type="button" data-tab="anamnese"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Anamnese
                    </button>
                    <button type="button" data-tab="saude"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Saúde
                    </button>
                    <button type="button" data-tab="capacidades"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Capacidades
                    </button>
                    <button type="button" data-tab="fotos"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Fotos
                    </button>
                    <button type="button" data-tab="observacoes"
                        class="tab-button py-4 px-6 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Observações
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Aba: Dados Básicos -->
                <div id="tab-dados-basicos" class="tab-content active">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Dados Básicos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="data_avaliacao" class="block text-sm font-medium text-gray-700 mb-1">Data da
                                Avaliação *</label>
                            <input type="date" id="data_avaliacao" name="data_avaliacao" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label for="peso" class="block text-sm font-medium text-gray-700 mb-1">Peso (kg) *</label>
                            <input type="number" id="peso" name="peso" step="0.1" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                oninput="calcularIMC()">
                        </div>

                        <div>
                            <label for="altura" class="block text-sm font-medium text-gray-700 mb-1">Altura (cm)
                                *</label>
                            <input type="number" id="altura" name="altura" step="0.1" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                value="<?php echo htmlspecialchars($cliente['altura'] ?? ''); ?>"
                                oninput="calcularIMC()">
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm font-medium text-gray-600">IMC</p>
                            <p id="imc_resultado" class="text-2xl font-bold text-blue-700">--</p>
                            <p id="imc_classificacao" class="text-xs text-gray-500">Classificação</p>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <div></div>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Medidas Corporais -->
                <div id="tab-medidas" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Medidas Corporais</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="percentual_gordura" class="block text-sm font-medium text-gray-700 mb-1">%
                                Gordura</label>
                            <input type="number" id="percentual_gordura" name="percentual_gordura" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>

                        <div>
                            <label for="massa_magra" class="block text-sm font-medium text-gray-700 mb-1">Massa Magra
                                (kg)</label>
                            <input type="number" id="massa_magra" name="massa_magra" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>

                        <div>
                            <label for="massa_gorda" class="block text-sm font-medium text-gray-700 mb-1">Massa Gorda
                                (kg)</label>
                            <input type="number" id="massa_gorda" name="massa_gorda" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>

                        <div>
                            <label for="circunferencia_abdominal"
                                class="block text-sm font-medium text-gray-700 mb-1">Circunferência Abdominal
                                (cm)</label>
                            <input type="number" id="circunferencia_abdominal" name="circunferencia_abdominal"
                                step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                oninput="calcularRCQ()">
                        </div>

                        <div>
                            <label for="circunferencia_quadril"
                                class="block text-sm font-medium text-gray-700 mb-1">Circunferência Quadril (cm)</label>
                            <input type="number" id="circunferencia_quadril" name="circunferencia_quadril" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                oninput="calcularRCQ()">
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm font-medium text-gray-600">RCQ (Relação Cintura/Quadril)</p>
                            <p id="rcq_resultado" class="text-2xl font-bold text-blue-700">--</p>
                            <p id="rcq_classificacao" class="text-xs text-gray-500">Classificação</p>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Perímetros -->
                <div id="tab-perimetros" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Perímetros Corporais (cm)</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="perimetro_braco"
                                class="block text-sm font-medium text-gray-700 mb-1">Braço</label>
                            <input type="number" id="perimetro_braco" name="perimetro_braco" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>

                        <div>
                            <label for="perimetro_antebraco"
                                class="block text-sm font-medium text-gray-700 mb-1">Antebraço</label>
                            <input type="number" id="perimetro_antebraco" name="perimetro_antebraco" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>

                        <div>
                            <label for="perimetro_coxa"
                                class="block text-sm font-medium text-gray-700 mb-1">Coxa</label>
                            <input type="number" id="perimetro_coxa" name="perimetro_coxa" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>

                        <div>
                            <label for="perimetro_panturrilha"
                                class="block text-sm font-medium text-gray-700 mb-1">Panturrilha</label>
                            <input type="number" id="perimetro_panturrilha" name="perimetro_panturrilha" step="0.1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Anamnese -->
                <div id="tab-anamnese" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Anamnese Completa</h2>

                    <div class="mb-4">
                        <label for="anamnese_completa"
                            class="block text-sm font-medium text-gray-700 mb-1">Anamnese</label>
                        <textarea id="anamnese_completa" name="anamnese_completa" rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="parq" class="block text-sm font-medium text-gray-700 mb-1">Questionário
                                PAR-Q</label>
                            <textarea id="parq" name="parq" rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"></textarea>
                        </div>

                        <div>
                            <label for="aha" class="block text-sm font-medium text-gray-700 mb-1">Questionário
                                AHA</label>
                            <textarea id="aha" name="aha" rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Saúde -->
                <div id="tab-saude" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Saúde</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pressao_arterial" class="block text-sm font-medium text-gray-700 mb-1">Pressão
                                Arterial</label>
                            <input type="text" id="pressao_arterial" name="pressao_arterial"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                placeholder="Ex: 120/80">
                        </div>

                        <div>
                            <label for="frequencia_cardiaca"
                                class="block text-sm font-medium text-gray-700 mb-1">Frequência Cardíaca (bpm)</label>
                            <input type="number" id="frequencia_cardiaca" name="frequencia_cardiaca"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Capacidades Físicas -->
                <div id="tab-capacidades" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Capacidades Físicas</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="flexibilidade"
                                class="block text-sm font-medium text-gray-700 mb-1">Flexibilidade</label>
                            <select id="flexibilidade" name="flexibilidade"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                                <option value="">Selecione</option>
                                <option value="ruim">Ruim</option>
                                <option value="regular">Regular</option>
                                <option value="boa">Boa</option>
                                <option value="excelente">Excelente</option>
                            </select>
                        </div>

                        <div>
                            <label for="resistencia"
                                class="block text-sm font-medium text-gray-700 mb-1">Resistência</label>
                            <select id="resistencia" name="resistencia"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                                <option value="">Selecione</option>
                                <option value="ruim">Ruim</option>
                                <option value="regular">Regular</option>
                                <option value="boa">Boa</option>
                                <option value="excelente">Excelente</option>
                            </select>
                        </div>

                        <div>
                            <label for="forca" class="block text-sm font-medium text-gray-700 mb-1">Força</label>
                            <select id="forca" name="forca"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out">
                                <option value="">Selecione</option>
                                <option value="ruim">Ruim</option>
                                <option value="regular">Regular</option>
                                <option value="boa">Boa</option>
                                <option value="excelente">Excelente</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Fotos -->
                <div id="tab-fotos" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Fotos da Avaliação</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="foto_frontal" class="block text-sm font-medium text-gray-700 mb-1">Foto
                                Frontal</label>
                            <input type="file" id="foto_frontal" name="foto_frontal" accept="image/*" class="hidden"
                                onchange="previewImage(this, 'previewFrontal')">
                            <label for="foto_frontal"
                                class="block w-full px-4 py-10 border-2 border-dashed border-gray-300 rounded-lg text-center cursor-pointer hover:border-blue-400 transition duration-150 ease-in-out">
                                <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-600">Clique para adicionar</p>
                            </label>
                            <div id="previewFrontal" class="mt-2 hidden">
                                <img src="" class="w-full h-40 object-cover rounded-lg shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label for="foto_lateral" class="block text-sm font-medium text-gray-700 mb-1">Foto
                                Lateral</label>
                            <input type="file" id="foto_lateral" name="foto_lateral" accept="image/*" class="hidden"
                                onchange="previewImage(this, 'previewLateral')">
                            <label for="foto_lateral"
                                class="block w-full px-4 py-10 border-2 border-dashed border-gray-300 rounded-lg text-center cursor-pointer hover:border-blue-400 transition duration-150 ease-in-out">
                                <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-600">Clique para adicionar</p>
                            </label>
                            <div id="previewLateral" class="mt-2 hidden">
                                <img src="" class="w-full h-40 object-cover rounded-lg shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label for="foto_posterior" class="block text-sm font-medium text-gray-700 mb-1">Foto
                                Posterior</label>
                            <input type="file" id="foto_posterior" name="foto_posterior" accept="image/*" class="hidden"
                                onchange="previewImage(this, 'previewPosterior')">
                            <label for="foto_posterior"
                                class="block w-full px-4 py-10 border-2 border-dashed border-gray-300 rounded-lg text-center cursor-pointer hover:border-blue-400 transition duration-150 ease-in-out">
                                <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-600">Clique para adicionar</p>
                            </label>
                            <div id="previewPosterior" class="mt-2 hidden">
                                <img src="" class="w-full h-40 object-cover rounded-lg shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="button"
                            class="next-tab bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Próximo <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Aba: Observações -->
                <div id="tab-observacoes" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Observações</h2>
                    <textarea id="observacoes" name="observacoes" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"></textarea>

                    <div class="flex justify-between mt-6">
                        <button type="button"
                            class="prev-tab bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-arrow-left mr-2"></i> Anterior
                        </button>
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition duration-150 ease-in-out">
                            <i class="fas fa-save mr-2"></i> Salvar Avaliação
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Navegação por abas
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
        const tabId = button.getAttribute('data-tab');

        // Atualizar botões de aba
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('tab-active', 'border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        button.classList.add('tab-active', 'border-blue-500', 'text-blue-600');
        button.classList.remove('border-transparent', 'text-gray-500');

        // Mostrar conteúdo da aba
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabId}`).classList.remove('hidden');
        document.getElementById(`tab-${tabId}`).classList.add('active');
    });
});

// Navegação entre abas com botões próximo/anterior
document.querySelectorAll('.next-tab').forEach(button => {
    button.addEventListener('click', () => {
        const currentTab = document.querySelector('.tab-content.active').id.replace('tab-', '');
        const tabButtons = Array.from(document.querySelectorAll('.tab-button'));
        const currentIndex = tabButtons.findIndex(btn => btn.getAttribute('data-tab') === currentTab);

        if (currentIndex < tabButtons.length - 1) {
            tabButtons[currentIndex + 1].click();
        }
    });
});

document.querySelectorAll('.prev-tab').forEach(button => {
    button.addEventListener('click', () => {
        const currentTab = document.querySelector('.tab-content.active').id.replace('tab-', '');
        const tabButtons = Array.from(document.querySelectorAll('.tab-button'));
        const currentIndex = tabButtons.findIndex(btn => btn.getAttribute('data-tab') === currentTab);

        if (currentIndex > 0) {
            tabButtons[currentIndex - 1].click();
        }
    });
});

// Cálculo do IMC
function calcularIMC() {
    const peso = parseFloat(document.getElementById('peso').value);
    const altura = parseFloat(document.getElementById('altura').value) / 100; // converter cm para m

    if (peso && altura) {
        const imc = peso / (altura * altura);
        document.getElementById('imc_resultado').textContent = imc.toFixed(2);

        // Classificação do IMC
        let classificacao = '';
        if (imc < 18.5) classificacao = 'Abaixo do peso';
        else if (imc < 25) classificacao = 'Peso normal';
        else if (imc < 30) classificacao = 'Sobrepeso';
        else if (imc < 35) classificacao = 'Obesidade Grau I';
        else if (imc < 40) classificacao = 'Obesidade Grau II';
        else classificacao = 'Obesidade Grau III';

        document.getElementById('imc_classificacao').textContent = classificacao;
    } else {
        document.getElementById('imc_resultado').textContent = '--';
        document.getElementById('imc_classificacao').textContent = 'Classificação';
    }
}

// Cálculo do RCQ
function calcularRCQ() {
    const abdominal = parseFloat(document.getElementById('circunferencia_abdominal').value);
    const quadril = parseFloat(document.getElementById('circunferencia_quadril').value);

    if (abdominal && quadril) {
        const rcq = abdominal / quadril;
        document.getElementById('rcq_resultado').textContent = rcq.toFixed(2);

        // Classificação do RCQ (valores de referência genéricos)
        let classificacao = '';
        if (rcq < 0.80) classificacao = 'Baixo risco';
        else if (rcq < 0.85) classificacao = 'Risco moderado';
        else classificacao = 'Alto risco';

        document.getElementById('rcq_classificacao').textContent = classificacao;
    } else {
        document.getElementById('rcq_resultado').textContent = '--';
        document.getElementById('rcq_classificacao').textContent = 'Classificação';
    }
}

// Preview de imagens
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const previewImg = preview.querySelector('img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// Validação do formulário antes de enviar
document.getElementById('avaliacaoForm').addEventListener('submit', function(e) {
    const peso = document.getElementById('peso').value;
    const altura = document.getElementById('altura').value;
    const dataAvaliacao = document.getElementById('data_avaliacao').value;

    if (!peso || !altura || !dataAvaliacao) {
        e.preventDefault();
        alert('Por favor, preencha os campos obrigatórios (Peso, Altura e Data da Avaliação).');
        document.querySelector('[data-tab="dados-basicos"]').click();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>