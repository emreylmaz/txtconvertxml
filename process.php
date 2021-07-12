<?php

class FileHelper {

    private $inputPath;
    private $outPutPath;

    function __construct($inputPath, $outPutPath){
        $this->inputPath = $inputPath;
        $this->outPutPath = $outPutPath;
    }

    function getFiles(){
        return scandir($this->inputPath);
    }

    function readFile($dir='input', $file){
        return file_get_contents(($dir==='input'?$this->inputPath:$this->outPutPath) . $file);
    }
}

class CsvHelper{

    private $content;

    public function __construct($content){
        $this->fill($content);
    }

    public function fill($content){
        $rows = str_getcsv($content,"\r\n");
        $content = [];


        $headerTitle = str_getcsv(array_shift($rows),';');
        $headerValue = str_getcsv(array_shift($rows),';');
        $content['header'] = array_combine($headerTitle, $headerValue);

        $details = [];
        $detailTitle = str_getcsv(array_shift($rows),';');

        foreach($rows as $row){
            $row = str_getcsv($row, ';');
            $details[] = array_combine($detailTitle, $row);
        }
        $content['detail'] = $details;

        $this->content = $content;
    }

    public function get(){
        return $this->content;
    }
}

class XmlHelper {

    private $xml;
    private $content;

    public function __construct($content){
        $this->xml = new XMLWriter();
        $this->content = $content;
    }

    function is_decimal($val){
        return strpos($val,',');
    }

    function generateXml($fileName){
        $xml = $this->xml;
        $xml->openUri($fileName);
        $xml->setIndent(true);

        $xml->startDocument();

        $xml->startElement('order');

        $xml->startElement('header');
        foreach($this->content['header'] as $key=>$value){
            if ($key==='dateCreated' || $key==='dateSend'){
                $value = date('Y-m-d H:i:s',strtotime(trim($value)));
            }
            $xml->writeElement(trim($key),trim($value));
        }
        $xml->endElement();


        $xml->startElement('lines');

        foreach($this->content['detail'] as $line){
            if (trim($line['itemCode'])!=='' && $this->is_decimal($line['price'])){

                $tr = array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ö', 'Ç');
                $en   = array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 'o', 'c');
                $line['itemDescription'] = str_replace($tr, $en, $line['itemDescription']);

                $xml->startElement('line');
                foreach($line as $key=>$value){

                    $key = trim($key);
                    $value = trim($value);
                    if ($key === 'deliveryDateLatest'){
                        $value = date('ymd',strtotime(trim($value)));
                    }
                    $xml->writeElement($key,$value);
                }
                $xml->endElement();
            }
        }
        $xml->endElement();
        $xml->endElement();
        $xml->flush();
    }

}

$fileSys = new FileHelper('input'.DIRECTORY_SEPARATOR, 'output'.DIRECTORY_SEPARATOR);

$files = $fileSys->getFiles();
foreach($files as $file){
    if ($file!=='.' && $file!=='..') {

        $fileContent = $fileSys->readFile('input',$file);
        $csvhelper = new CSVHelper($fileContent);
        $csv = $csvhelper->get();

        $xmlFileName = 'output'.DIRECTORY_SEPARATOR.$file.'.xml';
        $xml = new XmlHelper($csv);
        $xml->generateXml($xmlFileName);

        echo (file_exists($xmlFileName)?('Xml Yaratıldı : '.$xmlFileName):'Bir Sorun Oluştur').'<br />';
    }
}

