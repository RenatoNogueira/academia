<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    $_SESSION['erro'] = 'Você precisa estar logado para realizar esta ação.';
    header('Location: ../../login.php');
    exit();
}

// Verifica se o ID foi fornecido e é válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['erro'] = 'Cliente inválido.';
    header('Location: listar.php');
    exit();
}

$cliente_id = (int)$_GET['id'];
$usuario_id = getCurrentUserId();

try {
    // Verifica se o cliente pertence ao usuário antes de excluir
    $query = "DELETE FROM clientes WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $cliente_id, SQLITE3_INTEGER);
    $stmt->bindValue(':usuario_id', $usuario_id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    // Verifica se alguma linha foi afetada
    if ($db->changes() > 0) {
        $_SESSION['sucesso'] = 'Cliente excluído com sucesso.';

        // Opcional: Excluir a foto do cliente se existir
        $queryFoto = "SELECT foto FROM clientes WHERE id = :id";
        $stmtFoto = $db->prepare($queryFoto);
        $stmtFoto->bindValue(':id', $cliente_id, SQLITE3_INTEGER);
        $resultFoto = $stmtFoto->execute();
        if ($row = $resultFoto->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['foto']) && file_exists('../../' . $row['foto'])) {
                unlink('../../' . $row['foto']);
            }
        }
    } else {
        $_SESSION['erro'] = 'Cliente não encontrado ou você não tem permissão para excluí-lo.';
    }
} catch (Exception $e) {
    $_SESSION['erro'] = 'Erro ao excluir cliente: ' . $e->getMessage();
}

header('Location: listar.php');
exit();
