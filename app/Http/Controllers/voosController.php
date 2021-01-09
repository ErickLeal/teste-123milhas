<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

class voosController extends Controller
{
    public function groupFligths()
    {
        try {
            $return = array();
            $agroup = array();

            $id = 0;
            //busca os dados na api
            $client = new Client([
                'base_uri' => 'http://prova.123milhas.net/',
            ]);
            $response = $client->request('GET', 'api/flights');
            $data = json_decode($response->getBody()->getContents(), true);
            $return["flights"] = $data;
            $return["totalFlights"] = count($data);
               
            //agrupa voos de acordo com tarifa e preço
            foreach ($data as $fligth) {
                if ($fligth["inbound"]) {
                    if (!isset($agroup["in"][$fligth["fare"]][$fligth["price"]])) $agroup["in"][$fligth["fare"]][$fligth["price"]] = array();
                    $agroup["in"][$fligth["fare"]][$fligth["price"]][] = $fligth;
                } else {
                    if (!isset($agroup["out"][$fligth["fare"]][$fligth["price"]])) $agroup["out"][$fligth["fare"]][$fligth["price"]] = array();
                    $agroup["out"][$fligth["fare"]][$fligth["price"]][] = $fligth;
                }
            }   
            /*
                O foreach acima pode ser substituido em um cenário onde se faz duas request's,
                uma para os voos de ida e outra para os voos de volta, removendo a necessidade do
                foreach acima mas necessitando de uma request a mais. Considerei que um menor número
                de request's seria mais interessante.
            */
           
            //monta variações de ida e volta
            foreach($agroup["in"] as $fare => $v){
                foreach($v as $priceIn => $fligthIn){
                    foreach($agroup["out"][$fare] as $priceOut => $fligthOut){
                        $return["groups"][$id] = [
                            "uniqueId" => $id,
                            "totalPrice" => $priceIn+$priceOut,
                            "outbound" => $fligthOut,
                            "inbound" => $fligthIn,
                        ];
                        $aux = $id;
                        //garante ordenação pelo menor preço
                        while($aux !=0 && $return["groups"][$aux]["totalPrice"] < $return["groups"][$aux-1]["totalPrice"]){
                            $group = $return["groups"][$aux-1];
                            $return["groups"][$aux-1] = $return["groups"][$aux];
                            $return["groups"][$aux] = $group;
                            $aux--;
                        }
                        $id++;
                    }
                }
            }
            $return["totalGroups"] = count($return["groups"]);
            $return["cheapestPrice"] = $return["groups"][0]["totalPrice"];
            $return["cheapestGroup"] = $return["groups"][0]["uniqueId"];

            return response($return,200);
        } catch (\Exception $e) {
            return response([
                "mensagem" => "Erro inesperado",
                "erro" => $e->getMessage()
            ],400);
        }
    }
}
