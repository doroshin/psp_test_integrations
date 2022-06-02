<?php

class PayOp
{
    public static function invoiceFlow()
    {
        $invoiceAnswer = self::createInvoice();
        $tokenAnswer = null;
        $checkoutAnswer = null;
        $invoiceAnswerData = null;
        $txID = null;
        $transaction = null;

        if (!empty($invoiceAnswer['status'] && !empty($invoiceAnswer['data']))) {
            if ($invoiceAnswer['status'] == 1) {
                $invoiceAnswerData = $invoiceAnswer['data'];
                $tokenAnswer = self::cardTokenization($invoiceAnswerData);
            } else {
                echo($invoiceAnswer['message']);
            }
        } else {
            echo('Invoice is empty!');
        }

        if (!empty($tokenAnswer['status'] && !empty($tokenAnswer['data']['token']))) {
            if ($tokenAnswer['status'] == 1) {
                $checkoutAnswer = self::createCheckoutTransaction($invoiceAnswerData, $tokenAnswer['data']['token']);
            } else {
                echo($tokenAnswer['message']);
            }
        } else {
            echo('Token is empty!');
        }

        if (!empty($checkoutAnswer['data']['isSuccess']) && !empty($checkoutAnswer['status'])) {
            if ($checkoutAnswer['data']['isSuccess'] == true && $checkoutAnswer['status'] == 1) {
                if (!empty($checkoutAnswer['data']['txid'])) {
                    $txID = $checkoutAnswer['data']['txid'];
                    $transaction = self::getTransactionStatus($txID);
                }
            }
        } else {
            echo('Status is not success!');
        }

        if (!empty($transaction)) {
            if (!empty($transaction['state'])) {
                if ($transaction['state'] == 2) {
                    echo('Success!!!');
                } else {
                    echo('Not success or pending');
                }
            }
        }

    }

    public static function createInvoice()
    {
        $url = 'https://payop.com/v1/invoices/create';
        $header = ['Content-Type: application/json'];
        $body = [
            "publicKey" => "application-a61e0463-e737-491c-8b71-bb157ab43bd6",
            "order" => [
                "id" => "test-order",
                "amount" => "5",
                "currency" => "EUR",
                "items" => [
                    [
                        "id" => "487",
                        "name" => "Item 1",
                        "price" => "2.0999999999999996"
                    ]
                ],
                "description" => "string"
            ],
            "signature" => "1ab0dec9b3e6458c5ec76041e5299",
            "payer" => [
                "email" => "test.user@payop.com",
                "phone" => "",
                "name" => "",
                "extraFields" => []
            ],
            "paymentMethod" => 261,
            "language" => "en",
            "resultUrl" => "https://your.site/success",
            "failPath" => "https://your.site/fail",
            "metadata" => []
        ];

        $body['signature'] = self::generateSignature($body['order']['id'], $body['order']['amount'], $body['order']['currency']);

        return self::sendRequestToPayOp($url, $header, $body);
    }

    public static function cardTokenization($data)
    {
        $url = 'https://payop.com/v1/payment-tools/card-token/create';
        $header = ['Content-Type: application/json'];
        $body = [
            "invoiceIdentifier" => $data,
            "pan" => "5555555555554444",
            "expirationDate" => "12/28",
            "cvv" => "123",
            "holderName" => "HOLDER_NAME"
        ];

        return self::sendRequestToPayOp($url, $header, $body);
    }

    public static function createCheckoutTransaction($invoiceData, $token)
    {
        $url = 'https://payop.com/v1/checkout/create';
        $header = ['Content-Type: application/json'];
        $body = [
            "invoiceIdentifier" => $invoiceData,
            "customer" => [
                "email" => "test@email.com",
                "name" => "CUSTOMER_NAME"
            ],
            "checkStatusUrl" => "https://your.site/check-status/{{txid}}",
            "payCurrency" => "EUR",
            "paymentMethod" => 381,
            "cardToken" => $token
        ];

        return self::sendRequestToPayOp($url, $header, $body);
    }

    public static function getTransactionStatus($id)
    {
        $url = 'https://payop.com/v1/transactions/' . $id;
        $header = [
            'Content-Type: application/json',
            'Authorization' => 'Bearer YOUR_JWT_TOKEN'
        ];

        return self::sendRequestToPayOp($url, $header, [], 'GET');
    }


    public static function generateSignature($id, $amount, $currency)
    {
        $order = ['id' => $id, 'amount' => $amount, 'currency' => $currency];
        ksort($order, SORT_STRING);
        $dataSet = array_values($order);
        $dataSet[] = 'rekrj1f8bc4werwer';

        return hash('sha256', implode(':', $dataSet));
    }

    public static function sendRequestToPayOp($url, $header, $body, $type = 'POST')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}

PayOp::invoiceFlow();
 
?>