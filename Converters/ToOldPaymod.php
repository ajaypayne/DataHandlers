<?php
/**
 * @author Blake Payne <blake.payne@eckoh.com>
 * @version 1.0.0
 */
/**
 * Handles converting of payment requests and responses
 * in the style of new paymod to be represented in the style
 * old paymod.
 */
class DataHandlers_Converters_ToOldPaymod
{
    /**
     * @var string
     */
    protected $lineEnding;

    /**
     * @var DataHandlers_Converters_Array
     */
    private $arrayConverter;

    /**
     * DataHandlers_Converters_ToOldPaymod constructor.
     */
    public function __construct()
    {
        $this->lineEnding = $this->setLineEnding();
        $this->arrayConverter = new DataHandlers_Converters_Array();
    }

    /**
     * @param string $lineEnding
     * @return $this
     */
    public function setLineEnding($lineEnding = "\r\n")
    {
        $this->lineEnding = $lineEnding;
        return $this;
    }

    /**
     * @param $cards
     * @returns array
     */
    public function convertAllowedCards($cards)
    {
        $allowedCards = [];
        foreach ($cards as $scheme => $types) {
            foreach ($types as $type => $details) {
                $allowedCards[] = [
                    'paymodName' => strtoupper($scheme . $type),
                    'displayName' => $details['name'],
                    'text' => ucfirst($type) . " card issued by " . ucfirst($scheme)
                ];
            }
        }
        return $allowedCards;
    }

    /**
     * @param $result
     * @param $includeRaw
     * @return stdClass
     */
    public function convertPayResultToObject($result, $includeRaw = false)
    {
        if ($result['result'] === 'success') {
            $array = $this->convertNewPaymentResponseSuccess($result, $includeRaw);
        } else if ($result['result'] === 'declined') {
            $array = $this->convertNewPaymentResponseDeclined($result, $includeRaw);
        } else {
            $array = $this->convertNewPaymentResponseFailure($result, $includeRaw);
        }
        $object = new stdClass();
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }
        return $object;
    }

    /**
     * This is how a successful response from new PayMod looks
     *      [result]          => success
     *      [result_code]     => 100
     *      [payment_id]      => 20008117
     *      [authcode]        => T:1234
     *      [paid]            => 1500
     *      [masked_pan]      => 498824XXXXXX9977
     *      [expiry]          => 0616
     *      [scheme]          => visa
     *      [type]            => debit
     *      [reference]       => KPM-WEB-900009985-15
     *      [psp_reference]   => 24205
     *
     * And we need this for old PayMod Style
     *      [status] => SUCCESS
     *      [code] => 000
     *      [masked_pan] => 498824******9977
     *      [transaction_id] => 24193
     *      [auth_code] => T:1234
     *      [rawResponse] => <?xml version="1.0" encoding="UTF-8"?>
     * <result><status>SUCCESS</status><code>000</code><masked_pan>498824******9977</masked_pan><transaction_id>24193</transaction_id><auth_code>T:1234</auth_code></result>
     *
     * @param array $data
     * @param boolean $includeRaw
     * @return array $converted
     */
    public function convertNewPaymentResponseSuccess($data, $includeRaw = false)
    {
        $status = strtoupper($data['result']);
        $code = $data['result_code'];
        $maskedPan = $this->convertMaskedPan($data['masked_pan']);
        $transactionId = $data['payment_id'];
        $authCode = $data['authcode'];
        $converted = [
            'status' => $status,
            'code' => $code,
            'masked_pan' => $maskedPan,
            'transaction_id' => $transactionId,
            'auth_code' => $authCode
        ];
        if ($includeRaw) {
            $rawResponse = $this->arrayConverter->fromArray($converted, 'xml');
            $converted['rawResponse'] = $rawResponse;
        }
        return $converted;
    }

    /**
     * @param $pan
     * @return mixed
     */
    public function convertMaskedPan($pan)
    {
        return str_replace('X', '*', $pan);
    }

    /**
     * This is how a declined response looks from new PayMod:
     *     [result]         => "declined",
     *     [result_code]    =>200,
     *     [payment_id]     =>"10002011",
     *     [exception]      => [
     *          [message]        => "card reported as stolen",
     *          [code]           => 101
     *      ]
     *
     * This is how we need it to look for old PayMod services:
     *      [status]            => DECLINED
     *      [code]              => 200
     *      [transaction_id]    => "10002011"
     *      [message]           => "card reported as stolen"
     *      [rawResponse]       => <?xml version="1.0" encoding="UTF-8"?>
     * <result><status>DECLINED</status><code>200</code><transaction_id>10002011</transaction_id><message>card reported as stolen</message></result>
     *
     * @param $data
     * @param bool $includeRaw
     * @return array
     */
    public function convertNewPaymentResponseDeclined($data, $includeRaw = false)
    {
        $status = strtoupper($data['result']);
        $code = $data['result_code'];
        $transactionId = $data['payment_id'];
        $message = $data['exception']['message'];
        $converted = [
            'status' => $status,
            'code' => $code,
            'transaction_id' => $transactionId,
            'message' => $message
        ];
        if ($includeRaw) {
            $rawResponse = $this->arrayConverter->fromArray($converted, 'xml');
            $converted['rawResponse'] = $rawResponse;
        }
        return $converted;
    }

    /**
     * This is how a failure response looks from new PayMod:
     *      [result]        => "failed",
     *      [result_code]   => 400
     *      [exception]     => [
     *          [message]   => "Access to BTBuynet system denied. Please contact the BTBuynet helpdesk."
     *          [code]      => 905
     *      ]
     * This is how we need it to look for old PayMod services:
     *      [status]            => FAILURE
     *      [code]              => 400
     *      [message]           => "Access to BTBuynet system denied. Please contact the BTBuynet helpdesk."
     *      [rawResponse]       => <?xml version="1.0" encoding="UTF-8"?>
     * <result><status>DECLINED</status><code>400</code><message>Access to BTBuynet system denied. Please contact the BTBuynet helpdesk.</message></result>
     *
     * @param $data
     * @param bool $includeRaw
     * @return array
     */
    public function convertNewPaymentResponseFailure($data, $includeRaw = false)
    {
        $status = strtoupper($data['result']);
        $code = $data['result_code'];
        $message = $data['exception']['message'];
        $converted = [
            'status' => $status,
            'code' => $code,
            'message' => $message
        ];
        if ($includeRaw) {
            $rawResponse = $this->arrayConverter->fromArray($converted, 'xml');
            $converted['rawResponse'] = $rawResponse;
        }
        return $converted;
    }

    /**
     * @param $data
     * @return stdClass
     */
    public function convertCardToObject($data)
    {
        $array = $this->convertCard($data);
        $object = new stdClass();
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }
        return $object;
    }

    /**
     * For a new PayMod stored card we should get something like this -
     *      ["last_four"] => string(4) "9977"
     *      ["scheme"] => string(4) "visa"
     *      ["type"] => string(5) "debit"
     *      ["expiry_date"] => string(4) "0616"
     *      ["token"] => string(12) "166381414472"
     *      ["party_id"] => string(40) "c4435f21698e90710b5a95fc72e5e68bc8a12ce9"
     *      ["reference"] => string(20) "KPM-WEB-900009985-17"
     * And we need this for anything requiring an old PayMod format -
     *      ["cc_number"]   => "************9977"
     *      ["card_type"]   => "VISADEBIT"
     *      ["exp_date"]    => "0616"
     *      ["displayName"] => "VISA Debit"
     * @param array $data
     * @returns array $converted
     */
    public function convertCard($data)
    {
        $ccNumber = str_pad($data['last_four'], 16, '*', STR_PAD_LEFT);
        $cardType = strtoupper($data['scheme'] . $data['type']);
        $expDate = $data['expiry_date'];
        $displayName = strtoupper($data['scheme']) . ' ' . ucfirst($data['type']);
        $converted = [
            'cc_number' => $ccNumber,
            'card_type' => $cardType,
            'exp_date' => $expDate,
            'displayName' => $displayName
        ];
        return $converted;
    }

    /**
     * The response of a bin check from paymod edge will be
     * in this format:
     *      ["valid"]           => 1
     *      ["bin"]             => 424242
     *      ["scheme"]          => "visa"
     *      ["type"]            => "debit"
     *      ["issue_length"]    => 0
     *      ["cv2_length"]      => 3
     *      ["pan_length"]      => 16
     * For classic we need the following
     *      ["checkresult"]     => valid
     *      ["bin"]             => 424242
     *      ["card_type"]       => "VISADEBIT"
     * @param $data
     * @return array
     */
    public function convertBinCheck($data)
    {
        $valid = "invalid";
        if ($data['valid'] === 1) {
            $valid = "valid";
        }
        $bin = $data['bin'];
        $cardType = strtoupper($data['scheme'] . $data['type']);
        $converted = [
            'checkresult' => $valid,
            'bin' => $bin,
            'card_type' => $cardType
        ];
        return $converted;
    }
}