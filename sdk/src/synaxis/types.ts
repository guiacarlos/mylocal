export type JsonValue =
    | string
    | number
    | boolean
    | null
    | JsonValue[]
    | { [k: string]: JsonValue };

export interface SynaxisDoc {
    id: string;
    _version?: number;
    _createdAt?: string;
    _updatedAt?: string;
    [k: string]: any;
}

export interface WhereClauseTuple {
    0: string;
    1: WhereOperator;
    2: JsonValue;
    [k: number]: JsonValue | WhereOperator | string;
}

export interface WhereClauseObject {
    field: string;
    operator?: WhereOperator;
    value: JsonValue;
}

export type WhereClause = WhereClauseTuple | WhereClauseObject;

export type WhereOperator =
    | '='
    | '=='
    | '!='
    | '>'
    | '<'
    | '>='
    | '<='
    | 'IN'
    | 'contains';

export interface QueryParams {
    where?: WhereClause[];
    search?: string;
    orderBy?: { field: string; direction?: 'asc' | 'desc' };
    limit?: number;
    offset?: number;
}

export interface QueryResult<T extends SynaxisDoc = SynaxisDoc> {
    items: T[];
    total: number;
    params: QueryParams;
}

export interface SynaxisRequest {
    action: string;
    collection?: string;
    id?: string;
    params?: QueryParams;
    data?: Partial<SynaxisDoc> & { _REPLACE_?: boolean };
    [k: string]: unknown;
}

export interface SynaxisResponse<T = unknown> {
    success: boolean;
    data: T | null;
    error: string | null;
    code?: number;
}
