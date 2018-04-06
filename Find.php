<?php
/**
 * @author Blake Payne <ajaypayne@gmail.com>
 * @version 1.0.0
 * Work in progress, will be added to as different methods are
 * required.
 * Current methods
 * @findInArray can find an instance of any sting in any data.
 */

/**
 * Can take input in any format and return it in any other
 * format that you require.
 */
class DataHandlers_Find
{

    /**
     * Converts any data to an array, optionally flattened,
     * and traverses it to locate any given needles that exist
     * as either array keys or array values.
     *
     * If flattened is true, only one key => value will be
     * returned if the needle is found.
     *
     * If flattened is false or default, it the needle matches a key,
     * and the value of that key is iterable,
     * the full value will be returned.
     *
     * @param $needle mixed what you wish to find.
     * @param $haystack mixed the data to find the needle in.
     * @param $flatten bool false = return multidimensional array
     *                      true  = combine all layers into one flat array
     * @return array|string $results|Error message
     */
    public static function findInArray($needle, $haystack, $flatten = false)
    {
        $arrayConverter = new DataHandlers_Converters_Array();

        $needleArray = $arrayConverter->toArray($needle, $flatten);

        if ($arrayConverter->isIterable($haystack)) {
            $haystackArray = $arrayConverter->toArray($haystack, $flatten);
        } else {
            return '$haystack is not iterable, and cannot be searched for $needle';
        }

        /**
         * We should now have arrays for both needle and haystack.
         */
        $results = [];
        foreach ($needleArray as $needleKey) {
            foreach ($haystackArray as $haystackKey => $haystackValue) {
                if ($arrayConverter->isIterable($haystackValue)) {
                    $subResults = self::findInArray($needleKey, $haystackValue, $flatten);
                    if ($subResults !== '$haystack is not iterable, and cannot be searched for $needle') {
                        foreach ($subResults as $subResultsKey => $subResultsValue) {
                            $results[] = $subResultsValue;
                        }
                    }
                }
                if ($haystackKey === $needleKey || $haystackValue === $needleKey) {
                    $results[] = [$haystackKey => $haystackValue];
                }
            }
        }
        return $results;
    }
}