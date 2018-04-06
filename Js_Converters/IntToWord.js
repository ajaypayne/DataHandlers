/*
 * @copyright Copyright (c) 2017. Eckoh UK Ltd.
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
  return IntToWord.getMeasures(number);
};

/**
 * Convert a number into CamelCased words
 * @param number
 * @returns {*}
 */
IntToWord.convertToCamelCase = function ConvertToCamelCase(number) {
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

result =  IntToWord.convert(1982);
console.log(result);
