/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */

String.prototype.repeat = function (num) {
    if (num < 0) {
        return '';
    } else {
        return new Array(num + 1).join(this);
    }
};

function is_object(x) {
    return Object.prototype.toString.call(x) === "[object Object]";
}

function is_defined(x) {
    return typeof x !== 'undefined';
}

function is_array(x) {
    return Object.prototype.toString.call(x) === "[object Array]";
}

function xlog(v) {
    var tab = 0;

    var rt = function () {
        return '    '.repeat(tab);
    };

    // Log Fn
    var lg = function (x) {
        var kk;

        // Limit
        if (tab > 10)
            return '[...]';
        var r = '';
        if (!is_defined(x)) {
            r = '[VAR: UNDEFINED]';
        } else if (x === '') {
            r = '[VAR: EMPTY STRING]';
        } else if (is_array(x)) {
            r = '[\n';
            tab++;
            for (kk in x) {
                if (x.hasOwnProperty(kk)) {
                    r += rt() + kk + ' : ' + lg(x[kk]) + ',\n';
                }
            }
            tab--;
            r += rt() + ']';
        } else if (is_object(x)) {
            r = '{\n';
            tab++;
            for (kk in x) {
                if (x.hasOwnProperty(kk)) {
                    r += rt() + kk + ' : ' + lg(x[kk]) + ',\n';
                }
            }
            tab--;
            r += rt() + '}';
        } else {
            r = x;
        }
        return r;
    };

    return lg(v);
}

function ee(val) {
    alert(xlog(val));
}