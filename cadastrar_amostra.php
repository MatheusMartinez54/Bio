<?php
require 'config.php';

// Habilitar a exibição de erros (opcional para depuração)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se o ID do agendamento foi passado
if (isset($_GET['id_agendamento'])) {
    $id_agendamento = $_GET['id_agendamento'];
    $nome_procedimento = isset($_GET['nome_procedimento']) ? $_GET['nome_procedimento'] : '';

    // Mapeia os nomes dos procedimentos para os valores desejados
    if ($nome_procedimento === 'Hemograma Completo') {
        $nome_procedimento = 'hemograma';
    } elseif ($nome_procedimento === 'Glicemia em Jejum') {
        $nome_procedimento = 'glicemia';
    }

    // Consulta para obter os dados do agendamento, paciente e procedimento
    $stmt = $pdo->prepare('
        SELECT 
            AG.ID_AGENDAMENTO,
            AG.data_agendamento,
            AG.status_agendamento,
            AG.ID_PROCEDIMENTO,
            AG.id_profissional,
            P.ID_PACIENTE,
            P.nome_pac,
            P.data_nasc_pac,
            P.sexo,
            P.cpf_pac,
            P.convenio,
            PROC.nome_proced,
            PROC.descr_proced,
            PROC.modo_preparo,
            PROC.materiais,
            PROC.val_proced
        FROM 
            AGENDAMENTO AG
        INNER JOIN 
            PACIENTE P ON AG.ID_PACIENTE = P.ID_PACIENTE
        INNER JOIN 
            PROCEDIMENTO PROC ON AG.ID_PROCEDIMENTO = PROC.ID_PROCEDIMENTO
        WHERE 
            AG.ID_AGENDAMENTO = ?
    ');
    $stmt->execute([$id_agendamento]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        echo 'Agendamento não encontrado.';
        exit;
    }

    // Calcula a idade do paciente
    $data_nascimento = new DateTime($agendamento['data_nasc_pac']);
    $hoje = new DateTime();
    $idade = $data_nascimento->diff($hoje)->y;

} else {
    echo 'ID do agendamento não especificado.';
    exit;
}

// Processa o formulário quando for submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Dados do Hemograma
        $hemacias = $_POST['hemacias'];
        $hemoglobina = $_POST['hemoglobina'];
        $hematocrito = $_POST['hematocrito'];
        $vcm = $_POST['vcm'];
        $hcm = $_POST['hcm'];
        $chcm = $_POST['chcm'];
        $rdw = $_POST['rdw'];

        $leucocitos_totais = $_POST['leucocitos_totais'];
        $mielocitos = $_POST['mielocitos'];
        $metamielocitos = $_POST['metamielocitos'];
        $bastonetes = $_POST['bastonetes'];
        $segmentados = $_POST['segmentados'];
        $eosinofilos = $_POST['eosinofilos'];
        $basofilos = $_POST['basofilos'];
        $linfocitos_tipicos = $_POST['linfocitos_tipicos'];
        $linfocitos_atipicos = $_POST['linfocitos_atipicos'];
        $monocitos = $_POST['monocitos'];
        $blastos = $_POST['blastos'];
        $plaquetas = $_POST['plaquetas'];
        $vpm = $_POST['vpm'];

        // Caso seja glicemia
        $glicemia_val = isset($_POST['glicemia']) ? $_POST['glicemia'] : null;

        // Define a data e hora atuais
        $dt_realizado = date('Y-m-d H:i:s');

        // Inicia uma transação
        $pdo->beginTransaction();

        // Insere na tabela EXAMES_E_AMOSTRAS
        $stmt = $pdo->prepare('
            INSERT INTO EXAMES_E_AMOSTRAS (nome_exame, tipo, dt_realizado, ID_PACIENTE, ID_PROCEDIMENTO)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $nome_procedimento,  
            1,                   
            $dt_realizado,
            $agendamento['ID_PACIENTE'],
            $agendamento['ID_PROCEDIMENTO']
        ]);

        // Obtém o ID do exame recém-inserido
        $ID_EXAME = $pdo->lastInsertId();

        // Insere os dados conforme o procedimento
        if ($nome_procedimento === 'hemograma') {
            $stmt = $pdo->prepare('
                INSERT INTO HEMOGRAMA (
                    ID_EXAME,
                    hemacias,
                    hemoglobina,
                    hematocrito,
                    vcm,
                    hcm,
                    chcm,
                    rdw,
                    leucocitos_totais,
                    mielocitos,
                    metamielocitos,
                    bastonetes,
                    segmentados,
                    eosinofilos,
                    basofilos,
                    linfocitos_tipicos,
                    linfocitos_atipicos,
                    monocitos,
                    blastos,
                    plaquetas,
                    vpm
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $ID_EXAME,
                $hemacias,
                $hemoglobina,
                $hematocrito,
                $vcm,
                $hcm,
                $chcm,
                $rdw,
                $leucocitos_totais,
                $mielocitos,
                $metamielocitos,
                $bastonetes,
                $segmentados,
                $eosinofilos,
                $basofilos,
                $linfocitos_tipicos,
                $linfocitos_atipicos,
                $monocitos,
                $blastos,
                $plaquetas,
                $vpm
            ]);
        } elseif ($nome_procedimento === 'glicemia') {
            $stmt = $pdo->prepare('
                INSERT INTO GLICEMIA (ID_EXAME, glicemia)
                VALUES (?, ?)
            ');
            $stmt->execute([
                $ID_EXAME,
                $glicemia_val
            ]);
        }

        // Atualiza o status do agendamento para 'Atendido'
        $stmt = $pdo->prepare("UPDATE AGENDAMENTO SET status_agendamento = 'Atendido' WHERE ID_AGENDAMENTO = ?");
        $stmt->execute([$id_agendamento]);

        // Confirma a transação
        $pdo->commit();

        // Redireciona para a agenda
        header('Location: agenda.php');
        exit;
    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        $pdo->rollBack();
        echo "Erro ao cadastrar o exame: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Amostra - <?= ucfirst($nome_procedimento) ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2e7d32;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table th {
            text-align: center;
            background: #2e7d32;
            color: #fff;
            padding: 10px;
            font-size: 1.2em;
        }

        .info-table td {
            vertical-align: top;
            padding: 8px;
            border-bottom: 1px solid #ccc;
            font-size: 0.95em;
        }

        .info-table strong {
            color: #2e7d32;
        }

        /* Cada linha vai conter dados do paciente à esquerda e do procedimento à direita */
        .info-table td ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-table td ul li {
            margin-bottom: 5px;
        }

        form {
            margin-top: 20px;
        }

        form h2 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.3em;
            color: #2e7d32;
            text-align: center;
        }

        label {
            display: inline-block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.95em;
        }

        input[type="number"], input[type="text"] {
            width: 100px;
            padding: 5px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 0.9em;
        }

        button[type="submit"] {
            background: #4caf50;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            display: block;
            margin: 20px auto;
        }

        button[type="submit"]:hover {
            background: #388e3c;
        }

        a {
            text-decoration: none;
            color: #2e7d32;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #1b5e20;
        }

        .links-retorno {
            text-align: center;
            margin-top: 20px;
        }

        .input-group {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 10px;
        }

        .input-group label {
            display: block;
        }

        .section-title {
            margin-top: 40px;
            text-align: center;
            font-size: 1.2em;
            color: #2e7d32;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastrar Exame - <?= ucfirst($nome_procedimento) ?></h1>
        <table class="info-table">
            <tr>
                <th>Dados do Paciente</th>
                <th>Dados do Procedimento</th>
            </tr>
            <tr>
                <td>
                    <ul>
                        <li><strong>Nome:</strong> <?= htmlspecialchars($agendamento['nome_pac']) ?></li>
                        <li><strong>Convênio:</strong> <?= htmlspecialchars($agendamento['convenio']) ?></li>
                        <li><strong>Data do Exame:</strong> <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?></li>
                        <li><strong>Data de Nascimento:</strong> <?= date('d/m/Y', strtotime($agendamento['data_nasc_pac'])) ?></li>
                        <li><strong>Idade:</strong> <?= $idade ?> anos</li>
                        <li><strong>Sexo:</strong> <?= htmlspecialchars($agendamento['sexo']) ?></li>
                        <li><strong>CPF:</strong> <?= htmlspecialchars($agendamento['cpf_pac']) ?></li>
                    </ul>
                </td>
                <td>
                    <ul>
                        <li><strong>Procedimento:</strong> <?= htmlspecialchars($agendamento['nome_proced']) ?></li>
                        <li><strong>Descrição:</strong> <?= htmlspecialchars($agendamento['descr_proced']) ?></li>
                        <li><strong>Modo de Preparo:</strong> <?= htmlspecialchars($agendamento['modo_preparo']) ?></li>
                        <li><strong>Materiais:</strong> <?= htmlspecialchars($agendamento['materiais']) ?></li>
                        <li><strong>Valor:</strong> R$ <?= number_format($agendamento['val_proced'], 2, ',', '.') ?></li>
                    </ul>
                </td>
            </tr>
        </table>

        <form method="post">
            <?php if ($nome_procedimento === 'hemograma'): ?>
                <h2>Eritrograma</h2>
                <div class="input-group">
                    <label for="hemacias">Hemácias:</label>
                    <input type="number" step="0.01" name="hemacias" id="hemacias" required>
                </div>
                <div class="input-group">
                    <label for="hemoglobina">Hemoglobina:</label>
                    <input type="number" step="0.01" name="hemoglobina" id="hemoglobina" required>
                </div>
                <div class="input-group">
                    <label for="hematocrito">Hematócrito:</label>
                    <input type="number" step="0.01" name="hematocrito" id="hematocrito" required>
                </div>
                <div class="input-group">
                    <label for="vcm">VCM:</label>
                    <input type="number" step="0.01" name="vcm" id="vcm" required>
                </div>
                <div class="input-group">
                    <label for="hcm">HCM:</label>
                    <input type="number" step="0.01" name="hcm" id="hcm" required>
                </div>
                <div class="input-group">
                    <label for="chcm">CHCM:</label>
                    <input type="number" step="0.01" name="chcm" id="chcm" required>
                </div>
                <div class="input-group">
                    <label for="rdw">RDW:</label>
                    <input type="number" step="0.01" name="rdw" id="rdw" required>
                </div>

                <h2>Leucograma</h2>
                <div class="input-group">
                    <label for="leucocitos_totais">Leucócitos Totais:</label>
                    <input type="number" step="0.01" name="leucocitos_totais" id="leucocitos_totais" required>
                </div>
                <div class="input-group">
                    <label for="mielocitos">Mielócitos:</label>
                    <input type="number" step="0.01" name="mielocitos" id="mielocitos" required>
                </div>
                <div class="input-group">
                    <label for="metamielocitos">Metamielócitos:</label>
                    <input type="number" step="0.01" name="metamielocitos" id="metamielocitos" required>
                </div>
                <div class="input-group">
                    <label for="bastonetes">Bastonetes:</label>
                    <input type="number" step="0.01" name="bastonetes" id="bastonetes" required>
                </div>
                <div class="input-group">
                    <label for="segmentados">Segmentados:</label>
                    <input type="number" step="0.01" name="segmentados" id="segmentados" required>
                </div>
                <div class="input-group">
                    <label for="eosinofilos">Eosinófilos:</label>
                    <input type="number" step="0.01" name="eosinofilos" id="eosinofilos" required>
                </div>
                <div class="input-group">
                    <label for="basofilos">Basófilos:</label>
                    <input type="number" step="0.01" name="basofilos" id="basofilos" required>
                </div>
                <div class="input-group">
                    <label for="linfocitos_tipicos">Linfócitos Típicos:</label>
                    <input type="number" step="0.01" name="linfocitos_tipicos" id="linfocitos_tipicos" required>
                </div>
                <div class="input-group">
                    <label for="linfocitos_atipicos">Linfócitos Atípicos:</label>
                    <input type="number" step="0.01" name="linfocitos_atipicos" id="linfocitos_atipicos" required>
                </div>
                <div class="input-group">
                    <label for="monocitos">Monócitos:</label>
                    <input type="number" step="0.01" name="monocitos" id="monocitos" required>
                </div>
                <div class="input-group">
                    <label for="blastos">Blastos:</label>
                    <input type="number" step="0.01" name="blastos" id="blastos" required>
                </div>

                <h2>Plaquetas</h2>
                <div class="input-group">
                    <label for="plaquetas">Plaquetas:</label>
                    <input type="number" step="0.01" name="plaquetas" id="plaquetas" required>
                </div>
                <div class="input-group">
                    <label for="vpm">VPM:</label>
                    <input type="number" step="0.01" name="vpm" id="vpm" required>
                </div>
            <?php elseif ($nome_procedimento === 'glicemia'): ?>
                <h2>Glicemia</h2>
                <div class="input-group">
                    <label for="glicemia">Glicemia:</label>
                    <input type="number" step="0.01" name="glicemia" id="glicemia" required>
                </div>
            <?php endif; ?>

            <button type="submit">Cadastrar <?= ucfirst($nome_procedimento) ?></button>
        </form>

        <div class="links-retorno">
            <a href="agenda.php">Voltar para a Agenda</a>
        </div>
    </div>
</body>
</html>
