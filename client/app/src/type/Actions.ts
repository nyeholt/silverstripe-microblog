import { Action } from "redux";

export const ActionType = {
    STORE_LOAD: "STORE_LOAD",

    // from example module, please delete!
    LOAD_POSTS: "LOAD_POSTS"
}

export interface BaseAction extends Action {
    payload: any
}