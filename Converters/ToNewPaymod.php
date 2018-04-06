<?php
/**
 * @author Blake Payne <blake.payne@eckoh.com>
 * @version 1.0.0
 */
/**
 * Handles converting of payment requests and responses
 * in the style of old paymod to be represented in the style
 * new paymod.
 */
class DataHandlers_Converters_ToNewPaymod
{
    /**
     * DataHandlers_Converters_ToNewPaymod constructor.
     * This is here so that you can create new instances.
     * It is intentionally left empty.
     */
    public function __construct(){}

    /**
     * Old PayMod style requests will look like this -
     *      ['cc']                      = "4988242756859977";
     *      ['type']                    = "VISADEBIT";
     *      ['exp']                     = "0616";
     *      ['issue']                   = NULL;
     *      ['cv2']                     = "737";
     *      ['total']                   = 1500; (pence amount)
     *      ['origin']                  = "WEB";
     *      ['sid']                     = "20150126145931d07086";
     *      ['nosession']               = 1;
     *      ['trans_index']             = 1;
     *      ['internal_reference']      = "KPM-WEB-900009985-17";
     *      ['customer_name']           = "A Payer;
     *
     * And we need it like this for new PayMod -
     *      ['full_pan']                = "4988242756859977";
     *      ['expiry_date']             = "0616";
     *      ['issue_number']            = NULL;
     *      ['cv2']                     = "737";
     *      ['total']                   = 1500 (pence amount);
     *      ['internal_reference']      = "KPM-WEB-900009985-17";
     *      ['customer_name']           = "A Payer";
     *      ['store_card']              = 1||0;
     *      ['party_id']                = 9826908501900009985;
     *
     * As you can see above, either store_card and party_id will need to be
     * added to the array passed in, or you will have to add them to the
     * array returned from here.
     *
     * @param $data
     * @throws DataHandlers_Converters_Exception
     * @returns array $converted
     */
    public function convertNewPaymentRequest($data)
    {
        $fullPan = $data['cc'];
        $issueNumber = $data['issue'];
        $cv2 = $data['cv2'];
        $total = $data['total'];
        $internalReference = $data['internal_reference'];
        $customerName = $data['customer_name'];

        if (array_key_exists('exp', $data)) {
            $expiryDate = $data['exp'];
        } else if (array_key_exists('exp_date', $data)) {
            $expiryDate = $data['exp_date'];
        } else {
            throw new DataHandlers_Converters_Exception('Cannot convert request, please check the expiry date and try again.');
        }

        $converted = [
            'full_pan' => $fullPan,
            'expiry_date' => $expiryDate,
            'issue_number' => $issueNumber,
            'cv2' => $cv2,
            'total' => $total,
            'internal_reference' => $internalReference,
            'customer_name' => $customerName
        ];

        if (array_key_exists('store_card', $data)) {
            $converted['store_card'] = $data['store_card'];
        }

        if (array_key_exists('party_id', $data)) {
            $converted['party_id'] = $data['party_id'];
        }
        return $converted;
    }

    /**
     * Old PayMod stored payment requests look like this -
     *      ['payer_reference'] = "KPM-WEB-900009985-17";
     *      ['cv2']             = "737";
     *      ['total']           = 1500; (pence amount)
     *      ['masked_card']     = "498824******9977";
     *      ['currency']        = "GBP";
     *
     * And we need them to look like this for new PayMod -
     *      ['internal_reference']  = "KPM-WEB-900009985-17";
     *      ['total']               = 1500; (pence amount)
     *      ['token']               = "166381414472";
     *      ['last_four']           = "9977";
     *      ['scheme']              = "visa";
     *      ['type']                = "debit";
     *      ['cv2']                 = "737";
     *
     * Not currently needed, will update the functionality
     * as soon as this is required.
     *
     * @param $data
     * @return array $converted
     */
//    public function convertStoredPaymentRequest($data){}

    /**
     * @param $card
     * @return string
     */
    public function convertCardType($card)
    {
        $card = strtolower($card);
        if (substr($card, -5) === 'debit') {
            $scheme = substr($card, 0, -5);
            $type = substr($card, -5);
        } else if (substr($card, -6) === 'credit') {
            $scheme = substr($card, 0, -6);
            $type = substr($card, -6);
        } else if (substr($card, -7) === 'prepaid') {
            $scheme = substr($card, 0, -7);
            $type = substr($card, -7);
        } else {
            return 'Unknown Scheme and Type given, assumed not accepted for this service.';
        }
        $cardType = strtoupper($scheme) . ' ' . ucfirst($type);
        return $cardType;
    }
}