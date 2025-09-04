<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Obter lista de clientes
$clientes = [];
$query = "SELECT * FROM clientes WHERE usuario_id = :usuario_id ORDER BY nome ASC";
$stmt = $db->prepare($query);
$stmt->bindValue(':usuario_id', getCurrentUserId(), SQLITE3_INTEGER);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $clientes[] = $row;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Clientes Cadastrados</h1>
        <a href="cadastrar.php"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Novo Cliente
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden dark:bg-gray-800">
        <div class="overflow-x-auto dark:bg-gray-80">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Foto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Contato</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Idade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Sexo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:text-gray-400">
                    <?php foreach ($clientes as $cliente): ?>
                    <?php
                        $idade = '';
                        if (!empty($cliente['data_nascimento'])) {
                            $dataNasc = new DateTime($cliente['data_nascimento']);
                            $hoje = new DateTime();
                            $idade = $hoje->diff($dataNasc)->y;
                        }
                        ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (!empty($cliente['foto'])): ?>
                            <img src="../../<?php echo htmlspecialchars($cliente['foto']); ?>" alt="Foto"
                                class="h-10 w-10 rounded-full object-cove">
                            <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-400">
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($cliente['email']); ?><br>
                            <?php echo htmlspecialchars($cliente['telefone']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-400">
                            <?php echo $idade ? $idade . ' anos' : '--'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-400">
                            <?php
                                switch ($cliente['sexo']) {
                                    case 'masculino':
                                        echo 'Masculino';
                                        break;
                                    case 'feminino':
                                        echo 'Feminino';
                                        break;
                                    case 'outro':
                                        echo 'Outro';
                                        break;
                                    default:
                                        echo '--';
                                }
                                ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="../avaliacoes/registrar.php?cliente_id=<?php echo $cliente['id']; ?>"
                                    class="text-green-600 hover:text-green-900" title="Nova Avaliação">
                                    <i class="fas fa-clipboard-check"></i>
                                </a>
                                <a href="../agendamentos/criar.php?cliente_id=<?php echo $cliente['id']; ?>"
                                    class="text-blue-600 hover:text-blue-900" title="Novo Agendamento">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                                <a href="detalhes.php?id=<?php echo $cliente['id']; ?>"
                                    class="text-gray-600 hover:text-gray-900" title="Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="editar.php?id=<?php echo $cliente['id']; ?>"
                                    class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="excluir.php?id=<?php echo $cliente['id']; ?>"
                                    class="text-red-600 hover:text-red-900" title="Excluir"
                                    onclick="return confirm('Tem certeza que deseja excluir este cliente?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
require_once '../../includes/footer.php'
?>
</body>

</html>