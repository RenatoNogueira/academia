<?php
// futuras melhorias em repassar clientes
// require_once '../../includes/config.php';
// require_once '../../includes/auth.php';
// require_once '../../includes/db.php';

// if (!isLoggedIn()) {
//     header('Location: ../../login.php');
//     exit();
// }

// $cliente_id = (int)$_POST['cliente_id'];
// $novo_usuario_id = (int)$_POST['novo_usuario_id'];

// // Verifica se o cliente pertence ao usuário logado
// $currentUserId = getCurrentUserId();
// $stmt = $db->prepare("SELECT id FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
// $stmt->bindValue(':id', $cliente_id, SQLITE3_INTEGER);
// $stmt->bindValue(':usuario_id', $currentUserId, SQLITE3_INTEGER);
// $result = $stmt->execute();

// if (!$result->fetchArray()) {
//     header('Location: clientes.php?error=Cliente não encontrado ou acesso negado');
//     exit();
// }

// // Atualiza o cliente com o novo usuário
// $stmt = $db->prepare("UPDATE clientes SET usuario_id = :novo_usuario_id WHERE id = :id");
// $stmt->bindValue(':novo_usuario_id', $novo_usuario_id, SQLITE3_INTEGER);
// $stmt->bindValue(':id', $cliente_id, SQLITE3_INTEGER);
// $stmt->execute();

// header('Location: clientes.php?success=Cliente repassado com sucesso!');