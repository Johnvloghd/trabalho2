<?php
/**
 * ============================================
 * db.php — Conexão Segura com MySQLi (OO)
 * ============================================
 * 
 * REQUISITO ATENDIDO:
 * - Conexão usando a classe nativa mysqli de forma orientada a objetos
 * - Tratamento obrigatório de falhas com $mysqli->connect_errno
 * 
 * EXPLICAÇÃO PARA A SABATINA:
 * 
 * Por que usamos orientação a objetos (OO)?
 *   A classe mysqli orientada a objetos permite acessar métodos e
 *   propriedades usando a seta (->), como $mysqli->real_escape_string().
 *   Isso é mais limpo, moderno e facilita o uso de prepared statements.
 * 
 * O que é connect_errno?
 *   É uma propriedade que guarda o código de erro da última tentativa
 *   de conexão. Se for diferente de 0 (zero), significa que a conexão
 *   FALHOU. Verificar isso é OBRIGATÓRIO para não prosseguir com um
 *   objeto de conexão quebrado.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Usuário do MySQL (XAMPP padrão: root)
define('DB_PASS', '');              // Senha do MySQL (XAMPP padrão: vazio)
define('DB_NAME', 'gerenciador_contatos');
define('DB_CHARSET', 'utf8mb4');
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// OBRIGATÓRIO: Verificar se a conexão falhou
if ($mysqli->connect_errno) {
    // connect_errno → código numérico do erro (0 = sem erro)
    // connect_error → mensagem descritiva do erro
    
    // Em PRODUÇÃO, jamais exiba detalhes do erro para o usuário!
    // Aqui exibemos para fins didáticos.
    die(
        "<div style='color:red; font-family:monospace; padding:20px;'>"
        . "<strong>ERRO DE CONEXÃO</strong><br>"
        . "Código do erro: {$mysqli->connect_errno}<br>"
        . "Mensagem: {$mysqli->connect_error}<br><br>"
        . "Verifique se:<br>"
        . "1. O MySQL está rodando<br>"
        . "2. O banco '{$DB_NAME}' foi criado (execute setup.sql)<br>"
        . "3. As credenciais em db.php estão corretas"
        . "</div>"
    );
}

// Definir o charset da conexão para UTF-8 completo (suporta emojis, acentos, etc.)
// Isso TAMBÉM ajuda na segurança: garante que o escape_string funcione
// corretamente com caracteres multibyte.
if (!$mysqli->set_charset(DB_CHARSET)) {
    // Se falhar, avisamos — mas não abortamos
    error_log("Aviso: Não foi possível definir charset utf8mb4: {$mysqli->error}");
}

// A partir deste ponto, $mysqli é um objeto de conexão VÁLIDO
// e pode ser usado em qualquer arquivo que inclua db.php
?>
