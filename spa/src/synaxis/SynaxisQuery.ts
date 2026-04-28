/**
 * SynaxisQuery — motor de filtros espejo de CORE/core/QueryEngine.php.
 *
 * Operadores: =, ==, !=, >, <, >=, <=, IN, contains.
 * Soporta además: search (full-text naïve), orderBy, limit, offset.
 */

import type {
    JsonValue,
    QueryParams,
    QueryResult,
    SynaxisDoc,
    WhereClause,
    WhereClauseObject,
    WhereOperator,
} from './types';

function fieldOf(cond: WhereClause): string | null {
    if (Array.isArray(cond)) return (cond[0] as string) ?? null;
    return (cond as WhereClauseObject).field ?? null;
}
function opOf(cond: WhereClause): WhereOperator {
    if (Array.isArray(cond)) return (cond[1] as WhereOperator) ?? '=';
    return (cond as WhereClauseObject).operator ?? '=';
}
function valOf(cond: WhereClause): JsonValue {
    if (Array.isArray(cond)) return (cond[2] as JsonValue) ?? null;
    return (cond as WhereClauseObject).value ?? null;
}

function compare(itemVal: unknown, op: WhereOperator, val: JsonValue): boolean {
    switch (op) {
        case '=':
        case '==':
            // eslint-disable-next-line eqeqeq
            return itemVal == val;
        case '!=':
            // eslint-disable-next-line eqeqeq
            return itemVal != val;
        case '>':
            return (itemVal as number) > (val as number);
        case '<':
            return (itemVal as number) < (val as number);
        case '>=':
            return (itemVal as number) >= (val as number);
        case '<=':
            return (itemVal as number) <= (val as number);
        case 'IN':
            return Array.isArray(val) && val.includes(itemVal as JsonValue);
        case 'contains':
            if (typeof itemVal === 'string') {
                return itemVal.toLowerCase().indexOf(String(val).toLowerCase()) !== -1;
            }
            if (Array.isArray(itemVal)) {
                return (itemVal as JsonValue[]).includes(val);
            }
            return false;
        default:
            return true;
    }
}

export function applyWhere<T extends SynaxisDoc>(items: T[], where?: WhereClause[]): T[] {
    if (!where || where.length === 0) return items;
    return items.filter((item) => {
        for (const cond of where) {
            const f = fieldOf(cond);
            if (!f) continue;
            const itemVal = (item as Record<string, unknown>)[f] ?? null;
            if (!compare(itemVal, opOf(cond), valOf(cond))) return false;
        }
        return true;
    });
}

export function applySearch<T extends SynaxisDoc>(items: T[], term?: string): T[] {
    if (!term) return items;
    const t = term.toLowerCase();
    return items.filter((item) => {
        for (const k of Object.keys(item)) {
            const v = (item as Record<string, unknown>)[k];
            if (typeof v === 'string' && v.toLowerCase().indexOf(t) !== -1) return true;
        }
        return false;
    });
}

export function applyOrderBy<T extends SynaxisDoc>(
    items: T[],
    orderBy?: QueryParams['orderBy'],
): T[] {
    if (!orderBy) return items;
    const field = orderBy.field || '_createdAt';
    const dir = (orderBy.direction || 'desc') === 'asc' ? 1 : -1;
    return [...items].sort((a, b) => {
        const va = (a as Record<string, unknown>)[field] ?? null;
        const vb = (b as Record<string, unknown>)[field] ?? null;
        if (va === vb) return 0;
        return ((va as number) > (vb as number) ? 1 : -1) * dir;
    });
}

export function applyPaging<T>(items: T[], offset?: number, limit?: number): T[] {
    const off = Number(offset) || 0;
    if (limit) return items.slice(off, off + Number(limit));
    if (off > 0) return items.slice(off);
    return items;
}

export function runQuery<T extends SynaxisDoc>(
    items: T[],
    params: QueryParams = {},
): QueryResult<T> {
    let out = applyWhere(items, params.where);
    out = applySearch(out, params.search);
    out = applyOrderBy(out, params.orderBy);
    const total = out.length;
    out = applyPaging(out, params.offset, params.limit);
    return { items: out, total, params };
}
