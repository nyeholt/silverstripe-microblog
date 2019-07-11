import { Action } from "redux";

export const ActionType = {
    STORE_LOAD: "STORE_LOAD",

    // from example module, please delete!
    SET_USERNAME: "SET_USERNAME"
}

export interface BaseAction extends Action {
    payload: any
}