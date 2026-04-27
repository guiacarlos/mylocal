/**
 * SynaxisQuery — motor de consulta espejo de CORE/core/QueryEngine.php
 *
 * Operadores where: =, ==, !=, >, <, >=, <=, IN, contains
 * Forma: items[] -> { where[], search, orderBy{field,direction}, limit, offset }
 * Retorna { items, total, params }.
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.SynaxisQuery = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function applyWhere(items, where) {
        if (!Array.isArray(where) || where.length === 0) return items;

        return items.filter((item) => {
            for (const cond of where) {
                const field = cond[0] ?? cond.field;
                const op = cond[1] ?? cond.operator ?? '=';
                const val = cond[2] ?? cond.value;
                if (!field) continue;

                const itemVal = item[field] ?? null;

                switch (op) {
                    case '=':
                    case '==':
                        // eslint-disable-next-line eqeqeq
                        if (itemVal != val) return false;
                        break;
                    case '!=':
                        // eslint-disable-next-line eqeqeq
                        if (itemVal == val) return false;
                        break;
                    case '>':
                        if (!(itemVal > val)) return false;
                        break;
                    case '<':
                        if (!(itemVal < val)) return false;
                        break;
                    case '>=':
                        if (!(itemVal >= val)) return false;
                        break;
                    case '<=':
                        if (!(itemVal <= val)) return false;
                        break;
                    case 'IN':
                        if (!Array.isArray(val) || !val.includes(itemVal)) return false;
                        break;
                    case 'contains':
                        if (typeof itemVal === 'string') {
                            if (itemVal.toLowerCase().indexOf(String(val).toLowerCase()) === -1) return false;
                        } else if (Array.isArray(itemVal)) {
                            if (!itemVal.includes(val)) return false;
                        } else {
                            return false;
                        }
                        break;
                    default:
                        // Operador desconocido: no filtra.
                        break;
                }
            }
            return true;
        });
    }

    function applySearch(items, term) {
        if (!term) return items;
        const t = String(term).toLowerCase();
        return items.filter((item) => {
            for (const k of Object.keys(item)) {
                const v = item[k];
                if (typeof v === 'string' && v.toLowerCase().indexOf(t) !== -1) return true;
            }
            return false;
        });
    }

    function applyOrderBy(items, orderBy) {
        if (!orderBy) return items;
        const field = orderBy.field || '_createdAt';
        const dir = String(orderBy.direction || 'desc').toLowerCase() === 'asc' ? 1 : -1;
        return [...items].sort((a, b) => {
            const va = a[field] ?? null;
            const vb = b[field] ?? null;
            if (va === vb) return 0;
            return (va > vb ? 1 : -1) * dir;
        });
    }

    function applyPaging(items, offset, limit) {
        const off = Number(offset) || 0;
        if (limit) return items.slice(off, off + Number(limit));
        if (off > 0) return items.slice(off);
        return items;
    }

    function run(items, params = {}) {
        let out = applyWhere(items, params.where);
        out = applySearch(out, params.search);
        out = applyOrderBy(out, params.orderBy);
        const total = out.length;
        out = applyPaging(out, params.offset, params.limit);
        return { items: out, total, params };
    }

    return { run, applyWhere, applySearch, applyOrderBy, applyPaging };
});
