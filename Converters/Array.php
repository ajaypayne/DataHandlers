<?php
/**
 * @author Blake Payne <ajaypayne@gmail.com>
 * @version 1.0.0
 * Work in progress, will be added to as different methods are
 * required.
 * Current methods
 * @toArray Will convert any format of data into an array.
 * @booleanToArrayConversion Returns bool as the first value of an array.
 * @doubleToArrayConversion Splits doubles on the decimal each part becomes array value.
 * @integerToArrayConversion Each digit becomes an array value.
 * @objectToArrayConversion Object key->values become array key=>values.
 * @isIterable Confirms whether the data passed is iterable
 * @isJson Confirms whether the string passed is json encoded.
 * @flattenArrayConversion Moves all deep key=>values in lower arrays to the surface.
 * @jsonToArrayConversion Converts a json encoded string to an array.
 * @arrayToArrayConversion Here to prevent errors in case an array is passed in. Can flatten.
 * @nullToArrayConversion Returns an empty array when a null value is passed in.
 *
 * @version 1.0.1
 * Beginning to add methods to convert FROM arrays, to different formats.
 * Current methods
 * @arrayToXmlConversion Returns xml document, will accept multi-dimensional arrays and output full structure.
 */

/**
 * Array Converter
 * Can take input in any format and return it as an array.
 */
class DataHandlers_Converters_Array
{
    /**
     * DataHandlers_Converters_ArrayConverter constructor.
     * This is here so that you can create new instances.
     * It is intentionally left empty.
     */
    public function __construct(){}

    /**
     * Booleans return as the first value of an array.
     *
     * @param $data
     * @return array
     */
    public function booleanToArrayConversion($data)
    {
        if (is_bool($data)) {
            return [$data];
        }
        return $this->toArray($data);
    }

    /**
     * Determine the typeof data that we have and
     * call the corresponding method to convert it
     * to an array.
     *
     * @param $data
     * @param bool $flatten
     * @return array
     */
    public function toArray($data, $flatten = false)
    {
        $methodName = gettype($data) . 'ToArrayConversion';
        return $this->$methodName($data, $flatten);
    }

    public function fromArray($data, $to)
    {
        if(gettype($data) !== 'array') {
            throw new DataHandlers_Converters_Exception('Cannot convert. Please use toArray to convert from ' . gettype($data));
        }
        $methodName = 'arrayTo' . ucfirst($to) . 'Conversion';
        return $this->$methodName($data);
    }

    /**
     * Split doubles on the decimal and
     * return each part as a separate value.
     *
     * @param $data
     * @return array
     */
    public function doubleToArrayConversion($data)
    {
        if (is_double($data)) {
            return explode('.', $data);
        }
        return $this->toArray($data);
    }

    /**
     * Split any int so that each digit becomes a separate value.
     *
     * @param $data
     * @return array
     */
    public function integerToArrayConversion($data)
    {
        if (is_int($data)) {
            return str_split($data);
        }
        return $this->toArray($data);
    }

    /**
     * Set each array key=>value as they are in the object
     * optionally flattening to a one dimensional array.
     *
     * @param $data
     * @param $flatten
     * @return array
     */
    public function objectToArrayConversion($data, $flatten)
    {
        $result = [];
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                if ($this->isIterable($value)) {
                    $value = $this->toArray($value, $flatten);
                } else if ($this->isJson($value)) {
                    $value = json_decode($value);
                }
                $result[$key] = $value;
            }
            if ($flatten) {
                $result = $this->flattenArray($result);
            }
            return $result;
        }
        return $this->toArray($data, $flatten);
    }

    /**
     * Determine if a given data is iterable.
     * @param $data
     * @return bool
     */
    public function isIterable($data)
    {
        if (self::isJson($data)) {
            $data = json_decode($data, 1);
        }
        if (is_array($data) || is_object($data)) {
            return true;
        }
        return false;
    }

    /**
     * Try to json_decode a string, if there is no error
     * the string must be JSON.
     *
     * @param $data
     * @return bool
     */
    public function isJson($data)
    {
        if (is_string($data)) {
            json_decode($data);
            return (json_last_error() == JSON_ERROR_NONE);
        }
        return false;
    }

    /**
     * Convert a multidimensional array to a one dimensional array.
     *
     * @param $multiDimensional
     * @return array
     */
    public function flattenArray($multiDimensional)
    {
        $output = [];
        foreach ($multiDimensional as $key => $value) {
            if ($this->isIterable($value)) {
                $nextLevel = $this->flattenArray($value);
                unset($multiDimensional[$key]);
                $output = array_merge($output, $nextLevel);
            } else {
                $output[$key] = $multiDimensional[$key];
            }
        }
        return $output;
    }

    /**
     * If a string is JSON, decode it and optionally flatten it
     * to a one dimensional array.
     * Else split the string on the delimiter with each part
     * as an array value.
     *
     * @param $data
     * @param $flatten
     * @param string $stringDelimiter default = ,
     * @return array
     */
    public function stringToArrayConversion($data, $flatten = false, $stringDelimiter = ',')
    {
        if (is_string($data)) {
            if (self::isJson($data)) {
                $multiDimensional = json_decode($data, 1);
                if ($flatten) {
                    return $this->flattenArray($multiDimensional);
                }
                return $multiDimensional;
            } else {
                return explode($stringDelimiter, $data);
            }
        }
        return $this->toArray($data, $flatten);
    }

    /**
     * You may wish to call this method rather than decoding the
     * JSON yourself and calling flattenArray()
     * or using the toArray() base method.
     *
     * @param $data
     * @param boolean $flatten default false
     * @return array
     */
    public function jsonToArrayConversion($data, $flatten = false)
    {
        if ($this->isJson($data)) {
            $data = json_decode($data, 1);
            if ($flatten) {
                $data = $this->flattenArray($data);
            }
            return $data;
        }
        return $this->toArray($data, $flatten);
    }

    /**
     * We can get calls from toArray where the data is an
     * array, in this case it will attempt to call
     * arrayToArrayConversion($data, $flatten), we need
     * to be able to handle this call even though we
     * already have an array.
     *
     * @param array $data
     * @param boolean $flatten default false
     * @return array
     */
    public function arrayToArrayConversion($data, $flatten = false)
    {
        if (is_array($data)) {
            if ($flatten) {
                return $this->flattenArray($data);
            }
            return $data;
        }
        return $this->toArray($data, $flatten);
    }

    public function NULLtoArrayConversion(){
        return array();
    }

    /**
     * The following methods are for converting from an array
     * to different formats.
     */



    /**
     * @param $data
     * @param boolean $includeHeaderFooter
     * @return string
     */
    public function arrayToXmlConversion($data, $includeHeaderFooter = true)
    {
        $xml = '';
        if ($includeHeaderFooter === true) {
            $xml .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
            $xml .= "<result>";
        }
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $intToWord = new DataHandlers_Converters_IntToWord($key);
                $key = $intToWord->convertIntToCamelCaseWord();
            }
            $xml .= "<{$key}>";
            if ($this->isIterable($value)) {
                $xml .= $this->arrayToXmlConversion($value, false);
            } else {
                $xml .= $value;
            }
            $xml .= "</{$key}>";
        }
        if ($includeHeaderFooter === true) {
            $xml .= "</result>";
        }
        return $xml;
    }

}
