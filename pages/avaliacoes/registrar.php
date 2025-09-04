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

        // Upload de fotos
        $fotoFrontal = uploadFoto('foto_frontal', 'evaluations');
        $fotoLateral = uploadFoto('foto_lateral', 'evaluations');
        $fotoPosterior = uploadFoto('foto_posterior', 'evaluations');

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

        // Inserir avaliação no banco de dados (query atualizada)
        $stmt = $db->prepare("
    INSERT INTO avaliacoes (
        cliente_id, data_avaliacao, peso, altura, imc, percentual_gordura, massa_magra, massa_gorda,
        circunferencia_abdominal, circunferencia_quadril, rcq, pressao_arterial, frequencia_cardiaca,
        flexibilidade, resistencia, forca, foto_frontal, foto_lateral, foto_posterior, observacoes,
        anamnese_completa, parq, aha, perimetro_braco, perimetro_antebraco, perimetro_coxa,
        perimetro_panturrilha, massa_muscular, usuario_id
    ) VALUES (
        :cliente_id, :data_avaliacao, :peso, :altura, :imc, :percentual_gordura, :massa_magra, :massa_gorda,
        :circunferencia_abdominal, :circunferencia_quadril, :rcq, :pressao_arterial, :frequencia_cardiaca,
        :flexibilidade, :resistencia, :forca, :foto_frontal, :foto_lateral, :foto_posterior, :observacoes,
        :anamnese_completa, :parq, :aha, :perimetro_braco, :perimetro_antebraco, :perimetro_coxa,
        :perimetro_panturrilha, :massa_muscular, :usuario_id
    )
");

        // Bind dos parâmetros
        $stmt->bindValue(':cliente_id', $clienteId);
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
        $stmt->bindValue(':usuario_id', getCurrentUserId());
        $stmt->bindValue(':anamnese_completa', $anamneseCompleta);
        $stmt->bindValue(':parq', $parq);
        $stmt->bindValue(':aha', $aha);
        $stmt->bindValue(':perimetro_braco', $perimetroBraco);
        $stmt->bindValue(':perimetro_antebraco', $perimetroAntebraco);
        $stmt->bindValue(':perimetro_coxa', $perimetroCoxa);
        $stmt->bindValue(':perimetro_panturrilha', $perimetroPanturrilha);
        $stmt->bindValue(':massa_muscular', $massaMuscular);

        if ($stmt->execute()) {
            $success = 'Avaliação registrada com sucesso!';
            // Redirecionar para a página de detalhes da avaliação
            $avaliacaoId = $db->lastInsertRowID();
            header("Location: detalhes.php?id=$avaliacaoId");
            exit();
        } else {
            $error = 'Erro ao registrar avaliação.';
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
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Registrar Avaliação Física</h1>
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
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Dados Básicos</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="data_avaliacao" class="block text-sm font-medium text-gray-700">Data da Avaliação
                        *</label>
                    <input type="date" id="data_avaliacao" name="data_avaliacao" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div>
                    <label for="peso" class="block text-sm font-medium text-gray-700">Peso (kg) *</label>
                    <input type="number" id="peso" name="peso" step="0.1" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="altura" class="block text-sm font-medium text-gray-700">Altura (cm) *</label>
                    <input type="number" id="altura" name="altura" step="0.1" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo htmlspecialchars($cliente['altura'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Medidas Corporais</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="percentual_gordura" class="block text-sm font-medium text-gray-700">%
                        Gordura</label>
                    <input type="number" id="percentual_gordura" name="percentual_gordura" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="massa_magra" class="block text-sm font-medium text-gray-700">Massa Magra
                        (kg)</label>
                    <input type="number" id="massa_magra" name="massa_magra" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="massa_gorda" class="block text-sm font-medium text-gray-700">Massa Gorda
                        (kg)</label>
                    <input type="number" id="massa_gorda" name="massa_gorda" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="circunferencia_abdominal" class="block text-sm font-medium text-gray-700">Circunferência
                        Abdominal (cm)</label>
                    <input type="number" id="circunferencia_abdominal" name="circunferencia_abdominal" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="circunferencia_quadril" class="block text-sm font-medium text-gray-700">Circunferência
                        Quadril (cm)</label>
                    <input type="number" id="circunferencia_quadril" name="circunferencia_quadril" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Seção de Perímetros -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Perímetros Corporais (cm)</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="perimetro_braco" class="block text-sm font-medium text-gray-700">Braço</label>
                    <input type="number" id="perimetro_braco" name="perimetro_braco" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="perimetro_antebraco" class="block text-sm font-medium text-gray-700">Antebraço</label>
                    <input type="number" id="perimetro_antebraco" name="perimetro_antebraco" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="perimetro_coxa" class="block text-sm font-medium text-gray-700">Coxa</label>
                    <input type="number" id="perimetro_coxa" name="perimetro_coxa" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="perimetro_panturrilha"
                        class="block text-sm font-medium text-gray-700">Panturrilha</label>
                    <input type="number" id="perimetro_panturrilha" name="perimetro_panturrilha" step="0.1"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Seção de Anamnese -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Anamnese Completa</h2>

            <div class="mb-4">
                <label for="anamnese_completa" class="block text-sm font-medium text-gray-700">Anamnese</label>
                <textarea id="anamnese_completa" name="anamnese_completa" rows="4"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <label for="parq" class="block text-sm font-medium text-gray-700">Questionário PAR-Q</label>
                    <textarea id="parq" name="parq" rows="4"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div>
                    <label for="aha" class="block text-sm font-medium text-gray-700">Questionário AHA</label>
                    <textarea id="aha" name="aha" rows="4"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </div>
        </div>



        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Saúde</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="pressao_arterial" class="block text-sm font-medium text-gray-700">Pressão
                        Arterial</label>
                    <input type="text" id="pressao_arterial" name="pressao_arterial"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ex: 120/80">
                </div>

                <div>
                    <label for="frequencia_cardiaca" class="block text-sm font-medium text-gray-700">Frequência
                        Cardíaca (bpm)</label>
                    <input type="number" id="frequencia_cardiaca" name="frequencia_cardiaca"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Capacidades Físicas</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="flexibilidade" class="block text-sm font-medium text-gray-700">Flexibilidade</label>
                    <select id="flexibilidade" name="flexibilidade"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione</option>
                        <option value="ruim">Ruim</option>
                        <option value="regular">Regular</option>
                        <option value="boa">Boa</option>
                        <option value="excelente">Excelente</option>
                    </select>
                </div>

                <div>
                    <label for="resistencia" class="block text-sm font-medium text-gray-700">Resistência</label>
                    <select id="resistencia" name="resistencia"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione</option>
                        <option value="ruim">Ruim</option>
                        <option value="regular">Regular</option>
                        <option value="boa">Boa</option>
                        <option value="excelente">Excelente</option>
                    </select>
                </div>

                <div>
                    <label for="forca" class="block text-sm font-medium text-gray-700">Força</label>
                    <select id="forca" name="forca"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione</option>
                        <option value="ruim">Ruim</option>
                        <option value="regular">Regular</option>
                        <option value="boa">Boa</option>
                        <option value="excelente">Excelente</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Fotos da Avaliação</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="foto_frontal" class="block text-sm font-medium text-gray-700">Foto Frontal</label>
                    <input type="file" id="foto_frontal" name="foto_frontal" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div>
                    <label for="foto_lateral" class="block text-sm font-medium text-gray-700">Foto Lateral</label>
                    <input type="file" id="foto_lateral" name="foto_lateral" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div>
                    <label for="foto_posterior" class="block text-sm font-medium text-gray-700">Foto
                        Posterior</label>
                    <input type="file" id="foto_posterior" name="foto_posterior" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Observações</h2>
            <textarea id="observacoes" name="observacoes" rows="4"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <i class="fas fa-save mr-2"></i> Salvar Avaliação
            </button>
        </div>
    </form>
</div>
<?php include '../../includes/footer.php'; ?>
</body>

</html>