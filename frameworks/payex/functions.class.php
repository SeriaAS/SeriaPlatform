<?php

class functions{

private $encryptionKey = SERIA_PAYMENT_PAYEX_SECRET_KEY; //Merchant encryptionkey, generated in PayEx admin.

public function createHash($params)
{
        $params = $params.$this->encryptionKey;
        return md5($params);
}

/* Checking for OK statements in return xml. */
public function checkStatus($xml)
{
 $returnXml = new SimpleXMLElement($xml);
 $code = strtoupper($returnXml->status->code);
 $errorCode = strtoupper($returnXml->status->errorCode);
 $description = strtoupper($returnXml->status->description);
 $orderRef = strtoupper($returnXml->orderRef);
 $authenticationRequired = strtoupper($returnXml->authenticationRequired);

        return $status = array(
        'code'=>$code,
        'errorCode'=>$errorCode,
        'description'=>$description,
        'redirectUrl'=>$returnXml->redirectUrl,
        'orderRef'=>$orderRef,
        'authenticationRequired'=>$authenticationRequired);

}

/*checking complete on return url */
public function complete($params)
{
 $returnXml = new SimpleXMLElement($params);
 $code = strtoupper($returnXml->status->code);
 $errorCode = strtoupper($returnXml->status->errorCode);
 $description = strtoupper($returnXml->status->description);
 $transactionStatus = strtoupper($returnXml->transactionStatus);


        return $status = array(
        'code'=>$code,
        'errorCode'=>$errorCode,
        'description'=>$description,
        'transactionStatus'=>$transactionStatus);
}

}

