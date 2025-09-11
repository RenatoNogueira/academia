<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

// Parâmetros de busca e paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Obter lista de clientes com busca e paginação
$query = "SELECT * FROM clientes WHERE usuario_id = :usuario_id";
$countQuery = "SELECT COUNT(*) as total FROM clientes WHERE usuario_id = :usuario_id";

if (!empty($search)) {
    $searchTerm = "%$search%";
    $query .= " AND (nome LIKE :search OR email LIKE :search OR telefone LIKE :search)";
    $countQuery .= " AND (nome LIKE :search OR email LIKE :search OR telefone LIKE :search)";
}

$query .= " ORDER BY nome ASC LIMIT :limit OFFSET :offset";

// Executar contagem total
$stmtCount = $db->prepare($countQuery);
$stmtCount->bindValue(':usuario_id', getCurrentUserId(), SQLITE3_INTEGER);
if (!empty($search)) {
    $stmtCount->bindValue(':search', $searchTerm, SQLITE3_TEXT);
}
$resultCount = $stmtCount->execute();
$totalCount = $resultCount->fetchArray(SQLITE3_ASSOC)['total'];
$totalPages = ceil($totalCount / $limit);

// Executar consulta principal
$stmt = $db->prepare($query);
$stmt->bindValue(':usuario_id', getCurrentUserId(), SQLITE3_INTEGER);
if (!empty($search)) {
    $stmt->bindValue(':search', $searchTerm, SQLITE3_TEXT);
}
$stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

$clientes = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $clientes[] = $row;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Clientes Cadastrados</h1>
        <a href="cadastrar.php"
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i> Novo Cliente
        </a>
    </div>

    <!-- Card de busca e filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6 dark:bg-gray-800 transition-colors duration-200">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="flex-grow relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Buscar por nome, email ou telefone..."
                    class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                Buscar
            </button>
            <?php if (!empty($search)): ?>
            <a href="listar.php"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors duration-200 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white inline-flex items-center">
                <i class="fas fa-times mr-1"></i> Limpar
            </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($clientes)): ?>
    <!-- Estado vazio -->
    <div class="bg-white rounded-lg shadow-md p-8 text-center dark:bg-gray-800 transition-colors duration-200">
        <div class="mx-auto w-24 h-24 mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
            <i class="fas fa-users text-4xl text-gray-400 dark:text-gray-500"></i>
        </div>
        <h3 class="text-xl font-medium text-gray-700 mb-2 dark:text-gray-300">
            <?php echo empty($search) ? 'Nenhum cliente cadastrado' : 'Nenhum cliente encontrado'; ?>
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
            <?php echo empty($search) ? 'Comece cadastrando seu primeiro cliente.' : 'Tente ajustar os termos da busca.'; ?>
        </p>
        <?php if (empty($search)): ?>
        <a href="cadastrar.php"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium inline-flex items-center transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i> Cadastrar Cliente
        </a>
        <?php else: ?>
        <a href="index.php"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-medium transition-colors duration-200 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white inline-flex items-center">
            <i class="fas fa-list mr-2"></i> Ver Todos
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Lista de clientes -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($clientes as $cliente): ?>
        <?php
                $idade = '';
                if (!empty($cliente['data_nascimento'])) {
                    $dataNasc = new DateTime($cliente['data_nascimento']);
                    $hoje = new DateTime();
                    $idade = $hoje->diff($dataNasc)->y;
                }

                // Cor de fundo baseada no sexo (sutil)
                $cardColorClass = "bg-white dark:bg-gray-800";
                if ($cliente['sexo'] == 'masculino') {
                    $cardColorClass = "bg-blue-50 dark:bg-blue-900/20";
                } elseif ($cliente['sexo'] == 'feminino') {
                    $cardColorClass = "bg-pink-50 dark:bg-pink-900/20";
                }
                ?>
        <div
            class="<?php echo $cardColorClass; ?> rounded-lg shadow-md overflow-hidden transition-transform duration-200 hover:shadow-lg">
            <div class="p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <?php if (!empty($cliente['foto'])): ?>
                        <img src="../../<?php echo htmlspecialchars($cliente['foto']); ?>" alt="Foto"
                            class="h-16 w-16 rounded-full object-cover border-2 border-white shadow">
                        <?php else: ?>
                        <div
                            class="h-16 w-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center border-2 border-white shadow">
                            <i class="fas fa-user text-xl text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </h3>
                        <div class="flex items-center mt-1 text-sm text-gray-600 dark:text-gray-400">
                            <?php if ($idade): ?>
                            <span class="flex items-center mr-3">
                                <i class="fas fa-birthday-cake mr-1"></i> <?php echo $idade; ?> anos
                            </span>
                            <?php endif; ?>
                            <span class="flex items-center">
                                <i
                                    class="fas fa-<?php echo $cliente['sexo'] == 'feminino' ? 'venus' : ($cliente['sexo'] == 'masculino' ? 'mars' : 'genderless'); ?> mr-1"></i>
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
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <?php if (!empty($cliente['email'])): ?>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                        <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>"
                            class="hover:text-blue-600 dark:hover:text-blue-400 truncate">
                            <?php echo htmlspecialchars($cliente['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($cliente['telefone'])): ?>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-phone mr-2 text-gray-400"></i>
                        <a href="tel:<?php echo htmlspecialchars($cliente['telefone']); ?>"
                            class="hover:text-blue-600 dark:hover:text-blue-400">
                            <?php echo htmlspecialchars($cliente['telefone']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-6 flex justify-between">
                    <div class="flex space-x-2">
                        <a href="../avaliacoes/registrar.php?cliente_id=<?php echo $cliente['id']; ?>"
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-green-100 text-green-600 hover:bg-green-200 transition-colors duration-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 dark:text-green-400"
                            title="Nova Avaliação">
                            <i class="fas fa-clipboard-check"></i>
                        </a>
                        <a href="../agendamentos/criar.php?cliente_id=<?php echo $cliente['id']; ?>"
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors duration-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 dark:text-blue-400"
                            title="Novo Agendamento">
                            <i class="fas fa-calendar-plus"></i>
                        </a>
                    </div>

                    <div class="flex space-x-2">
                        <a href="detalhes.php?id=<?php echo $cliente['id']; ?>"
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400"
                            title="Detalhes">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="editar.php?id=<?php echo $cliente['id']; ?>"
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-yellow-100 text-yellow-600 hover:bg-yellow-200 transition-colors duration-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 dark:text-yellow-400"
                            title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="excluir.php?id=<?php echo $cliente['id']; ?>"
                            class="h-10 w-10 flex items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-400"
                            title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este cliente?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-8 flex justify-center">
        <div class="flex items-center gap-1">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                class="h-10 w-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php
                    // Mostrar até 5 páginas na navegação
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    if ($endPage - $startPage < 4) {
                        $startPage = max(1, $endPage - 4);
                    }

                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
            <a href="?page=<?php echo $p; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                class="h-10 w-10 flex items-center justify-center rounded-full <?php echo $p == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400'; ?> transition-colors duration-200">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                class="h-10 w-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-400">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once '../../includes/footer.php'
?>
</body>

</html>