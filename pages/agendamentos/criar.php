<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$error = '';
$success = '';

// Função para converter data do formato BR para o formato do banco
function converterDataParaBanco($dataBr)
{
    if (empty($dataBr)) return '';

    // Verificar se está no formato BR (DD/MM/YYYY HH:MM)
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})$/', $dataBr, $matches)) {
        return $matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5];
    }

    // Se não estiver no formato BR, assumir que já está no formato do banco
    return $dataBr;
}

// Função para converter data do formato do banco para BR
function converterDataParaBR($dataBanco)
{
    if (empty($dataBanco)) return '';
    $data = new DateTime($dataBanco);
    return $data->format('d/m/Y H:i');
}

// Obter lista de clientes para o select
$clientes = [];
$result = $db->query("SELECT id, nome FROM clientes WHERE usuario_id = " . getCurrentUserId() . " ORDER BY nome ASC");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $clientes[] = $row;
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clienteId = $_POST['cliente_id'];
        $dataAgendamento = converterDataParaBanco($_POST['data_agendamento']);
        $tipo = $_POST['tipo'];
        $observacoes = trim($_POST['observacoes']);

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

        // Inserir no banco de dados
        $stmt = $db->prepare("
            INSERT INTO agendamentos
            (cliente_id, data_agendamento, tipo, observacoes, status, usuario_id)
            VALUES
            (:cliente_id, :data_agendamento, :tipo, :observacoes, 'pendente', :usuario_id)
        ");

        $stmt->bindValue(':cliente_id', $clienteId);
        $stmt->bindValue(':data_agendamento', $dataAgendamento);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':observacoes', $observacoes);
        $stmt->bindValue(':usuario_id', getCurrentUserId());

        if ($stmt->execute()) {
            $success = 'Agendamento criado com sucesso!';

            // Limpar o formulário ou redirecionar
            if (isset($_POST['continuar'])) {
                // Manter apenas os dados necessários para continuar adicionando
                $_POST['cliente_id'] = '';
                $_POST['data_agendamento'] = '';
                $_POST['tipo'] = '';
                $_POST['observacoes'] = '';
            } else {
                header('Location: listar.php');
                exit();
            }
        } else {
            $error = 'Erro ao criar agendamento. Tente novamente.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Preparar dados para exibição no formulário (convertendo para BR se necessário)
$dataForm = isset($_POST['data_agendamento']) ? converterDataParaBR($_POST['data_agendamento']) : '';
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Novo Agendamento</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Agende um novo horário para seu cliente</p>
        </div>
        <a href="listar.php"
            class="mt-4 md:mt-0 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Agendamentos
        </a>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($error) ?></p>
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
                <p class="text-sm text-green-700 dark:text-green-300"><?= htmlspecialchars($success) ?></p>
                <?php if (isset($_POST['continuar'])): ?>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">Continue preenchendo o formulário para
                    adicionar outro
                    agendamento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cliente_id"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                        Cliente
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <select id="cliente_id" name="cliente_id" required
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>"
                            <?= isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="tipo"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                        Tipo de Agendamento
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <select id="tipo" name="tipo" required
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white">
                        <option value="">Selecione o tipo</option>
                        <option value="Avaliação Física"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Avaliação Física' ? 'selected' : '' ?>>
                            Avaliação Física</option>
                        <option value="Retorno"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Retorno' ? 'selected' : '' ?>>Retorno
                        </option>
                        <option value="Treino"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Treino' ? 'selected' : '' ?>>Treino
                        </option>
                        <option value="Consulta"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Consulta' ? 'selected' : '' ?>>Consulta
                        </option>
                        <option value="Reabilitação"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Reabilitação' ? 'selected' : '' ?>>
                            Reabilitação
                        </option>
                        <option value="Acompanhamento"
                            <?= isset($_POST['tipo']) && $_POST['tipo'] === 'Acompanhamento' ? 'selected' : '' ?>>
                            Acompanhamento
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="data_agendamento"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                        Data e Hora
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" id="data_agendamento" name="data_agendamento" required
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white datetimepicker"
                        value="<?= $dataForm ?>" placeholder="Selecione a data e hora">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Formato: DD/MM/AAAA HH:MM</p>
                </div>

                <div class="md:col-span-2">
                    <label for="observacoes"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="4"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 dark:bg-gray-700 dark:text-white"
                        placeholder="Adicione observações relevantes sobre este agendamento (opcional)"><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : '' ?></textarea>
                </div>
            </div>

            <div
                class="flex flex-col-reverse sm:flex-row justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center mt-4 sm:mt-0">
                    <input id="continuar" name="continuar" type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700">
                    <label for="continuar" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        Continuar adicionando após salvar
                    </label>
                </div>

                <button type="submit"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-5 rounded-lg inline-flex items-center justify-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                    <i class="fas fa-calendar-plus mr-2"></i> Agendar
                </button>
            </div>
        </form>
    </div>

    <!-- Dica rápida -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-lightbulb text-blue-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <strong>Dica:</strong> Use a opção "Continuar adicionando" para agilizar a criação de múltiplos
                    agendamentos.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<script>
// Configuração do datetime picker
function initFlatpickr() {
    const isDark = document.documentElement.classList.contains('dark');

    flatpickr('.datetimepicker', {
        enableTime: true,
        dateFormat: 'd/m/Y H:i',
        locale: 'pt',
        minDate: 'today',
        time_24hr: true,
        minuteIncrement: 15,
        allowInput: true,
        disableMobile: true,
        position: 'auto',
        placeholder: 'DD/MM/AAAA HH:MM',
        theme: isDark ? 'dark' : 'light',
        onReady: function(selectedDates, dateStr, instance) {
            instance.calendarContainer.classList.add('shadow-lg', 'rounded-lg');
        }
    });
}

// Inicializar flatpickr quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    initFlatpickr();

    // Observar mudanças no tema para atualizar o flatpickr
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                // Destruir e recriar os datepickers com o tema correto
                document.querySelectorAll('.datetimepicker').forEach(function(el) {
                    if (el._flatpickr) {
                        el._flatpickr.destroy();
                    }
                });
                initFlatpickr();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true
    });

    // Validação básica do formulário
    const form = document.querySelector('form');
    const datetimepicker = document.querySelector('.datetimepicker');

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

        // Validar formato da data
        if (datetimepicker.value && !isValidDateTime(datetimepicker.value)) {
            isValid = false;
            datetimepicker.classList.add('border-red-500');

            // Adicionar mensagem de erro se não existir
            if (!datetimepicker.nextElementSibling || !datetimepicker.nextElementSibling.classList
                .contains('text-red-500')) {
                const errorDiv = document.createElement('p');
                errorDiv.className = 'text-red-500 text-xs mt-1';
                errorDiv.textContent = 'Formato de data inválido. Use DD/MM/AAAA HH:MM';
                datetimepicker.parentNode.appendChild(errorDiv);
            }
        }

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

    // Função para validar formato de data e hora
    function isValidDateTime(dateTimeString) {
        const regex = /^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})$/;
        return regex.test(dateTimeString);
    }

    // Remover estilos de erro ao interagir com os campos
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.classList.contains('border-red-500')) {
                this.classList.remove('border-red-500');

                // Remover mensagem de erro se não existir
                if (this.nextElementSibling && this.nextElementSibling.classList.contains(
                        'text-red-500')) {
                    this.nextElementSibling.remove();
                }
            }
        });
    });
});
</script>