<?php

# Small Daemon

// Execute "php smalldaemon.php"
// Confira com "cat contagem.txt"
// Execute "php smalldaemon.php 127"
// Execute "php smalldaemon.php abc"

if (empty($argv)) {
    exit('Nao pode ser acessado diretamente' . PHP_EOL);
}

# Variaveis de ambiente para recursos e controle de execucao estarao nessa altura
$limite = 20;

$in = $argv[1] ?? 1; // Argumento da linha de comando

// Exclui arquivo de contagem
if ($in=='zerar') {
    file_put_contents('contagem.txt', '');
    exit('Contagem reiniciada' . PHP_EOL);
}

// Garante que o valor seja inteiro
$num = preg_replace('/[^0-9]/', '', $in);
if (empty($num)) {
    exit('Digite um valor inteiro. Valor inserido: ' . $in . PHP_EOL);
}

file_put_contents('contagem.txt', $num . "\n", FILE_APPEND);

if ($num>=$limite) {
    exit('Limite de execuçoes alcançado' . PHP_EOL);
    return; // Finaliza script por criterio de ambiente
}

// Codigo aqui
// Processamento e;
// Possivel finalizacao do script por criterio de necessidade, recursos ou regras de negocio

$num++; // Incrementa valor para nova execuçao do cron
       // Pode carregar o id que inicia uma paginaçao na geracao de relatorio/extrato ou
       // ainda a continuaçao da execuçao de filas

shell_exec("php smalldaemon.php $num");
exit('Script executado com sucesso.' . PHP_EOL);
