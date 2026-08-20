<?php
require_once 'db.php';

$mensagem_sucesso = '';
$mensagem_erro    = '';
$termo_busca      = '';
$contatos         = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {

    // Capturar os dados do formulário
    // trim() remove espaços em branco no início e fim
    $nome     = isset($_POST['nome'])     ? trim($_POST['nome'])     : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

    // Validação básica: nome é obrigatório
    if (empty($nome)) {
        $mensagem_erro = 'O campo Nome é obrigatório!';
    } else {
        // POR QUE PREPARED STATEMENT NO INSERT?
        // O Prepared Statement separa a ESTRUTURA da query
        // dos DADOS que serão inseridos. Funciona assim:
        // 1. Você envia a query com marcadores de posição (?)
        //    → "INSERT INTO contatos (nome, descricao) VALUES (?, ?)"
        // 2. O MySQL compila a ESTRUTURA da query primeiro
        // 3. Depois, você envia os dados separadamente via bind_param()
        // 4. O MySQL SUBSTITUI os ? pelos dados, MAS NUNCA os interpreta
        //    como código SQL — eles são tratados como texto puro
        $sql_insert = "INSERT INTO contatos (nome, descricao) VALUES (?, ?)";

        // prepare() → cria o statement (objeto mysqli_stmt)
        $stmt = $mysqli->prepare($sql_insert);

        if ($stmt) {
            // bind_param(tipos, variáveis) → vincula os dados aos ?
            // 'ss' = ambos são strings
            $stmt->bind_param('ss', $nome, $descricao);

            // execute() → envia os dados e roda a query no banco
            if ($stmt->execute()) {
                $mensagem_sucesso = "Contato «{$nome}» cadastrado com sucesso!";
            } else {
                $mensagem_erro = 'Erro ao cadastrar: ' . $stmt->error;
            }

            // SEMPRE feche o statement após o uso
            $stmt->close();
        } else {
            $mensagem_erro = 'Erro na preparação da query: ' . $mysqli->error;
        }
    }
}

$termo_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// ==========================================
// BLINDAGEM #2: escape_string() NO LIKE
// ==========================================
// 
// POR QUE escape_string() NO FILTRO E NÃO PREPARED STATE?
// 
// Você PODE usar prepared statements com LIKE, mas precisa
// incluir os curingas % dentro do valor que será vinculado:
//
//   $termo_com_curinga = '%' . $termo_busca . '%';
//   $stmt = $mysqli->prepare("SELECT * FROM contatos WHERE nome LIKE ?");
//   $stmt->bind_param('s', $termo_com_curinga);
//
// ISSO TAMBÉM FUNCIONA E É IGUALMENTE SEGURO!
//
// Aqui demonstramos escape_string() porque o professor
// pediu ESPECIFICAMENTE que você saiba usar AMBAS as técnicas.
//

$termo_escapado = $mysqli->real_escape_string($termo_busca);

// Montar a query com o termo já escapado
// ATENÇÃO: os % curingas ficam FORA do valor escapado
// Eles são PARTE DA SINTAXE SQL, não dados do usuário
if (!empty($termo_busca)) {
    $sql_select = "SELECT id, nome, descricao, data_cadastro 
                  FROM contatos 
                  WHERE nome LIKE '%{$termo_escapado}%' 
                     OR descricao LIKE '%{$termo_escapado}%' 
                  ORDER BY data_cadastro DESC";
} else {
    // Sem filtro: listar todos
    $sql_select = "SELECT id, nome, descricao, data_cadastro 
                  FROM contatos 
                  ORDER BY data_cadastro DESC";
}

// ==========================================
// 5. EXECUTAR A CONSULTA E PERCORRER RESULTADOS
// ==========================================
// COMO FUNCIONA:
// 
// query() → executa a query e retorna um objeto mysqli_result
// fetch_assoc() → retorna UMA linha como array associativo
//   ['id' => 1, 'nome' => 'Maria', 'descricao' => '...', ...]
//   Ou retorna NULL quando não há mais linhas

$resultado = $mysqli->query($sql_select);

if ($resultado) {
    // Percorrer linha a linha com while + fetch_assoc
    while ($linha = $resultado->fetch_assoc()) {
        $contatos[] = $linha; // Armazena cada linha no array
    }
    // Libera a memória do resultado
    $resultado->free();
} else {
    $mensagem_erro = 'Erro na consulta: ' . $mysqli->error;
}

// Fechar a conexão ao final (boa prática)
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Contatos — Blindado contra SQL Injection</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
        }

        .cabecalho {
            text-align: center;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .cabecalho h1 {
            font-size: 2rem;
            color: #7fdbca;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .cabecalho .badge {
            display: inline-block;
            background: #1a472a;
            color: #7fdbca;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid #2d6a4f;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 30px;
        }

        @media (max-width: 800px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        .painel {
            background: rgba(30, 30, 50, 0.85);
            border: 1px solid rgba(127, 219, 202, 0.2);
            border-radius: 12px;
            padding: 28px;
            backdrop-filter: blur(10px);
        }

        .painel h2 {
            color: #7fdbca;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(127, 219, 202, 0.15);
        }

        .campo {
            margin-bottom: 18px;
        }

        .campo label {
            display: block;
            margin-bottom: 6px;
            color: #a0c4b8;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .campo input,
        .campo textarea {
            width: 100%;
            padding: 12px 14px;
            background: rgba(15, 15, 30, 0.8);
            border: 1px solid rgba(127, 219, 202, 0.3);
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .campo input:focus,
        .campo textarea:focus {
            outline: none;
            border-color: #7fdbca;
            box-shadow: 0 0 0 3px rgba(127, 219, 202, 0.15);
        }

        .campo textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Botão de cadastro */
        .btn-cadastrar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a472a, #2d6a4f);
            color: #7fdbca;
            border: 1px solid #3a8a5c;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            letter-spacing: 0.5px;
        }

        .btn-cadastrar:hover {
            background: linear-gradient(135deg, #2d6a4f, #3a8a5c);
            box-shadow: 0 4px 15px rgba(58, 138, 92, 0.3);
            transform: translateY(-1px);
        }

        .busca-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .busca-container input {
            flex: 1;
            padding: 12px 14px;
            background: rgba(15, 15, 30, 0.8);
            border: 1px solid rgba(127, 219, 202, 0.3);
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .busca-container input:focus {
            outline: none;
            border-color: #7fdbca;
        }

        .btn-buscar {
            padding: 12px 20px;
            background: rgba(127, 219, 202, 0.15);
            color: #7fdbca;
            border: 1px solid rgba(127, 219, 202, 0.3);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-buscar:hover {
            background: rgba(127, 219, 202, 0.25);
        }

        .btn-limpar {
            padding: 12px 14px;
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-limpar:hover {
            background: rgba(255, 107, 107, 0.25);
        }

        .tabela-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        thead th {
            background: rgba(127, 219, 202, 0.1);
            color: #7fdbca;
            padding: 12px 14px;
            text-align: left;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(127, 219, 202, 0.2);
        }

        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(127, 219, 202, 0.08);
            font-size: 0.92rem;
        }

        tbody tr:hover {
            background: rgba(127, 219, 202, 0.05);
        }

        .sem-resultados {
            text-align: center;
            padding: 30px;
            color: #8899aa;
            font-style: italic;
        }

        /* ==========================================
           MENSAGENS DE FEEDBACK
           ========================================== */
        .msg-sucesso {
            background: rgba(29, 71, 42, 0.8);
            border: 1px solid #2d6a4f;
            color: #7fdbca;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .msg-erro {
            background: rgba(71, 29, 29, 0.8);
            border: 1px solid #6a2d2d;
            color: #ff6b6b;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        /* ==========================================
           RODAPÉ INFORMATIVO
           ========================================== */
        .rodape {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #667;
            font-size: 0.8rem;
        }

        .rodape .shield {
            display: inline-block;
            background: #1a3a2a;
            color: #7fdbca;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 6px;
        }

        /* Data formatada */
        .data-cadastro {
            color: #8899aa;
            font-size: 0.82rem;
        }

        /* Contador de registros */
        .contador {
            color: #667;
            font-size: 0.82rem;
            margin-bottom: 14px;
        }
    </style>
</head>

<body>
    <div class="cabecalho">
        <h1>🛡️ Gerenciador de Contatos</h1>
        <span class="badge">🔒 100% Blindado contra SQL Injection</span>
    </div>

    <div class="container">

        <div class="painel">
            <h2>➕ Cadastrar Novo Contato</h2>

            <!-- Mensagens de feedback -->
            <?php if ($mensagem_sucesso): ?>
                <div class="msg-sucesso">✅ <?php echo htmlspecialchars($mensagem_sucesso); ?></div>
            <?php endif; ?>

            <?php if ($mensagem_erro): ?>
                <div class="msg-erro">❌ <?php echo htmlspecialchars($mensagem_erro); ?></div>
            <?php endif; ?>

                <div class="campo">
                    <label for="campo-nome">Nome *</label>
                    <input
                        type="text"
                        id="campo-nome"
                        name="nome"
                        placeholder="Ex: Maria Silva"
                        required
                        maxlength="150"
                        autocomplete="name">
                </div>
                <div class="campo">
                    <label for="campo-descricao">Descrição</label>
                    <textarea
                        id="campo-descricao"
                        name="descricao"
                        placeholder="Ex: Desenvolvedora backend"
                        maxlength="500"></textarea>
                </div>

                <button type="submit" class="btn-cadastrar">
                    📥 Cadastrar Contato
                </button>
            </form>
        </div>
        <div class="painel">
            <h2>📋 Contatos Cadastrados</h2>
            <form method="GET" action="">
                <div class="busca-container">
                    <label for="campo-busca" style="position:absolute; left:-9999px;">
                        Buscar contatos
                    </label>
                    <input
                        type="text"
                        id="campo-busca"
                        name="busca"
                        value="<?php echo htmlspecialchars($termo_busca); ?>"
                        placeholder="🔍 Digite para filtrar..."
                        autocomplete="off">
                    <button type="submit" class="btn-buscar">Buscar</button>
                    <?php if (!empty($termo_busca)): ?>
                        <a href="?" class="btn-limpar" title="Limpar filtro">✕</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Contador de registros -->
            <p class="contador">
                <?php echo count($contatos); ?> contato(s) encontrado(s)
                <?php if (!empty($termo_busca)): ?>
                    para «<strong><?php echo htmlspecialchars($termo_busca); ?></strong>»
                <?php endif; ?>
            </p>
            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Cadastrado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contatos)): ?>
                            <!-- Nenhum resultado → mensagem informativa -->
                            <tr>
                                <td colspan="4" class="sem-resultados">
                                    🔍 Nenhum resultado encontrado.
                                    <?php if (!empty($termo_busca)): ?>
                                        <br><small>
                                            O termo «<?php echo htmlspecialchars($termo_busca); ?>»
                                            não corresponde a nenhum contato.
                                        </small>
                                    <?php else: ?>
                                        <br><small>Cadastre seu primeiro contato usando o formulário!</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- ======================================
                                 LOOP FOREACH — Imprime cada contato
                                 ======================================
                                 
                                 $contato é cada elemento do array $contatos.
                                 Cada $contato é um array associativo como:
                                 [
                                   'id' => 1,
                                   'nome' => 'Maria Silva',
                                   'descricao' => 'Desenvolvedora',
                                   'data_cadastro' => '2025-01-15 10:30:00'
                                 ]
                                 
                                 htmlspecialchars() → escapa HTML para
                                 prevenir XSS (Cross-Site Scripting).
                                 É a defesa do lado do OUTPUT,
                                 assim como escape_string é a
                                 defesa do lado do INPUT/SQL.
                            -->
                            <?php foreach ($contatos as $contato): ?>
                                <tr>
                                    <td><?php echo (int)$contato['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($contato['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($contato['descricao'] ?? ''); ?></td>
                                    <td class="data-cadastro">
                                        <?php
                                        // Formatar a data para o padrão brasileiro
                                        $data = new DateTime($contato['data_cadastro']);
                                        echo $data->format('d/m/Y H:i');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Rodapé -->
    <div class="rodape">
        <p>Sistema desenvolvido como projeto acadêmico — Segurança contra SQL Injection</p>
        <span class="shield">🛡️ Prepared Statements + escape_string = Blindagem Completa</span>
    </div>

</body>

</html>
