composer require tecnickcom/tcpdf;

<?php
require 'vendor/autoload.php'; // Inclua o autoloader do Composer
require 'config.php';

use TCPDF;

// Certifique-se de que o ID do agendamento foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_agendamento'])) {
    $id_agendamento = intval($_POST['id_agendamento']);

    // Buscar os dados do agendamento no banco
    $stmt = $pdo->prepare('
        SELECT 
            P.nome_pac AS nome_paciente,
            P.endereco_pac AS endereco,
            P.telefone_pac AS telefone,
            AG.data_agendamento,
            PROC.nome_proced AS nome_procedimento
        FROM 
            AGENDAMENTO AG
        INNER JOIN 
            PACIENTE P ON AG.ID_PACIENTE = P.ID_PACIENTE
        LEFT JOIN
            PROCEDIMENTO PROC ON AG.ID_PROCEDIMENTO = PROC.ID_PROCEDIMENTO
        WHERE AG.ID_AGENDAMENTO = ?
    ');
    $stmt->execute([$id_agendamento]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paciente) {
        die('Erro: Agendamento não encontrado.');
    }

    // Criar o PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Agendamentos');
    $pdf->SetTitle('Dados do Paciente');
    $pdf->SetSubject('Relatório de Agendamento');
    $pdf->SetKeywords('Paciente, Agendamento, PDF');

    // Configurações do documento
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Adicionar uma página
    $pdf->AddPage();

    // Definir fonte e adicionar conteúdo
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Dados do Paciente', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('helvetica', '', 12);
    $pdf->Write(0, 'Nome: ' . $paciente['nome_paciente'], '', 0, 'L', true, 0, false, false, 0);
    $pdf->Write(0, 'Endereço: ' . $paciente['endereco'], '', 0, 'L', true, 0, false, false, 0);
    $pdf->Write(0, 'Telefone: ' . $paciente['telefone'], '', 0, 'L', true, 0, false, false, 0);
    $pdf->Write(0, 'Data Agendada: ' . date('d/m/Y', strtotime($paciente['data_agendamento'])), '', 0, 'L', true, 0, false, false, 0);
    $pdf->Write(0, 'Procedimento: ' . ($paciente['nome_procedimento'] ?? 'Procedimento não definido'), '', 0, 'L', true, 0, false, false, 0);

    // Nome do arquivo PDF gerado
    $fileName = 'Paciente_' . $id_agendamento . '.pdf';

    // Forçar o download do PDF no navegador
    $pdf->Output($fileName, 'D');
} else {
    die('Erro: Nenhum ID de agendamento foi enviado.');
}
?>
