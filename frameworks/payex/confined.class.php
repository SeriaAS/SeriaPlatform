<?php

class confined
{

        function saleCC3($params)
        {
                $PayEx = new SoapClient(PxConfinedWSDL,array("trace" => 1, "exceptions" => 0));
                $function = new functions();
                //create the hash
                $hash = $function->createHash(trim(implode("", $params)));
                //append the hash to the parameters
                $params['hash'] = $hash;

                try{
                        //defining which initialize version to run, this one is 6.
                        $respons = $PayEx->SaleCC3($params);

                        /* NB: SHOULD BE EDITED TO NOT SHOW THE CUSTOMER THIS MESSAGE, BUT SHOW A GENERIC ERROR MESSAGE FOR THE USER, BUT YOU SHOULD BE INFORMED OF THE ERROR. "*/
                }catch (SoapFault $error){
                        echo "Error: {$error->faultstring}";
                }
        return $respons->{'SaleCC3Result'};

        }

        function prepareSaleCC2($params)
        {
                $PayEx = new SoapClient(PxConfinedWSDL,array("trace" => 1, "exceptions" => 0));
                $function = new functions();
                unset($params['transactionType']);

                //create the hash
                $hash = $function->createHash(trim(implode("", $params)));
                //append the hash to the parameters
                //$params['transactionType'] = '';

                $params['hash'] = $hash;

                try{
                        //defining which initialize version to run, this one is 6.
                        $respons = $PayEx->PrepareSaleCC2($params);
                        /* NB: SHOULD BE EDITED TO NOT SHOW THE CUSTOMER THIS MESSAGE, BUT SHOW A GENERIC ERROR MESSAGE FOR THE USER, BUT YOU SHOULD BE INFORMED OF THE ERROR. "*/
                }catch (SoapFault $error){
                        echo "Error: {$error->faultstring}";
                }

        return $respons->{'PrepareSaleCC2Result'};


        }
}
