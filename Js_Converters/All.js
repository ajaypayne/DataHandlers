/*
 * @author Blake Payne <ajaypayne@gmail.com>
 * @maintainer Blake Payne <ajaypayne@gmail.com>
 */
var ArrayConverter = {},
  methodName;

/**
 * Determine the typeof data that we have and
 * call the corresponding method to convert it
 * to an array.
 *
 * @param data mixed
 * @param flatten boolean
 * @return array
 */
ArrayConverter.toArray = function ToArray (data, flatten) {
  flatten = flatten || false;
  methodName = eval("ArrayConverter." + (typeof data) + 'ToArrayConversion');
  return methodName(data, flatten);
};

/**
 * Convert from an array to a different format.
 *
 * @param data array
 * @param to string desired format
 * @returns mixed
 */
ArrayConverter.fromArray = function FromArray (data, to) {
  if (!Array.isArray(data)) {
    throw 'Cannot convert.';
  }
  methodName = 'ArrayConverter.arrayTo' + to.charAt(0).toUpperCase() + to.slice(1) + 'Conversion';
  return methodName(data);
};

/**
 * Return an empty array if the data is undefined.
 * If the data passed in is not a undefined,
 * attempt to convert to a filled array.
 * @param data
 * @param flatten
 * @returns {*}
 */
ArrayConverter.undefinedToArrayConversion = function UndefinedToArray (data, flatten) {
  flatten = flatten || false;
  if (typeof data === "undefined") {
    return [];
  }
  return ArrayConverter.toArray(data, flatten);
};

/**
 * Convert a boolean to an array containing one
 * value, which is the value of the boolean.
 * If the data passed in is not a boolean,
 * attempt to convert the data to an array anyway.
 *
 * @param data
 * @param flatten
 * @returns {*}
 */
ArrayConverter.booleanToArrayConversion = function BooleanToArray (data, flatten) {
  flatten = flatten || false;
  if (typeof data === "boolean") {
    return [data];
  }
  return ArrayConverter.toArray(data, flatten);
};

/**
 * Convert a number to and array of digits.
 * If the data passed in is not a number,
 * attempt to convert the data to an array anyway.
 *
 * @param data
 * @param flatten
 * @returns {*}
 */
ArrayConverter.numberToArrayConversion = function NumberToArray (data, flatten) {
  flatten = flatten || false;
  if (typeof data === "number") {
    var output = [];
    var stringNumber = data.toString();
    for (var i = 0, len = stringNumber.length; i < len; i += 1) {
      output.push(stringNumber.charAt(i));
    }
    return output;
  }
  return ArrayConverter.toArray(data, flatten);
};

/**
 * Convert a string to an array on delimiter.
 * If the string is JSON, parse it and send
 * back to toArray to convert from object.
 * If the data passed in is not a string,
 * attempt to convert the data to an array anyway.
 *
 * @param data
 * @param flatten
 * @param delimiter
 * @returns {*}
 */
ArrayConverter.stringToArrayConversion = function StringToArray (data, flatten, delimiter) {
  delimiter = delimiter || ',';
  flatten = flatten || false;
  if (typeof data === "string") {
    if (ArrayConverter.isJSON(data)) {
      return ArrayConverter.toArray(JSON.parse(data), flatten);
    }
    return data.split(delimiter);
  }
  return ArrayConverter.toArray(data, flatten);
};

/**
 * Convert an object to an array and optionally flatten it.
 * If data passed in is not an object,
 * attempt to convert the data to an array anyway.
 *
 * @param data
 * @param flatten
 * @returns {*}
 */
ArrayConverter.objectToArrayConversion = function ObjectToArray (data, flatten) {
  flatten = flatten || false;
  if (typeof data === "object") {
    var output = [];
    for (var key in data) {
      if(data.hasOwnProperty(key)) {
        if (ArrayConverter.isIterable(data[key])) {
          output[key] = ArrayConverter.objectToArrayConversion(data[key], flatten);
        } else {
          output[key] = data[key];
        }
      }
    }
    if (flatten) {
      output = ArrayConverter.flattenArray(output);
    }
    return output;
  }
  return ArrayConverter.toArray(data, flatten);
};

/**
 * This will break a function down into it's
 * parts:
 * name
 * params
 * body
 * return vars
 *
 * If the data passed in is not a function,
 * attempt to convert the data to an array anyway.
 *
 * @param data
 * @param flatten
 * @returns {*}
 */
ArrayConverter.functionToArrayConversion = function FunctionToArray (data, flatten) {
  flatten = flatten || false;
  if (typeof data === "function") {
    var strFunc = data.toString().replace(/\r/g, '').replace(/\n/g, '');
    var output = [];
    var regEx = /(function)(\s)?([a-z]+)(\s)?(\()([a-z,\s]*)(\))(\s)?(\{)((.*[\r\n\s].*)*)(\})/gi;
    var matches = [];
    while (matches = regEx.exec(strFunc)) {
      output["name"] = matches[3];
      output["params"] = matches[6].split(',');
      output["body"] = matches[10].replace(/\s{2,}/g, ' ').replace(/\t/g, '');
    }
    return output;
  }
  return ArrayConverter.toArray(data, flatten);
};

/**
 * Returns well formed XML from an array.
 * @param data
 * @param includeHeader
 * @returns {string}
 */
ArrayConverter.arrayToXmlConversion = function ArrayToStringConversion(data, includeHeaderFooter) {
  includeHeaderFooter = includeHeaderFooter !== false;
  var xml = "";
  if (includeHeaderFooter) {
    xml += "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    xml += "<result>";
  }
  for (var key in data) {
    if (data.hasOwnProperty(key)) {
      var keyWord = key;
      if (!isNaN(parseInt(key))) {
        keyWord = IntToWord.convertToCamelCase(key);
      }
      xml += "<" + keyWord + ">";
      if (ArrayConverter.isIterable(data[key])) {
        xml += ArrayConverter.arrayToXmlConversion(ArrayConverter.toArray(data[key]), false);
      } else {
        xml += data[key];
      }
      xml += "</" + keyWord + ">";
    }
  }
  if (includeHeaderFooter) {
    xml += "</result>";
  }
  return xml;
};

//Helper functions needed to run some of the conversions above.
/**
 * Checks if a string is a JSON string.
 * @param data
 * @returns {boolean}
 */
ArrayConverter.isJSON = function IsJSON (data) {
  try {
    JSON.parse(data);
  } catch (e){
    return false;
  }
  return true;
};

/**
 * Checks if data is iterable.
 *
 * @param data
 * @returns {boolean}
 */
ArrayConverter.isIterable = function IsIterable (data) {
  return (typeof data === "object");
};

/**
 * Flatten a multidimensional array
 * into a one dimensional array.
 * If the data type passed in is not an array
 * return the data unmodified.
 *
 * @param data
 * @returns {*}
 */
ArrayConverter.flattenArray = function FlattenArray (data) {
  var output = [];
  if (Array.isArray(data)) {
    for (var key in data) {
      if (data.hasOwnProperty(key)) {
        if (Array.isArray(data[key])) {
          output = Object.assign([], ArrayConverter.flattenArray(data[key]), output);
        } else {
          output[key] = data[key];
        }
      }
    }
    return output;
  }
  return data;
};


//IntToWord Functions

/*
 * @author Blake Payne <ajaypayne@gmail.com>
 * @maintainer Blake Payne <ajaypayne@gmail.com>
 */

var IntToWord = {};
var flags = [];
var words = '';
var remainingDigits = '0';

/**
 * Convert a number into words
 * @param number
 * @returns {string}
 */
IntToWord.convert = function Convert(number) {
  words = '';
  return IntToWord.getMeasures(number);
};

/**
 * Convert a number into CamelCased words
 * @param number
 * @returns {*}
 */
IntToWord.convertToCamelCase = function ConvertToCamelCase(number) {
  words = '';
  var converted = IntToWord.convert(number);
  var wordsArr = converted.split(' ');
  var result = wordsArr[0];
  //we want the first one to be lower case still
  for (var i = 1; i < wordsArr.length; i++) {
    result += wordsArr[i].charAt(0).toUpperCase() + wordsArr[i].substr(1);
  }
  return result;
};

/**
 *
 * @param number
 * @returns {string}
 */
IntToWord.getMeasures = function GetMeasures(number) {
  number = parseInt(number);
  if (typeof number === "number") {
    if (isNaN(number)) {
      return IntToWord.finishConversion();
    }
    var measures = [];
    measures[9] = {method: "Hundreds", flag: "HundredMillions", suffix: "million"};
    measures[8] = {method: "Tens", flag: "TenMillions", suffix: "million "};
    measures[7] = {method: "Millions", flag: "Millions", suffix: "million "};
    measures[6] = {method: "Hundreds", flag: "HundredThousands", suffix: "thousand "};
    measures[5] = {method: "Tens", flag: "TenThousands", suffix: "thousand "};
    measures[4] = {method: "Thousands", flag: "Thousands", suffix: "thousand "};
    measures[3] = {method: "Hundreds", flag: "Hundreds", suffix: "hundred "};
    measures[2] = {method: "Tens", flag: "Tens", suffix: ""};
    measures[1] = {method: "Units", flag: "Units", suffix: ""};

    remainingDigits = number.toString();

    if (remainingDigits.length > 9) {
      return IntToWord.finishConversion()
    }
    for(var i = remainingDigits.length; i > 0; --i) {
      var length = IntToWord.getLength(remainingDigits);
      if (measures[length]) {
        var nextDigit = parseInt(remainingDigits.toString().substr(0, 1));
        IntToWord.includeAnd(measures[length].flag);
        var nextMethod = eval("IntToWord.get" + measures[length].method);
        nextMethod(nextDigit, measures[length].flag, measures[length].suffix);
        remainingDigits = remainingDigits.toString().substr(1, length - 1);
      }
    }
    return IntToWord.finishConversion();
  }
};

/**
 *
 * @param number
 * @returns {Number}
 */
IntToWord.getLength = function GetLength(number) {
  return number.toString().length;
};

/**
 *
 * @param flag
 * @returns {boolean}
 */
IntToWord.includeAnd = function IncludeAnd(flag) {
  switch (flag) {
    case 'Units':
      if (!flags.indexOf('Tens')) {
        words += "and ";
      }
      break;
    case 'Tens':
    case 'TenThousands':
    case 'TenMillions':
      words += "and ";
      break;
  }
};

IntToWord.getMillions = function GetMillions(current, flag, suffix) {
  if (current !== 0) {
    IntToWord.getUnits(current, flag, suffix);
    flags.push(flag);
  }
  IntToWord.getNextSuffix('million ');
};

IntToWord.getThousands = function GetThousands(current, flag, suffix) {
  if (current !== 0) {
    IntToWord.getUnits(current, flag, suffix);
    flags.push(flag);
  }
  IntToWord.getNextSuffix('thousand ');
};

IntToWord.getHundreds = function GetHundreds(current, flag, suffix) {
  if (current !== 0) {
    IntToWord.getUnits(current, flag, suffix);
    flags.push(flag);
  }
  IntToWord.getNextSuffix('hundred ');
};

IntToWord.getTens = function GetTens(current, flag, suffix) {
  var tens =
    {
      2: 'twenty ',
      3: 'thirty ',
      4: 'forty ',
      5: 'fifty ',
      6: 'sixty ',
      7: 'seventy ',
      8: 'eighty ',
      9: 'ninety '
    };
  if (current !== 0) {
    if (current === 1) {
      IntToWord.getTeens(suffix);
    } else {
      words += tens[current];
      flags.push(flag);
    }
  }
};

IntToWord.getTeens = function GetTeens(suffix) {
  remainingDigits.toString().substr(1);
  var teens = [];
  teens[0] ='ten ';
  teens[1] ='eleven ';
  teens[2] ='twelve ';
  teens[3] ='thirteen ';
  teens[4] ='fourteen ';
  teens[5] ='fifteen ';
  teens[6] ='sixteen ';
  teens[7] ='seventeen ';
  teens[8] ='eighteen ';
  teens[9] ='nineteen ';

  remainingDigits = remainingDigits.toString().substr(1);
  var current = remainingDigits.toString().substr(0, 1);
  words += teens[current] + suffix;
};

IntToWord.getUnits = function GetUnits(current, flag, suffix) {
  var units = [];
  units[1] = 'one ';
  units[2] = 'two ';
  units[3] = 'three ';
  units[4] = 'four ';
  units[5] = 'five ';
  units[6] = 'six ';
  units[7] = 'seven ';
  units[8] = 'eight ';
  units[9] = 'nine ';

  if (current !== 0) {
    words += units[current];
  }
  if (!flags.indexOf(flag)) {
    flags.push(flag);
  }
};

IntToWord.getNextSuffix = function GetNextSuffix(suffix) {
  if (suffix === '') {
    return;
  }
  var trimmed = words.trim();
  if (suffix === 'million '
    && trimmed.substr(trimmed.length -7) !== 'million'
  ) {
    words += suffix;
  } else if(suffix === 'thousand '
    && trimmed.substr(trimmed.length -8) !== 'thousand'
    && trimmed.substr(trimmed.length -7) !== 'million'
  ) {
    words += suffix;
  } else if (suffix === 'hundred '
    && trimmed.substr(trimmed.length -7) !== 'hundred'
    && trimmed.substr(trimmed.length -8) !== 'thousand'
    && trimmed.substr(trimmed.length -7) !== 'million'
  ) {
    words += suffix;
  }
};

/**
 *
 * @returns {*}
 */
IntToWord.finishConversion = function FinishConversion () {
  if (words.length === 0 || words === ''|| words === "zero") {
    return 'zero';
  }
  words.replace(/and thousand/g, 'thousand');
  words.replace(/and million/g, 'million');
  if (words.indexOf('and ') === 0) {
    words = words.substr(4);
  }

  return words.trim();
};


console.log(IntToWord.convertToCamelCase(123456));