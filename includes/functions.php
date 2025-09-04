<?php
require_once 'config.php';

/**
 * Funções utilitárias para o sistema de avaliações físicas
 */

/**
 * Calcula o IMC (Índice de Massa Corporal)
 * @param float $peso Peso em kg
 * @param float $altura Altura em cm
 * @return float Valor do IMC
 */
function calcularIMC($peso, $altura)
{
    if ($altura <= 0) return 0;
    $alturaMetros = $altura / 100;
    return $peso / ($alturaMetros * $alturaMetros);
}

/**
 * Classifica o IMC de acordo com os valores de referência
 * @param float $imc Valor do IMC
 * @return string Classificação
 */
function classificarIMC($imc)
{
    if ($imc < 18.5) return 'Abaixo do peso';
    if ($imc < 25) return 'Peso normal';
    if ($imc < 30) return 'Sobrepeso';
    if ($imc < 35) return 'Obesidade Grau I';
    if ($imc < 40) return 'Obesidade Grau II';
    return 'Obesidade Grau III';
}

/**
 * Calcula o RCQ (Relação Cintura-Quadril)
 * @param float $cintura Circunferência da cintura em cm
 * @param float $quadril Circunferência do quadril em cm
 * @return float Valor do RCQ
 */
function calcularRCQ($cintura, $quadril)
{
    if ($quadril <= 0) return 0;
    return $cintura / $quadril;
}

/**
 * Classifica o RCQ de acordo com os valores de referência
 * @param float $rcq Valor do RCQ
 * @param string $sexo Sexo do cliente (masculino/feminino)
 * @return string Classificação do risco
 */
function classificarRCQ($rcq, $sexo)
{
    if ($sexo === 'masculino') {
        return $rcq >= 0.95 ? 'Risco aumentado' : 'Risco normal';
    } else {
        return $rcq >= 0.80 ? 'Risco aumentado' : 'Risco normal';
    }
}

/**
 * Calcula a idade a partir da data de nascimento
 * @param string $dataNascimento Data no formato YYYY-MM-DD
 * @return int Idade em anos
 */
function calcularIdade($dataNascimento)
{
    if (empty($dataNascimento)) return 0;

    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    return $hoje->diff($nascimento)->y;
}

/**
 * Formata uma data para o padrão brasileiro
 * @param string $data Data no formato YYYY-MM-DD
 * @return string Data formatada (DD/MM/YYYY)
 */
function formatarData($data)
{
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}

/**
 * Formata uma data e hora para o padrão brasileiro
 * @param string $dataHora Data e hora no formato YYYY-MM-DD HH:MM:SS
 * @return string Data e hora formatada (DD/MM/YYYY HH:MM)
 */
function formatarDataHora($dataHora)
{
    if (empty($dataHora)) return '';
    return date('d/m/Y H:i', strtotime($dataHora));
}

/**
 * Valida e faz upload de uma imagem
 * @param array $file Array $_FILES do arquivo
 * @param string $subDir Subdiretório dentro de uploads/
 * @return string|null Caminho relativo do arquivo ou null em caso de falha
 */
function uploadImagem($file, $subDir = '')
{
    // Verifica se houve erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Verifica o tamanho do arquivo
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return null;
    }

    // Verifica o tipo do arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_FILE_TYPES)) {
        return null;
    }

    // Cria o diretório se não existir
    $uploadDir = UPLOAD_DIR . $subDir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Gera um nome único para o arquivo
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid() . '.' . $ext;
    $caminhoCompleto = $uploadDir . $nomeArquivo;

    // Move o arquivo para o diretório de uploads
    if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        return 'uploads/' . ($subDir ? $subDir . '/' : '') . $nomeArquivo;
    }

    return null;
}

/**
 * Remove um arquivo de upload
 * @param string $caminhoRelativo Caminho relativo do arquivo (a partir da raiz do site)
 * @return bool True se o arquivo foi removido com sucesso
 */
function removerArquivoUpload($caminhoRelativo)
{
    if (empty($caminhoRelativo)) return false;

    $caminhoAbsoluto = __DIR__ . '/../' . ltrim($caminhoRelativo, '/');
    if (file_exists($caminhoAbsoluto)) {
        return unlink($caminhoAbsoluto);
    }
    return false;
}

/**
 * Gera um token CSRF e armazena na sessão
 * @return string Token gerado
 */
function gerarTokenCSRF()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica um token CSRF
 * @param string $token Token a ser verificado
 * @return bool True se o token for válido
 */
function verificarTokenCSRF($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redireciona com uma mensagem flash
 * @param string $url URL para redirecionar
 * @param string $tipo Tipo da mensagem (success, error, warning, info)
 * @param string $mensagem Conteúdo da mensagem
 */
function redirectWithMessage($url, $tipo, $mensagem)
{
    $_SESSION['flash_message'] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem
    ];
    header("Location: $url");
    exit();
}

/**
 * Obtém e limpa a mensagem flash da sessão
 * @return array|null Array com 'tipo' e 'mensagem' ou null se não houver mensagem
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $mensagem = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $mensagem;
    }
    return null;
}

/**
 * Formata um número para o padrão brasileiro (com vírgula para decimais)
 * @param float $numero Número a ser formatado
 * @param int $decimals Número de casas decimais
 * @return string Número formatado
 */
function formatarNumero($numero, $decimals = 2)
{
    return number_format($numero, $decimals, ',', '.');
}

/**
 * Converte uma string no formato brasileiro para float
 * @param string $numero String com número no formato brasileiro (1.234,56)
 * @return float Número convertido
 */
function parseNumeroBrasileiro($numero)
{
    $numero = str_replace('.', '', $numero);
    $numero = str_replace(',', '.', $numero);
    return (float) $numero;
}

/**
 * Obtém a data e hora atual no formato do banco de dados
 * @return string Data e hora no formato YYYY-MM-DD HH:MM:SS
 */
function getCurrentDateTime()
{
    return date('Y-m-d H:i:s');
}

/**
 * Sanitiza uma string para evitar XSS
 * @param string $dados String a ser sanitizada
 * @return string String sanitizada
 */
function sanitizeInput($dados)
{
    return htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida um endereço de e-mail
 * @param string $email E-mail a ser validado
 * @return bool True se o e-mail for válido
 */
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida um número de telefone (formato brasileiro simples)
 * @param string $telefone Número de telefone
 * @return bool True se o telefone for válido
 */
function validarTelefone($telefone)
{
    // Remove caracteres não numéricos
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    // Verifica se tem entre 10 e 11 dígitos
    return strlen($telefone) >= 10 && strlen($telefone) <= 11;
}

/**
 * Obtém os dados de um cliente pelo ID
 * @param SQLite3 $db Instância do banco de dados
 * @param int $clienteId ID do cliente
 * @param int $usuarioId ID do usuário/profissional (para verificação de permissão)
 * @return array|false Array com os dados do cliente ou false se não encontrado
 */
function getClienteById($db, $clienteId, $usuarioId)
{
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->bindValue(':id', $clienteId);
    $stmt->bindValue(':usuario_id', $usuarioId);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

/**
 * Obtém os dados de uma avaliação pelo ID
 * @param SQLite3 $db Instância do banco de dados
 * @param int $avaliacaoId ID da avaliação
 * @param int $usuarioId ID do usuário/profissional (para verificação de permissão)
 * @return array|false Array com os dados da avaliação ou false se não encontrada
 */
function getAvaliacaoById($db, $avaliacaoId, $usuarioId)
{
    $stmt = $db->prepare("
        SELECT a.*, c.nome as cliente_nome
        FROM avaliacoes a
        JOIN clientes c ON a.cliente_id = c.id
        WHERE a.id = :id AND a.usuario_id = :usuario_id
    ");
    $stmt->bindValue(':id', $avaliacaoId);
    $stmt->bindValue(':usuario_id', $usuarioId);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

/**
 * Obtém o histórico de avaliações de um cliente
 * @param SQLite3 $db Instância do banco de dados
 * @param int $clienteId ID do cliente
 * @param int $usuarioId ID do usuário/profissional (para verificação de permissão)
 * @param int $limit Limite de resultados (opcional)
 * @return array Array com as avaliações do cliente
 */
function getHistoricoAvaliacoes($db, $clienteId, $usuarioId, $limit = null)
{
    $query = "
        SELECT * FROM avaliacoes
        WHERE cliente_id = :cliente_id AND usuario_id = :usuario_id
        ORDER BY data_avaliacao DESC
    ";

    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }

    $stmt = $db->prepare($query);
    $stmt->bindValue(':cliente_id', $clienteId);
    $stmt->bindValue(':usuario_id', $usuarioId);

    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    }

    $result = $stmt->execute();
    $avaliacoes = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $avaliacoes[] = $row;
    }

    return $avaliacoes;
}

/**
 * Gera dados para um gráfico de evolução de medidas
 * @param array $avaliacoes Array de avaliações do cliente
 * @param string $campo Campo a ser plotado (peso, imc, etc.)
 * @return array Array com labels e dados para o gráfico
 */
function gerarDadosGraficoEvolucao($avaliacoes, $campo = 'peso')
{
    $labels = [];
    $dados = [];

    foreach ($avaliacoes as $avaliacao) {
        $labels[] = formatarData($avaliacao['data_avaliacao']);
        $dados[] = (float) $avaliacao[$campo];
    }

    // Inverte para ordem cronológica
    $labels = array_reverse($labels);
    $dados = array_reverse($dados);

    return [
        'labels' => $labels,
        'dados' => $dados
    ];
}

/**
 * Calcula a diferença percentual entre dois valores
 * @param float $valorAtual Valor mais recente
 * @param float $valorAnterior Valor anterior
 * @return float Diferença percentual (positiva para aumento, negativa para redução)
 */
function calcularDiferencaPercentual($valorAtual, $valorAnterior)
{
    if ($valorAnterior == 0) return 0;
    return (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
}


/**
 * Gera alertas de saúde com base nas classificações de IMC e RCQ
 * @param string $imcClassificacao Classificação do IMC retornada por classificarIMC()
 * @param string $rcqClassificacao Classificação do RCQ retornada por classificarRCQ()
 * @return array Array de alertas com 'tipo' (danger, warning, info) e 'mensagem'
 */
function gerarAlertasSaude($imcClassificacao, $rcqClassificacao)
{
    $alertas = [];

    // Alertas para IMC
    switch ($imcClassificacao) {
        case 'Obesidade Grau III':
            $alertas[] = [
                'tipo' => 'danger',
                'mensagem' => 'Obesidade Grau III: Risco extremamente elevado de complicações de saúde. Consulte um médico urgentemente.'
            ];
            break;

        case 'Obesidade Grau II':
            $alertas[] = [
                'tipo' => 'danger',
                'mensagem' => 'Obesidade Grau II: Risco muito elevado. Recomenda-se intervenção médica e acompanhamento nutricional.'
            ];
            break;

        case 'Obesidade Grau I':
            $alertas[] = [
                'tipo' => 'warning',
                'mensagem' => 'Obesidade Grau I: Risco elevado. Sugere-se plano de perda de peso com profissional.'
            ];
            break;

        case 'Sobrepeso':
            $alertas[] = [
                'tipo' => 'warning',
                'mensagem' => 'Sobrepeso: Risco aumentado. Atenção à dieta e pratique exercícios regularmente.'
            ];
            break;

        case 'Abaixo do peso':
            $alertas[] = [
                'tipo' => 'warning',
                'mensagem' => 'Abaixo do peso: Pode indicar desnutrição ou outros problemas. Consulte um especialista.'
            ];
            break;
    }

    // Alertas para RCQ
    if ($rcqClassificacao === 'Risco aumentado') {
        $alertas[] = [
            'tipo' => 'danger',
            'mensagem' => 'Risco cardiovascular aumentado (RCQ elevado). Importante avaliar com profissional de saúde.'
        ];
    }

    return $alertas;
}


function formatar_numero($valor, $casas_decimais = 2, $sufixo = '')
{
    if (empty($valor) || !is_numeric($valor)) return '--';
    return number_format((float)$valor, $casas_decimais) . $sufixo;
}
