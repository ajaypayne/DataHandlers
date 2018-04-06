/**
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
 * @param {*} data
 * @param {boolean} flatten
 * @returns {Array}
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

