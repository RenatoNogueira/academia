<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';


if (!isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}
function getCurrentUserNameFromSession()
{
    // Assuming the user's name is stored in the session
    return $_SESSION['username'] ?? 'Não informado';
}
$avaliacaoId = $_GET['avaliacao_id'] ?? null;
$clienteId = $_GET['cliente_id'] ?? null;
$tipoRelatorio = $_GET['tipo'] ?? 'individual';
$currentUserId = getCurrentUserId();

$cliente = [];
$avaliacao = [];
$avaliacoes = [];

if ($avaliacaoId) {
    $stmt = $db->prepare("SELECT a.*, c.nome as cliente_nome, c.data_nascimento, c.sexo, c.altura as altura_cliente, c.foto FROM avaliacoes a JOIN clientes c ON a.cliente_id = c.id WHERE a.id = :id AND c.usuario_id = :usuario_id");
    $stmt->bindValue(':id', $avaliacaoId);
    $stmt->bindValue(':usuario_id', $currentUserId);
    $result = $stmt->execute();
    $avaliacao = $result->fetchArray(SQLITE3_ASSOC);

    if (!$avaliacao) {
        header('Location: ../avaliacoes/listar.php');
        exit();
    }

    $clienteId = $avaliacao['cliente_id'];
    $cliente = [
        'id' => $avaliacao['cliente_id'],
        'nome' => $avaliacao['cliente_nome'],
        'data_nascimento' => $avaliacao['data_nascimento'],
        'sexo' => $avaliacao['sexo'],
        'altura' => $avaliacao['altura_cliente'],
        'foto' => $avaliacao['foto']
    ];
} elseif ($clienteId) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->bindValue(':id', $clienteId);
    $stmt->bindValue(':usuario_id', $currentUserId);
    $result = $stmt->execute();
    $cliente = $result->fetchArray(SQLITE3_ASSOC);

    if (!$cliente) {
        header('Location: ../clientes/listar.php');
        exit();
    }

    $stmt = $db->prepare("SELECT * FROM avaliacoes WHERE cliente_id = :cliente_id ORDER BY data_avaliacao ASC");
    $stmt->bindValue(':cliente_id', $clienteId);
    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $avaliacoes[] = $row;
    }

    if (empty($avaliacoes)) {
        header('Location: ../clientes/detalhes.php?id=' . $clienteId);
        exit();
    }
} else {
    header('Location: ../dashboard.php');
    exit();
}

$idade = '';
if (!empty($cliente['data_nascimento'])) {
    $dataNasc = new DateTime($cliente['data_nascimento']);
    $hoje = new DateTime();
    $idade = $hoje->diff($dataNasc)->y;
}

require_once __DIR__ . '/../../vendor/autoload.php';


if (!class_exists('TCPDF')) {
    die('Erro: A classe TCPDF não foi carregada corretamente. Verifique se o arquivo tcpdf.php está no caminho correto.');
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TCPDF');
$pdf->SetAuthor('Sistema de Avaliações Físicas');
$pdf->SetTitle('Relatório de Avaliação Física');
$pdf->SetSubject('Relatório de Avaliação Física');
$pdf->SetKeywords('avaliação, física, relatório, academia');

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Header with logo and title
$html = '
<div style="text-align: center; margin-bottom: 20px;">
    <h1 style="font-size: 18px; color: #333;">RELATÓRIO DE AVALIAÇÃO FÍSICA</h1>
    <hr style="border: 1px solid #ddd;">
</div>';

// Client information
$html .= '
<div style="margin-bottom: 10px;">
    <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">DADOS DO CLIENTE</h2>
    <table border="0" cellpadding="4">
        <tr>
            <td width="30%"><strong>Nome:</strong></td>
            <td width="70%">' . htmlspecialchars($cliente['nome']) . '</td>
        </tr>
        <tr>
            <td><strong>Idade:</strong></td>
            <td>' . $idade . ' anos</td>
        </tr>
        <tr>
            <td><strong>Sexo:</strong></td>
            <td>' . ucfirst($cliente['sexo'] ?? 'Não informado') . '</td>
        </tr>
        <tr>
            <td><strong>Altura:</strong></td>
            <td>' . ($cliente['altura'] ?? 'Não informada') . ' cm</td>
        </tr>
    </table>
</div>';

if ($tipoRelatorio === 'individual' && $avaliacao) {
    // Evaluation details
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">DADOS DA AVALIAÇÃO</h2>
        <table border="0" cellpadding="4">
            <tr>
                <td width="30%"><strong>Data da Avaliação:</strong></td>
                <td width="70%">' . date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) . '</td>
            </tr>
        </table>
    </div>';

    // Anthropometric measures
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">MEDIDAS ANTROPOMÉTRICAS</h2>
        <table border="0" cellpadding="4">
            <tr>
                <td width="50%"><strong>Peso:</strong></td>
                <td width="50%">' . number_format($avaliacao['peso'], 1) . ' kg</td>
            </tr>
            <tr>
                <td><strong>IMC:</strong></td>
                <td>' . number_format($avaliacao['imc'], 1) . ' (' . classificarIMC($avaliacao['imc']) . ')</td>
            </tr>
            <tr>
                <td><strong>Percentual de Gordura:</strong></td>
                <td>' . ($avaliacao['percentual_gordura'] ?? 'Não informado') . '%</td>
            </tr>
            <tr>
                <td><strong>Massa Magra:</strong></td>
                <td>' . ($avaliacao['massa_magra'] ?? 'Não informado') . ' kg</td>
            </tr>
            <tr>
                <td><strong>Massa Gorda:</strong></td>
                <td>' . ($avaliacao['massa_gorda'] ?? 'Não informado') . ' kg</td>
            </tr>
        </table>
    </div>';

    // Circumferences
    $html .= '
<div style="margin-bottom: 10px;">
    <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">CIRCUNFERÊNCIAS</h2>
    <table border="0" cellpadding="4">
        <tr>
            <td width="50%"><strong>Abdominal:</strong></td>
            <td width="50%">' . ($avaliacao['circunferencia_abdominal'] ?? 'Não informado') . ' cm</td>
        </tr>
        <tr>
            <td><strong>Quadril:</strong></td>
            <td>' . ($avaliacao['circunferencia_quadril'] ?? 'Não informado') . ' cm</td>
        </tr>
        <tr>
            <td><strong>RCQ (Relação Cintura-Quadril):</strong></td>
            <td>' . (isset($avaliacao['rcq']) ? number_format($avaliacao['rcq'], 2) : 'Não calculado') . '</td>
        </tr>
        <tr>
            <td><strong>Braço:</strong></td>
            <td>' . ($avaliacao['perimetro_braco'] ?? 'Não informado') . ' cm</td>
        </tr>
        <tr>
            <td><strong>Antebraço:</strong></td>
            <td>' . ($avaliacao['perimetro_antebraco'] ?? 'Não informado') . ' cm</td>
        </tr>
        <tr>
            <td><strong>Coxa:</strong></td>
            <td>' . ($avaliacao['perimetro_coxa'] ?? 'Não informado') . ' cm</td>
        </tr>
        <tr>
            <td><strong>Panturrilha:</strong></td>
            <td>' . ($avaliacao['perimetro_panturrilha'] ?? 'Não informado') . ' cm</td>
        </tr>
    </table>
</div>';

    // Anamnese e Questionários
    $hasAnamnese = !empty($avaliacao['anamnese_completa']) || !empty($avaliacao['parq']) || !empty($avaliacao['aha']);
    if ($hasAnamnese) {
        $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">ANAMNESE E QUESTIONÁRIOS</h2>';

        if (!empty($avaliacao['anamnese_completa'])) {
            $html .= '
        <div style="margin-bottom: 10px;">
            <h3 style="font-size: 12px; color: #666;"><strong>Anamnese Completa</strong></h3>
            <p>' . nl2br(htmlspecialchars($avaliacao['anamnese_completa'])) . '</p>
        </div>';
        }

        if (!empty($avaliacao['parq'])) {
            $html .= '
        <div style="margin-bottom: 10px;">
            <h3 style="font-size: 12px; color: #666;"><strong>Questionário PAR-Q</strong></h3>
            <p>' . nl2br(htmlspecialchars($avaliacao['parq'])) . '</p>
        </div>';
        }

        if (!empty($avaliacao['aha'])) {
            $html .= '
        <div style="margin-bottom: 10px;">
            <h3 style="font-size: 12px; color: #666;"><strong>Questionário AHA</strong></h3>
            <p>' . nl2br(htmlspecialchars($avaliacao['aha'])) . '</p>
        </div>';
        }

        $html .= '
    </div>';
    }

    // Circumferences
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">CIRCUNFERÊNCIAS</h2>
        <table border="0" cellpadding="4">
            <tr>
                <td width="50%"><strong>Abdominal:</strong></td>
                <td width="50%">' . ($avaliacao['circunferencia_abdominal'] ?? 'Não informado') . ' cm</td>
            </tr>
            <tr>
                <td><strong>Quadril:</strong></td>
                <td>' . ($avaliacao['circunferencia_quadril'] ?? 'Não informado') . ' cm</td>
            </tr>
            <tr>
                <td><strong>RCQ (Relação Cintura-Quadril):</strong></td>
                <td>' . (isset($avaliacao['rcq']) ? number_format($avaliacao['rcq'], 2) : 'Não calculado') . '</td>
            </tr>
        </table>
    </div>';

    // Health parameters
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">PARÂMETROS DE SAÚDE</h2>
        <table border="0" cellpadding="4">
            <tr>
                <td width="50%"><strong>Pressão Arterial:</strong></td>
                <td width="50%">' . ($avaliacao['pressao_arterial'] ?? 'Não informada') . '</td>
            </tr>
            <tr>
                <td><strong>Frequência Cardíaca:</strong></td>
                <td>' . ($avaliacao['frequencia_cardiaca'] ?? 'Não informada') . ' bpm</td>
            </tr>
        </table>
    </div>';

    // Physical capacities
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">CAPACIDADES FÍSICAS</h2>
        <table border="0" cellpadding="4">
            <tr>
                <td width="50%"><strong>Flexibilidade:</strong></td>
                <td width="50%">' . ucfirst($avaliacao['flexibilidade'] ?? 'Não avaliada') . '</td>
            </tr>
            <tr>
                <td><strong>Resistência:</strong></td>
                <td>' . ucfirst($avaliacao['resistencia'] ?? 'Não avaliada') . '</td>
            </tr>
            <tr>
                <td><strong>Força:</strong></td>
                <td>' . ucfirst($avaliacao['forca'] ?? 'Não avaliada') . '</td>
            </tr>
        </table>
    </div>';

    // Photos and observations
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">FOTOS DA AVALIAÇÃO</h2>';

    $hasPhotos = false;
    $photoTypes = [
        'foto_frontal' => 'Frontal',
        'foto_lateral' => 'Lateral',
        'foto_posterior' => 'Posterior'
    ];

    // First write the HTML header
    $pdf->writeHTML($html, true, false, true, false, '');
    $html = ''; // reset html buffer

    // Calculate available width (A4 page width - margins)
    $pageWidth = 210; // A4 width in mm
    $margins = 15 * 2; // left + right margins
    $availableWidth = $pageWidth - $margins;

    // Image settings
    $imgWidth = 45;  // Reduzido de 50mm para 45mm
    $imgHeight = 80; // Reduzido de 120mm para 80mm
    $imgSpacing = 5; // Espaçamento reduzido de 10mm para 5mm

    // Calculate starting X position to center the images
    $totalImagesWidth = (count($photoTypes) * $imgWidth) + ((count($photoTypes) - 1) * $imgSpacing);
    $startX = ($pageWidth - $totalImagesWidth) / 2;

    // Get current Y position
    $currentY = $pdf->GetY();

    // Add images side by side
    $imageCount = 0;
    foreach ($photoTypes as $field => $label) {
        if (!empty($avaliacao[$field])) {
            $imagePath = '../../' . $avaliacao[$field];
            if (file_exists($imagePath)) {
                $hasPhotos = true;

                // Calculate X position for this image
                $xPos = $startX + ($imageCount * ($imgWidth + $imgSpacing));

                // Add image label
                $pdf->SetXY($xPos, $currentY);
                $pdf->Cell($imgWidth, 5, $label, 0, 1, 'C');

                // Add the image
                try {
                    $pdf->Image(
                        $imagePath,
                        $xPos,             // X
                        $pdf->GetY(),       // Y
                        $imgWidth,          // Width
                        $imgHeight,         // Height
                        '',                 // Type
                        '',                 // Link
                        '',                 // Align
                        false,              // Resize
                        300,                // DPI
                        '',                 // Palette
                        false,              // IsMask
                        false,              // ImgMask
                        0,                  // Border
                        false,              // FitBox
                        false,              // Hidden
                        false,              // FitOnPage
                        false,              // AltText
                        array(),            // AltImgs
                    );

                    // Add space after image
                    $pdf->SetY($pdf->GetY() + $imgHeight + 5);

                    $imageCount++;
                } catch (Exception $e) {
                    // Log error or handle it silently
                }
            }
        }
    }

    if (!$hasPhotos) {
        $pdf->writeHTML('<p>Nenhuma foto registrada nesta avaliação</p>', true, false, true, false, '');
    } else {
        // Adjust Y position after images
        $pdf->SetY($currentY + $imgHeight + 15);
    }

    if (!$hasPhotos) {
        $pdf->writeHTML('<p>Nenhuma foto registrada nesta avaliação</p>', true, false, true, false, '');
    } else {
        // Adjust Y position after images
        $pdf->SetY($currentY + $imgHeight + 15);
    }

    // Continue with the rest of the document
    $html = '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">OBSERVAÇÕES</h2>
        <p>' . nl2br(htmlspecialchars($avaliacao['observacoes'] ?? 'Nenhuma observação registrada')) . '</p>
    </div>';

    // Analysis and recommendations
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">ANÁLISE E RECOMENDAÇÕES</h2>
        <p style="font-style: italic;">(Esta seção deve ser preenchida pelo profissional com base nos resultados obtidos)</p>
    </div>';

    // Continue with the rest of the document
    $html = '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">OBSERVAÇÕES</h2>
        <p>' . nl2br(htmlspecialchars($avaliacao['observacoes'] ?? 'Nenhuma observação registrada')) . '</p>
    </div>';

    // Analysis and recommendations
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">ANÁLISE E RECOMENDAÇÕES</h2>
        <p style="font-style: italic;">(Esta seção deve ser preenchida pelo profissional com base nos resultados obtidos)</p>
    </div>';
} elseif ($tipoRelatorio === 'evolucao' && !empty($avaliacoes)) {
    $html .= '
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 14px; color: #555; background-color: #f5f5f5; padding: 5px;">EVOLUÇÃO DO CLIENTE</h2>
        <table border="1" cellpadding="5">
            <tr style="background-color: #f5f5f5;">
                <th width="20%">Data</th>
                <th width="15%">Peso (kg)</th>
                <th width="15%">IMC</th>
                <th width="15%">% Gordura</th>
                <th width="15%">Massa Magra</th>
                <th width="20%">Circ. Abdominal</th>
            </tr>';

    foreach ($avaliacoes as $av) {
        $html .= '
            <tr>
                <td>' . date('d/m/Y', strtotime($av['data_avaliacao'])) . '</td>
                <td>' . number_format($av['peso'], 1) . '</td>
                <td>' . number_format($av['imc'], 1) . '</td>
                <td>' . ($av['percentual_gordura'] ?? '-') . '</td>
                <td>' . ($av['massa_magra'] ?? '-') . '</td>
                <td>' . ($av['circunferencia_abdominal'] ?? '-') . '</td>
            </tr>';
    }

    $html .= '
        </table>
    </div>';
}

// Footer
$html .= '
<div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 9px; text-align: center;">
    <p>Emitido em: ' . date('d/m/Y H:i') . '</p>
    <p>Profissional Responsável: ' . htmlspecialchars(getCurrentUserNameFromSession()) . '</p>
    <p style="font-style: italic;">Sistema de Avaliações Físicas - Versão 1.0</p>
</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('relatorio_avaliacao.pdf', 'I');