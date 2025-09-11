<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Verificar se foi passado um ID
if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit();
}

$agendamentoId = $_GET['id'];
$currentUserId = getCurrentUserId();

// Obter dados do agendamento
$stmt = $db->prepare("
    SELECT ag.*, c.nome as cliente_nome, c.telefone as cliente_telefone, c.foto as cliente_foto
    FROM agendamentos ag
    JOIN clientes c ON ag.cliente_id = c.id
    WHERE ag.id = :id AND ag.usuario_id = :usuario_id
");
$stmt->bindValue(':id', $agendamentoId);
$stmt->bindValue(':usuario_id', $currentUserId);
$result = $stmt->execute();
$agendamento = $result->fetchArray(SQLITE3_ASSOC);

if (!$agendamento) {
    redirectWithMessage('listar.php', 'error', 'Agendamento não encontrado.');
}

// Obter lista de clientes
$clientes = [];
$result = $db->query("SELECT id, nome FROM clientes WHERE usuario_id = $currentUserId ORDER BY nome ASC");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $clientes[] = $row;
}

$error = '';
$success = '';

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clienteId = $_POST['cliente_id'];
        $dataAgendamento = $_POST['data_agendamento'];
        $tipo = $_POST['tipo'];
        $observacoes = trim($_POST['observacoes']);
        $status = $_POST['status'];

        // Validar dados
        if (empty($clienteId)) {
            throw new Exception('Selecione um cliente');
        }

        if (empty($dataAgendamento)) {
            throw new Exception('Informe a data e hora do agendamento');
        }

        if (empty($tipo)) {
            throw new Exception('Informe o tipo de agendamento');
        }

        if (empty($status)) {
            throw new Exception('Selecione um status');
        }

        // Verificar se o cliente pertence ao usuário
        $clienteValido = false;
        foreach ($clientes as $cliente) {
            if ($cliente['id'] == $clienteId) {
                $clienteValido = true;
                break;
            }
        }

        if (!$clienteValido) {
            throw new Exception('Cliente inválido');
        }

        // Atualizar no banco de dados
        $stmt = $db->prepare("
            UPDATE agendamentos
            SET cliente_id = :cliente_id,
                data_agendamento = :data_agendamento,
                tipo = :tipo,
                observacoes = :observacoes,
                status = :status
            WHERE id = :id
        ");

        $stmt->bindValue(':cliente_id', $clienteId);
        $stmt->bindValue(':data_agendamento', $dataAgendamento);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $agendamentoId);

        if ($stmt->execute()) {
            $success = 'Agendamento atualizado com sucesso!';
            // Atualizar dados do agendamento para exibir as informações atualizadas
            $agendamento['cliente_id'] = $clienteId;
            $agendamento['data_agendamento'] = $dataAgendamento;
            $agendamento['tipo'] = $tipo;
            $agendamento['observacoes'] = $observacoes;
            $agendamento['status'] = $status;
        } else {
            $error = 'Erro ao atualizar agendamento. Tente novamente.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Função para formatar data e hora no padrão BR
function formatarDataHoraBR($dataHora)
{
    if (empty($dataHora)) return '';
    $data = new DateTime($dataHora);
    return $data->format('d/m/Y H:i');
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Editar Agendamento</h1>
            <p class="text-gray-600 mt-1">Atualize as informações do agendamento</p>
        </div>
        <a href="listar.php"
            class="mt-4 md:mt-0 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-5 rounded-lg inline-flex items-center transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Agendamentos
        </a>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informações atuais -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Informações Atuais</h2>

                <div class="flex items-center mb-4">
                    <?php if ($agendamento['cliente_foto']): ?>
                    <div class="flex-shrink-0 h-12 w-12">
                        <img class="h-12 w-12 rounded-full object-cover"
                            src="../../<?= htmlspecialchars($agendamento['cliente_foto']) ?>"
                            alt="<?= htmlspecialchars($agendamento['cliente_nome']) ?>">
                    </div>
                    <?php else: ?>
                    <div class="flex-shrink-0 h-12 w-12 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-400 text-xl"></i>
                    </div>
                    <?php endif; ?>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">
                            <?= htmlspecialchars($agendamento['cliente_nome']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($agendamento['cliente_telefone']) ?></p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Data e Hora</p>
                        <p class="text-sm font-medium text-gray-900">
                            <?= formatarDataHoraBR($agendamento['data_agendamento']) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Tipo</p>
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($agendamento['tipo']) ?></p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <?php
                        $statusClasses = [
                            'pendente' => 'bg-yellow-100 text-yellow-800',
                            'confirmado' => 'bg-blue-100 text-blue-800',
                            'realizado' => 'bg-green-100 text-green-800',
                            'cancelado' => 'bg-red-100 text-red-800'
                        ];
                        $statusIcons = [
                            'pendente' => 'clock',
                            'confirmado' => 'check-circle',
                            'realizado' => 'check-double',
                            'cancelado' => 'times-circle'
                        ];
                        $statusText = [
                            'pendente' => 'Pendente',
                            'confirmado' => 'Confirmado',
                            'realizado' => 'Realizado',
                            'cancelado' => 'Cancelado'
                        ];
                        ?>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClasses[$agendamento['status']] ?>">
                            <i class="fas fa-<?= $statusIcons[$agendamento['status']] ?> mr-1"></i>
                            <?= $statusText[$agendamento['status']] ?>
                        </span>
                    </div>

                    <?php if (!empty($agendamento['observacoes'])): ?>
                    <div>
                        <p class="text-sm text-gray-600">Observações</p>
                        <p class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($agendamento['observacoes']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulário de edição -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Editar Informações</h2>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="cliente_id"
                                class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                Cliente
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select id="cliente_id" name="cliente_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>"
                                    <?= ($agendamento['cliente_id'] == $cliente['id'] || (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                Tipo de Agendamento
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select id="tipo" name="tipo" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                <option value="">Selecione o tipo</option>
                                <option value="Avaliação Física"
                                    <?= ($agendamento['tipo'] === 'Avaliação Física' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Avaliação Física')) ? 'selected' : '' ?>>
                                    Avaliação Física</option>
                                <option value="Retorno"
                                    <?= ($agendamento['tipo'] === 'Retorno' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Retorno')) ? 'selected' : '' ?>>
                                    Retorno</option>
                                <option value="Treino"
                                    <?= ($agendamento['tipo'] === 'Treino' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Treino')) ? 'selected' : '' ?>>
                                    Treino</option>
                                <option value="Consulta"
                                    <?= ($agendamento['tipo'] === 'Consulta' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Consulta')) ? 'selected' : '' ?>>
                                    Consulta</option>
                                <option value="Outro"
                                    <?= ($agendamento['tipo'] === 'Outro' || (isset($_POST['tipo']) && $_POST['tipo'] === 'Outro')) ? 'selected' : '' ?>>
                                    Outro</option>
                            </select>
                        </div>

                        <div>
                            <label for="data_agendamento"
                                class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                Data e Hora
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="text" id="data_agendamento" name="data_agendamento" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 datetimepicker"
                                value="<?= isset($_POST['data_agendamento']) ? htmlspecialchars($_POST['data_agendamento']) : htmlspecialchars($agendamento['data_agendamento']) ?>"
                                placeholder="Selecione a data e hora">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                Status
                                <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select id="status" name="status" required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                <option value="">Selecione o status</option>
                                <option value="pendente"
                                    <?= ($agendamento['status'] === 'pendente' || (isset($_POST['status']) && $_POST['status'] === 'pendente')) ? 'selected' : '' ?>>
                                    Pendente</option>
                                <option value="confirmado"
                                    <?= ($agendamento['status'] === 'confirmado' || (isset($_POST['status']) && $_POST['status'] === 'confirmado')) ? 'selected' : '' ?>>
                                    Confirmado</option>
                                <option value="realizado"
                                    <?= ($agendamento['status'] === 'realizado' || (isset($_POST['status']) && $_POST['status'] === 'realizado')) ? 'selected' : '' ?>>
                                    Realizado</option>
                                <option value="cancelado"
                                    <?= ($agendamento['status'] === 'cancelado' || (isset($_POST['status']) && $_POST['status'] === 'cancelado')) ? 'selected' : '' ?>>
                                    Cancelado</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="observacoes"
                                class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="4"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                placeholder="Adicione observações relevantes sobre este agendamento"><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : htmlspecialchars($agendamento['observacoes']) ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Opcional</p>
                        </div>
                    </div>

                    <div
                        class="flex flex-col-reverse sm:flex-row justify-end space-y-4 space-y-reverse sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-200">
                        <a href="listar.php"
                            class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200">
                            <i class="fas fa-times mr-2"></i> Cancelar
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
// Configuração do datetime picker
flatpickr('.datetimepicker', {
    enableTime: true,
    dateFormat: 'd-m-Y H:i',
    locale: 'pt',
    time_24hr: true,
    minuteIncrement: 15,
    allowInput: true,
    disableMobile: true, // Melhora a experiência em dispositivos móveis
    position: 'auto',
    placeholder: 'Selecione a data e hora',
    onReady: function(selectedDates, dateStr, instance) {
        // Adicionar ícone de calendário
        instance.calendarContainer.classList.add('shadow-lg', 'rounded-lg');
    }
});

// Validação básica do formulário
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');

                // Adicionar mensagem de erro se não existir
                if (!field.nextElementSibling || !field.nextElementSibling.classList.contains(
                        'text-red-500')) {
                    const errorDiv = document.createElement('p');
                    errorDiv.className = 'text-red-500 text-xs mt-1';
                    errorDiv.textContent = 'Este campo é obrigatório';
                    field.parentNode.appendChild(errorDiv);
                }
            } else {
                field.classList.remove('border-red-500');

                // Remover mensagem de erro se existir
                if (field.nextElementSibling && field.nextElementSibling.classList.contains(
                        'text-red-500')) {
                    field.nextElementSibling.remove();
                }
            }
        });

        if (!isValid) {
            e.preventDefault();

            // Scroll para o primeiro erro
            const firstError = form.querySelector('.border-red-500');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
    });

    // Remover estilos de erro ao interagir com os campos
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.classList.contains('border-red-500')) {
                this.classList.remove('border-red-500');

                // Remover mensagem de erro se existir
                if (this.nextElementSibling && this.nextElementSibling.classList.contains(
                        'text-red-500')) {
                    this.nextElementSibling.remove();
                }
            }
        });
    });
});
</script>