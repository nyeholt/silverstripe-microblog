import { Action } from "redux";

export interface ReducerMap<T> {
    [key: string]: (state: T, action: Action) => T
}
