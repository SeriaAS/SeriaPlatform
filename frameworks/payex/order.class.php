<?php

class order{

        function initialize7($params)
        {
                $PayEx = new SoapClient(PxOrderWSDL,array("trace" => 1, "exceptions" => 0));
                $function = new functions();
                //create the hash
                $hash = $function->createHash(trim(implode("", $params)));
                //append the hash to the parameters
                $params['hash'] = $hash;
                try{
                        $respons = $PayEx->Initialize7($params);
                        /* NB: SHOULD BE EDITED TO NOT SHOW THE CUSTOMER THIS MESSAGE, BUT SHOW A GENERIC ERROR MESSAGE FOR THE USER, BUT YOU SHOULD BE INFORMED OF THE ERROR. "*/
                }catch (SoapFault $error){
                        //echo "Error: {$error->faultstring}";
                        echo "An error has occured. You will not be charged any costs, and you may try again. We have been informed of this error and will look into it!";
                }
        return $respons->{'Initialize7Result'};
        // for debugging: print_r($respons->{'Initialize7Result'}."\n");
        }

        function addOrderLine($params)
        {
                $PayEx = new SoapClient(PxOrderWSDL,array("trace" => 1, "exceptions" => 0));
                $function = new functions();
                $hash = $function->createHash(trim(implode("", $params)));
                $params['hash'] = $hash;
                try {
                        $respons = $PayEx->addSingleOrderLine($params);
                }catch (SoapFault $error) {
               		 echo "Error: {$error->faultstring}";
                }
	return $respons->{'addSingleOrderLineResponse'};

        }


        function Complete($params)
        {
                $PayEx = new SoapClient(PxOrderWSDL,array("trace" => 1, "exceptions" => 0));
                $function = new functions();

                //create the hash
                $hash = $function->createHash(trim(implode("", $params)));
                //append the hash to the parameters
                $params['hash'] = $hash;

                try{
                        //defining which complete
                        $respons = $PayEx->Complete($params);
                        /* NB: SHOULD BE EDITED TO NOT SHOW THE CUSTOMER THIS MESSAGE, BUT SHOW A GENERIC ERROR MESSAGE FOR THE USER, BUT YOU SHOULD BE INFORMED OF THE ERROR. "*/
                }catch (SoapFault $error){
                        echo "Error: {$error->faultstring}";
                }
        return $respons->{'CompleteResult'};
        }
}
