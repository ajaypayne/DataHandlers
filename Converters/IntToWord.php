<?php
/**
 * @author Blake Payne <ajaypayne@gmail.com>
 * @version 1.0.0
 * Work in progress, will be added to as different methods are
 * required.
 * Currently can handle up to 9 digits.
 */

/**
 * Takes an integer and converts it to the English language
 * words for that number
 * @exampleUsage
 *      $int            = 999;
 *      $intToWord      = new My_DataHandlers_IntToWord($int);
 *      $word           = $intToWord->convertIntToWord();
 *      will return "nine hundred and ninety nine"
 *      $camelCaseWord  = $intToWord->convertIntToCamelCaseWord();
 *      will return "nineHundredAndNinetyNine"
 */
class DataHandlers_Converters_IntToWord
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var int
     */
    private $digit;

    /**
     * @var string
     */
    private $result;

    /**
     * @var array
     */
    private $flags = [];

    /**
     * @param int $number
     */
    public function __construct($number)
    {
        $this->number = $number;
        $this->result = '';
    }

    /**
     * @return string
     */
    public function convertIntToWord()
    {
        $this->getMeasures();
        return $this->finishConversion();
    }

    /**
     * @return string
     */
    public function convertIntToCamelCaseWord()
    {
        $camelCase = '';
        $result = $this->convertIntToWord();
        $resultArray = explode(' ', $result);
        foreach ($resultArray as $word) {
            $camelCase .= ucfirst($word);
        }
        return lcfirst($camelCase);
    }

    /**
     * Works out what methods we need to use
     * to convert our number to a word.
     *
     * @limitation Currently, anything
     *      over 999,999,999 will return ''.
     */
    private function getMeasures()
    {
        if ((int)$this->number === 0) {
            return $this->finishConversion();
        }
        // Could go on for a while,
        // but for now this will suffice.
        $measures = [
            '9' => ['method' => 'Hundreds', 'flag' => 'HundredMillions', 'suffix' => 'million '],
            '8' => ['method' => 'Tens', 'flag' => 'TenMillions', 'suffix' => 'million '],
            '7' => ['method' => 'Millions', 'flag' => 'Millions', 'suffix' => 'million '],
            '6' => ['method' => 'Hundreds', 'flag' => 'HundredThousands', 'suffix' => 'thousand '],
            '5' => ['method' => 'Tens', 'flag' => 'TenThousands', 'suffix' => 'thousand '],
            '4' => ['method' => 'Thousands', 'flag' => 'Thousands', 'suffix' => 'thousand '],
            '3' => ['method' => 'Hundreds', 'flag' => 'Hundreds', 'suffix' => 'hundred '],
            '2' => ['method' => 'Tens', 'flag' => 'Tens', 'suffix' => ''],
            '1' => ['method' => 'Units', 'flag' => 'Units', 'suffix' => '']
        ];
        foreach ($measures as $digit => $measure) {
            $length = $this->getLength();
            if ($length === 0 || (int)$this->number === 0) {
                $this->getNextSuffix($measure['suffix']);
                return $this->finishConversion();
            }
            if ($digit === $length) {
                $this->digit = substr($this->number, 0, 1);

                $this->includeAnd($measure['flag']);

                $nextMethod = "get{$measure['method']}";
                $this->$nextMethod($this->digit, $measure['flag'], $measure['suffix']);
                $this->number = substr($this->number, 1);
            }
        }
        return $this->finishConversion();
    }

    /**
     * @return int
     */
    private function getLength()
    {
        return strlen((string)$this->number);
    }

    /**
     * may include this in the future, but has
     * a bug around long numbers ie 100300600
     * would give
     * 'one hundred and million three hundred and thousand six hundred and'
     * as it is not strictly necessary for me right now
     * I will not worry about fixing it.
     *
     * @param string $flag
     */
    private function includeAnd($flag)
    {
        if (strlen($this->result) == 0 || $this->digit == 0) {
            return;
        }
        switch ($flag) {
            case 'Units':
                if (!in_array('Tens', $this->flags)) {
                    $this->result .= 'and ';
                }
                break;
            case 'Tens':
            case 'TenThousands':
            case 'TenMillions':
                $this->result .= 'and ';
                break;
        }
    }

    /**
     * @param $current
     * @param $flag
     */
    private function getMillions($current, $flag)
    {
        if ($current != 0) {
            $this->getUnits($current, $flag);
            $this->flags[] = $flag;
        }
        $this->getNextSuffix('million ');
    }

    /**
     * @param $current
     * @param $flag
     */
    private function getThousands($current, $flag)
    {
        if ($current != 0) {
            $this->getUnits($current, $flag);
            $this->flags[] = $flag;
        }
        $this->getNextSuffix('thousand ');
    }

    /**
     * @param $current
     * @param $flag
     */
    private function getHundreds($current, $flag)
    {
        if ($current != 0) {
            $this->getUnits($current, $flag);
            $this->flags[] = $flag;
        }
        $this->getNextSuffix('hundred ');
    }

    /**
     * @param $current
     * @param $flag
     * @param $teenSuffix
     */
    private function getTens($current, $flag, $teenSuffix)
    {
        $tens = [
            '2' => 'twenty ',
            '3' => 'thirty ',
            '4' => 'forty ',
            '5' => 'fifty ',
            '6' => 'sixty ',
            '7' => 'seventy ',
            '8' => 'eighty ',
            '9' => 'ninety '
        ];
        if ($current != 0) {
            if ($current == 1) {
                $this->getTeens($teenSuffix);
            } else {
                $this->result .= $tens[(string)$current];
                $this->flags[] = $flag;
            }
        }
    }

    /**
     * To get to this function we have detected that
     * we have a 1 for our tens/tenXXXX digit.
     * We need to use the next digit for this
     * so we wont pass in the current, and we will
     * remove the first digit from $this->number
     * as we know it will be 1.
     *
     * @param string $suffix
     */
    private function getTeens($suffix = '')
    {
        $this->number = substr($this->number, 1);
        $teens = [
            'ten ',
            'eleven ',
            'twelve ',
            'thirteen ',
            'fourteen ',
            'fifteen ',
            'sixteen ',
            'seventeen ',
            'eighteen ',
            'nineteen '
        ];
        $current = substr($this->number, 0, 1);
        $this->result .= $teens[(int)$current] . $suffix;
    }

    /**
     * @param $current
     * @param $flag
     */
    private function getUnits($current, $flag)
    {
        $units = [
            '1' => 'one ',
            '2' => 'two ',
            '3' => 'three ',
            '4' => 'four ',
            '5' => 'five ',
            '6' => 'six ',
            '7' => 'seven ',
            '8' => 'eight ',
            '9' => 'nine ',
        ];
        if ($current != 0) {
            $this->result .= $units[(string)$current];
        }
        if (!in_array($flag, $this->flags)) {
            $this->flags[] = $flag;
        }
    }

    /**
     * To get here we are on our final convertible digit
     * which is either our final digit or the first 0
     * from a number ending in multiple zeros.
     * If we have a suffix, we need to append it to
     * the result string. Unless the current ending is
     * the same or higher in value.
     *
     * @param $suffix
     */
    private function getNextSuffix($suffix)
    {
        if ($suffix === '') {
            return;
        }
        $trimmed = trim($this->result);
        if ($suffix === 'million '
            && substr($trimmed, -7) !== 'million'
        ) {
            $this->result .= $suffix;
        } else if($suffix === 'thousand '
            && substr($trimmed, -8) !== 'thousand'
            && substr($trimmed, -7) !== 'million'
        ) {
            $this->result .= $suffix;
        } else if ($suffix === 'hundred '
            && substr($trimmed, -7) !== 'hundred'
            && substr($trimmed, -8) !== 'thousand'
            && substr($trimmed, -7) !== 'million'
        ) {
            $this->result .= $suffix;
        }

    }

    /**
     * If we have done any conversions, we want to trim
     * any leading or trailing whitespace,
     * and we want to remove any instances of bad grammar
     * e.g. "one hundred and million" should be "one hundred million"
     * and return the
     * string we have created.
     * Otherwise, we either had 0 or something non numeric.
     *
     * @return string
     */
    private function finishConversion()
    {
        if (strlen($this->result) === 0 || $this->result === '') {
            return 'zero';
        }

        /**
         * Replacement for sentence words
         */
        preg_replace(['/and thousand/','/and million/'], ['thousand', 'million'], $this->result, -1);

        return trim($this->result);
    }
}
