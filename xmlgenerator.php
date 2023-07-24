<?php

# SuperFeed [background only]

define('KEEP', 200000);

$task = $argv[1] ?? false;

if (!$task) {
    exit('O SuperFeed não pode ser acessado diretamente.');
}

$relation = 1; // Fonte de dados, por exemplo, uma tabela principal
               // Mas nao sao todos os campos que sao obtidos na consulta, apenas o id e campos leves
               // Porque senao essa variavel pode se tornar muito grande em consumo de memoria

if (!$relation) {
    exit('Nada a ser processado');
}

$num = count($relation); // quantidade de dados
$time = time();          // timestamp

// Log sobre inicio do processos, quantidade de dados, etc.

$jobcount = 0;

$time_limit = $time + 596; // limite de execuçao, no caso sao 600 na Hostinger, 4 sao descontados por segurança

# XML generate process
$xml = new XmlWriter();
$xml->openMemory();
$xml->setIndent(true);
$xml->startDocument('1.0', 'UTF-8');
# Start source
$xml->startElement('source');
# Metadata
$xml->startElement('publisher');
$xml->writeCData('Site de Empregos');
$xml->endElement();
$xml->startElement('publisherurl');
$xml->writeCData('https://sitedeempregos.com.br/');
$xml->endElement();
$xml->startElement('lastbuilddate');
$xml->writeCData(gmdate('Y-m-d\TH:i:s\Z'));
$xml->endElement();

# File write process begin
$file = 'feed.xml'; // nome do arquivo
$point = fopen($file,'w'); // funçoes de baixo uso de recursos computacionais
fwrite($point, $xml->flush(true));

foreach ($relation as $row) {
    
    # Content (description)
    $post_content   = field('wp_posts','post_content',"WHERE ID='$row[id]';");
    $u['url']       = 'https://sitedeempregos.com.br/job/' . $row['post_name'];
    # Search related data
    $terms = mselect('wp_term_relationships','term_taxonomy_id','WHERE object_id="' . $row['id'] . '";');
    # Lista o id dos anexos para pegar a taxonomia
    foreach ($terms as $term) {
        $term_name = select('wp_term_taxonomy','term_id,taxonomy',"WHERE term_taxonomy_id='$term[term_taxonomy_id]';");
        if (isset($term_name['taxonomy'])) {
            $term_value[$term_name['taxonomy']] = field('wp_terms','name',"WHERE term_id='$term_name[term_id]';");
        }
    }

    # Register creation
    $data['referencenumber']        = $row['id'];
    $data['title']                  = $row['post_title'];
    $data['company']                = $term_value['empresa'] ?? null;
    [$data['city'],$data['state']]  = isset($term_value['local']) ? exportLocal($term_value['local']) : [null,null];
    $data['country']                = 'br'; # [chumbado]
    $data['dateposted']             = $row['post_modified'];
    $data['url']                    = $u['url'];
    $data['logo']                   = $u['img'];

    $data = array_filter($data);

    # Write each job
    $xml->startElement('job');
    foreach ($data as $tag => $value) {
        $xml->startElement($tag);
        $xml->writeCData($value);
        $xml->endElement();
    }
    $xml->endElement();
    fwrite($point, $xml->flush(true));
    
    $data = [];
    $term_value = [];
    
    # Keep Alive Connection
    if (($jobcount%KEEP)===0) {
        reconnect();
    }
    $jobcount++;
    
    # Execution Time Limit Prevent
    if (time()>=$time_limit) {
        break;
    }
    
}

# End of source
$xml->endElement();

# Final write and close on superFeed
fwrite($point, $xml->flush(true));
fclose($point);

// Algum log de registro de conclusao

