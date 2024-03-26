<?php
declare(strict_types=1);
const URL = "https://verification.api.sinch.com/verification/v1/verifications";
const METHOD = "POST";
include("../../private/config.php");
/*
    The number that will receive the SMS PIN. Test accounts are limited to verified numbers.
    The number must be in E.164 Format, e.g. Netherlands 0639111222 -> +31639111222
*/
    
function initiateVerification($toNumber){
    /*
        The key from one of your Verification Apps, found here https://dashboard.sinch.com/verification/apps
    */
    $applicationKey  = SINCH_PUBLIC;
    
    /*
        The secret from the Verification App that uses the key above, found here https://dashboard.sinch.com/verification/apps
    */
    $applicationSecret = SINCH_PRIVATE;
    

    
    $smsVerificationPayload = [
        "identity" => [
            "type" => "number",
            "endpoint" => $toNumber
        ],
        "method" => "sms"
    ];
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Basic " . base64_encode($applicationKey . ":" . $applicationSecret)
        ],
        CURLOPT_POSTFIELDS => json_encode($smsVerificationPayload),
        CURLOPT_URL => URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => METHOD,
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    if ($error) {
        return false;
    } else {
        return true;
    }
}