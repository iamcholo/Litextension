/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

Validation.add('lecaip-limit-demo', 'The value is not within the specified range 1 - 10',
    function(v, elm) {
        if (Validation.get('IsEmpty').test(v)) {
            return true;
        }

        var numValue = parseNumber(v);
        if (isNaN(numValue)) {
            return false;
        }

        var reRange = /^number-range-(-?[\d.,]+)?-(-?[\d.,]+)?$/,
            result = true;

        $w(elm.className).each(function(name) {
            var m = reRange.exec(name);
            if (m) {
                result = result
                    && (m[1] == null || m[1] == '' || numValue >= parseNumber(m[1]))
                    && (m[2] == null || m[2] == '' || numValue <= parseNumber(m[2]));
            }
        });

        return result;
    }
);
